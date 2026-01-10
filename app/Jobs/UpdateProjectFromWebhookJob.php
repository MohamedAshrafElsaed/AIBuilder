<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectScan;
use App\Services\Projects\ChunkBuilder;
use App\Services\Projects\GitService;
use App\Services\Projects\KnowledgeBaseBuilder;
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
        public int   $projectId,
        public array $payload,
    ) {}

    public function uniqueId(): string
    {
        return 'webhook_update_' . $this->projectId;
    }

    public function handle(
        GitService       $git,
        ScannerService   $scanner,
        StackDetector    $stackDetector,
        ChunkBuilder     $chunkBuilder,
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
            'scanner_version' => '2.1.0',
            'meta' => ['payload' => $this->payload],
        ]);

        $project->markScanning('clone', 0);

        try {
            $result = $this->runIncrementalUpdate($project, $scan, $git, $scanner, $stackDetector, $chunkBuilder, $progress);

            Log::info('UpdateProjectFromWebhookJob: Completed', [
                'project_id' => $project->id,
                'kb_scan_id' => $result['kb_scan_id'] ?? null,
                'commit_sha' => $result['commit_sha'] ?? null,
            ]);
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
        Project          $project,
        ProjectScan      $scan,
        GitService       $git,
        ScannerService   $scanner,
        StackDetector    $stackDetector,
        ChunkBuilder     $chunkBuilder,
        ProgressReporter $progress,
    ): array {
        $startTime = microtime(true);
        $previousSha = $project->last_commit_sha;

        // Clone/update repo
        $progress->startStage($project, $scan, 'clone');
        $token = $this->getGitHubToken($project);
        $newSha = $git->cloneOrUpdate($project, $token);

        $scan->update([
            'commit_sha' => $newSha,
            'previous_commit_sha' => $previousSha,
        ]);
        $progress->completeStage($project, $scan, 'clone');

        // Check if this is actually a new commit
        if ($previousSha === $newSha) {
            Log::info('UpdateProjectFromWebhookJob: No new commits', ['project_id' => $project->id]);
            $progress->complete($project, $scan, $newSha);
            return ['commit_sha' => $newSha, 'no_changes' => true];
        }

        // Get changed files
        $progress->startStage($project, $scan, 'manifest');
        $changes = $git->getChangedFiles($project, $previousSha, $newSha);

        $totalChanges = count($changes['added']) + count($changes['modified']) + count($changes['deleted']);

        // If too many changes, do a full scan
        if ($totalChanges > 500 || empty($previousSha)) {
            return $this->runFullScan($project, $scan, $scanner, $stackDetector, $chunkBuilder, $progress, $newSha, $startTime);
        }

        $scan->update([
            'is_incremental' => true,
            'meta' => array_merge($scan->meta ?? [], [
                'changes' => [
                    'added' => count($changes['added']),
                    'modified' => count($changes['modified']),
                    'deleted' => count($changes['deleted']),
                ],
            ]),
        ]);

        Log::debug('UpdateProjectFromWebhookJob: Processing incremental changes', [
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

        // Build KB output
        $progress->startStage($project, $scan, 'finalize');

        $kbBuilder = new KnowledgeBaseBuilder($project, $scan);
        $kbValidation = $kbBuilder->build();

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        $project->update([
            'scan_output_version' => '2.1.0',
            'last_commit_sha' => $newSha,
            'last_kb_scan_id' => $kbBuilder->getScanId(),
            'scanned_at' => now(),
        ]);

        $scan->update([
            'files_scanned' => count($updatedFiles),
            'duration_ms' => $durationMs,
        ]);

        $project->cleanupOldKbScans(3);

        $progress->completeStage($project, $scan, 'finalize');
        $progress->complete($project, $scan, $newSha);

        return [
            'commit_sha' => $newSha,
            'updated_files' => count($updatedFiles),
            'duration_ms' => $durationMs,
            'kb_scan_id' => $kbBuilder->getScanId(),
            'kb_validation' => $kbValidation,
        ];
    }

    private function runFullScan(
        Project          $project,
        ProjectScan      $scan,
        ScannerService   $scanner,
        StackDetector    $stackDetector,
        ChunkBuilder     $chunkBuilder,
        ProgressReporter $progress,
        string           $commitSha,
        float            $startTime,
    ): array {
        $scan->update(['is_incremental' => false]);

        // Full manifest scan
        $result = $scanner->scanDirectory($project, function ($processed, $total) use ($project, $scan, $progress) {
            $percent = $total > 0 ? (int)(($processed / $total) * 100) : 0;
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
        $chunkResult = $chunkBuilder->build($project, function ($processed, $total) use ($project, $scan, $progress) {
            $percent = $total > 0 ? (int)(($processed / $total) * 100) : 0;
            $progress->updateStage($project, $scan, 'chunks', $percent);
        });
        $progress->completeStage($project, $scan, 'chunks');

        // Build KB output
        $progress->startStage($project, $scan, 'finalize');

        $kbBuilder = new KnowledgeBaseBuilder($project, $scan);
        $kbValidation = $kbBuilder->build();

        $durationMs = (int)((microtime(true) - $startTime) * 1000);

        $project->update([
            'scan_output_version' => '2.1.0',
            'last_commit_sha' => $commitSha,
            'last_kb_scan_id' => $kbBuilder->getScanId(),
            'scanned_at' => now(),
        ]);

        $scan->update([
            'files_scanned' => $result['stats']['total_files'],
            'files_excluded' => $result['stats']['excluded_count'] ?? 0,
            'chunks_created' => $chunkResult['total_chunks'] ?? 0,
            'total_lines' => $result['stats']['total_lines'],
            'total_bytes' => $result['stats']['total_bytes'],
            'duration_ms' => $durationMs,
        ]);

        $project->cleanupOldKbScans(3);

        $progress->completeStage($project, $scan, 'finalize');
        $progress->complete($project, $scan, $commitSha);

        return [
            'commit_sha' => $commitSha,
            'total_files' => $result['stats']['total_files'],
            'total_chunks' => $chunkResult['total_chunks'] ?? 0,
            'duration_ms' => $durationMs,
            'kb_scan_id' => $kbBuilder->getScanId(),
            'kb_validation' => $kbValidation,
        ];
    }

    private function shouldRedetectStack(array $changes): bool
    {
        $stackFiles = [
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'vite.config.js',
            'vite.config.ts',
            'webpack.mix.js',
            'tailwind.config.js',
            'tailwind.config.ts',
        ];

        $allChanges = array_merge($changes['added'], $changes['modified'], $changes['deleted']);

        foreach ($allChanges as $path) {
            if (in_array(basename($path), $stackFiles)) {
                return true;
            }
        }

        return false;
    }

    private function recalculateStats(Project $project): void
    {
        $stats = $project->files()
            ->where('is_excluded', false)
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
}
