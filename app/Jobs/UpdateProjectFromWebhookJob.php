<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectScan;
use App\Services\Projects\ChunkBuilder;
use App\Services\Projects\GitService;
use App\Services\Projects\ProgressReporter;
use App\Services\Projects\ScannerService;
use App\Services\Projects\StackDetector;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateProjectFromWebhookJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;
    public int $backoff = 30;
    public int $uniqueFor = 60;

    public function __construct(
        public int $projectId,
        public array $payload,
    ) {}

    public function uniqueId(): string
    {
        return 'webhook_update_' . $this->projectId;
    }

    public function handle(
        GitService $git,
        ScannerService $scanner,
        StackDetector $stackDetector,
        ChunkBuilder $chunkBuilder,
        ProgressReporter $progress,
    ): void {
        $project = Project::find($this->projectId);

        if (!$project) {
            Log::warning('UpdateProjectFromWebhookJob: Project not found', ['project_id' => $this->projectId]);
            return;
        }

        // Skip if project is already being scanned
        if ($project->isScanning()) {
            Log::info('UpdateProjectFromWebhookJob: Project already scanning, skipping', ['project_id' => $this->projectId]);
            return;
        }

        // Create scan record
        $scan = ProjectScan::create([
            'project_id' => $project->id,
            'status' => 'running',
            'trigger' => 'webhook',
            'started_at' => now(),
            'meta' => ['payload' => $this->payload],
        ]);

        $project->markScanning('clone', 0);

        try {
            $this->runIncrementalUpdate($project, $scan, $git, $scanner, $stackDetector, $chunkBuilder, $progress);

            Log::info('UpdateProjectFromWebhookJob: Completed', ['project_id' => $project->id]);
        } catch (Exception $e) {
            Log::error('UpdateProjectFromWebhookJob: Failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            $progress->fail($project, $scan, $e->getMessage());

            throw $e;
        }
    }

    private function runIncrementalUpdate(
        Project $project,
        ProjectScan $scan,
        GitService $git,
        ScannerService $scanner,
        StackDetector $stackDetector,
        ChunkBuilder $chunkBuilder,
        ProgressReporter $progress,
    ): void {
        $previousSha = $project->last_commit_sha;

        // Fetch and update repository
        $progress->startStage($project, $scan, 'clone');
        $token = $this->getGitHubToken($project);
        $newSha = $git->cloneOrUpdate($project, $token);
        $scan->update(['commit_sha' => $newSha]);
        $progress->completeStage($project, $scan, 'clone');

        // If no previous SHA, do a full scan instead
        if (!$previousSha) {
            $this->runFullScan($project, $scan, $scanner, $stackDetector, $chunkBuilder, $progress, $newSha);
            return;
        }

        // Get changed files
        $progress->startStage($project, $scan, 'manifest');
        $changes = $this->getChangesFromPayload();

        if (empty($changes['added']) && empty($changes['modified']) && empty($changes['deleted'])) {
            // Fall back to git diff
            $changes = $git->getChangedFiles($project, $previousSha, $newSha);
        }

        $totalChanges = count($changes['added']) + count($changes['modified']) + count($changes['deleted']);

        if ($totalChanges === 0) {
            Log::info('UpdateProjectFromWebhookJob: No changes detected', ['project_id' => $project->id]);
            $progress->complete($project, $scan, $newSha);
            return;
        }

        Log::info('UpdateProjectFromWebhookJob: Processing changes', [
            'project_id' => $project->id,
            'added' => count($changes['added']),
            'modified' => count($changes['modified']),
            'deleted' => count($changes['deleted']),
        ]);

        // Update manifest for changed files
        $updatedFiles = $scanner->updateChangedFiles($project, $changes);
        $this->recalculateStats($project);
        $progress->completeStage($project, $scan, 'manifest');

        // Check if stack detection is needed
        $progress->startStage($project, $scan, 'stack');
        if ($this->shouldRedetectStack($changes)) {
            $stack = $stackDetector->detect($project);
            $project->updateStackInfo($stack);
            $stackDetector->saveStackJson($project, $stack);
        }
        $progress->completeStage($project, $scan, 'stack');

        // Rebuild chunks for affected files
        $progress->startStage($project, $scan, 'chunks');
        if (!empty($updatedFiles)) {
            $chunkBuilder->rebuildForFiles($project, $updatedFiles);
        }
        $progress->completeStage($project, $scan, 'chunks');

        // Finalize
        $progress->startStage($project, $scan, 'finalize');
        $progress->completeStage($project, $scan, 'finalize');

        $progress->complete($project, $scan, $newSha);
    }

    private function runFullScan(
        Project $project,
        ProjectScan $scan,
        ScannerService $scanner,
        StackDetector $stackDetector,
        ChunkBuilder $chunkBuilder,
        ProgressReporter $progress,
        string $commitSha,
    ): void {
        // Full manifest scan
        $result = $scanner->scanDirectory($project, function($processed, $total) use ($project, $scan, $progress) {
            $percent = $total > 0 ? (int) (($processed / $total) * 100) : 0;
            $progress->updateStage($project, $scan, 'manifest', $percent);
        });
        $scanner->persistManifest($project, $result['files']);
        $project->updateStats(
            $result['stats']['total_files'],
            $result['stats']['total_lines'],
            $result['stats']['total_bytes']
        );
        $progress->completeStage($project, $scan, 'manifest');

        // Stack detection
        $progress->startStage($project, $scan, 'stack');
        $stack = $stackDetector->detect($project);
        $project->updateStackInfo($stack);
        $stackDetector->saveStackJson($project, $stack);
        $progress->completeStage($project, $scan, 'stack');

        // Build chunks
        $progress->startStage($project, $scan, 'chunks');
        $chunkBuilder->build($project, function($processed, $total) use ($project, $scan, $progress) {
            $percent = $total > 0 ? (int) (($processed / $total) * 100) : 0;
            $progress->updateStage($project, $scan, 'chunks', $percent);
        });
        $progress->completeStage($project, $scan, 'chunks');

        // Finalize
        $progress->startStage($project, $scan, 'finalize');
        $progress->completeStage($project, $scan, 'finalize');

        $progress->complete($project, $scan, $commitSha);
    }

    private function getChangesFromPayload(): array
    {
        $changes = [
            'added' => [],
            'modified' => [],
            'deleted' => [],
        ];

        // Extract from GitHub push payload
        foreach ($this->payload['commits'] ?? [] as $commit) {
            $changes['added'] = array_merge($changes['added'], $commit['added'] ?? []);
            $changes['modified'] = array_merge($changes['modified'], $commit['modified'] ?? []);
            $changes['deleted'] = array_merge($changes['deleted'], $commit['removed'] ?? []);
        }

        // Remove duplicates
        $changes['added'] = array_unique($changes['added']);
        $changes['modified'] = array_unique($changes['modified']);
        $changes['deleted'] = array_unique($changes['deleted']);

        return $changes;
    }

    private function shouldRedetectStack(array $changes): bool
    {
        $stackFiles = [
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'tailwind.config.js',
            'tailwind.config.ts',
            'vite.config.js',
            'vite.config.ts',
            'webpack.mix.js',
        ];

        $allChanges = array_merge($changes['added'], $changes['modified']);

        foreach ($allChanges as $file) {
            if (in_array(basename($file), $stackFiles)) {
                return true;
            }
        }

        return false;
    }

    private function recalculateStats(Project $project): void
    {
        $stats = $project->files()
            ->selectRaw('COUNT(*) as total_files, SUM(line_count) as total_lines, SUM(size_bytes) as total_bytes')
            ->first();

        $project->updateStats(
            $stats->total_files ?? 0,
            $stats->total_lines ?? 0,
            $stats->total_bytes ?? 0
        );
    }

    private function getGitHubToken(Project $project): string
    {
        $user = $project->user;
        $account = $user->githubAccount;

        if (!$account || !$account->hasValidToken()) {
            throw new Exception('No valid GitHub token available');
        }

        return $account->access_token;
    }

    public function failed(Exception $exception): void
    {
        $project = Project::find($this->projectId);

        if ($project) {
            $project->markFailed('Webhook update failed: ' . $exception->getMessage());
        }

        Log::error('UpdateProjectFromWebhookJob: Permanently failed', [
            'project_id' => $this->projectId,
            'error' => $exception->getMessage(),
        ]);
    }
}
