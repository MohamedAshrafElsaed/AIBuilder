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
        public string $projectId,
        public array  $payload,
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

        if ($project->isScanning()) {
            Log::info('UpdateProjectFromWebhookJob: Project already scanning, skipping', ['project_id' => $this->projectId]);
            return;
        }

        $scan = ProjectScan::create([
            'project_id' => $project->id,
            'status' => 'running',
            'trigger' => 'webhook',
            'started_at' => now(),
            'scanner_version' => '2.1.0',
            'meta' => ['payload' => $this->payload],
        ]);

        $project->markScanning();

        try {
            $result = $this->runIncrementalUpdate($project, $scan, $git, $scanner, $stackDetector, $chunkBuilder, $progress);

            Log::info('UpdateProjectFromWebhookJob: Completed', [
                'project_id' => $project->id,
                'kb_scan_id' => $result['kb_scan_id'] ?? null,
                'commit_sha' => $result['commit_sha'] ?? null,
                'total_files' => $result['total_files'] ?? 0,
                'total_chunks' => $result['total_chunks'] ?? 0,
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

        $progress->startStage($project, $scan, 'clone');
        $token = $this->getGitHubToken($project);
        $commitSha = $git->cloneOrUpdate($project, $token);
        $scan->update([
            'commit_sha' => $commitSha,
            'previous_commit_sha' => $previousSha,
            'is_incremental' => true,
        ]);
        $progress->completeStage($project, $scan, 'clone');

        $progress->startStage($project, $scan, 'manifest');
        $changes = $git->getChangedFiles($project, $previousSha, $commitSha);

        $result = ['stats' => ['total_files' => 0, 'total_lines' => 0, 'total_bytes' => 0]];

        if (!empty($changes['added']) || !empty($changes['modified']) || !empty($changes['deleted'])) {
            $updatedPaths = $scanner->updateChangedFiles($project, $changes);

            if ($this->shouldRedetectStack($changes)) {
                $stack = $stackDetector->detect($project);
                $project->updateStackInfo($stack);
                $stackDetector->saveStackJson($project, $stack);
            }

            if (!empty($updatedPaths)) {
                $chunkBuilder->rebuildForFiles($project, $updatedPaths);
            }

            $this->recalculateStats($project);
        }

        $progress->completeStage($project, $scan, 'manifest');

        $progress->startStage($project, $scan, 'finalize');

        $kbBuilder = new KnowledgeBaseBuilder($project, $scan);
        $kbValidation = $kbBuilder->build();

        $project->update([
            'scan_output_version' => '2.1.0',
            'last_commit_sha' => $commitSha,
            'last_kb_scan_id' => $kbBuilder->getScanId(),
            'scanned_at' => now(),
        ]);

        $durationMs = (int)((microtime(true) - $startTime) * 1000);
        $scan->update([
            'files_scanned' => $project->total_files,
            'files_excluded' => $project->files()->where('is_excluded', true)->count(),
            'chunks_created' => $project->chunks()->count(),
            'total_lines' => $project->total_lines,
            'total_bytes' => $project->total_size_bytes,
            'duration_ms' => $durationMs,
        ]);

        $project->cleanupOldKbScans(3);

        $progress->completeStage($project, $scan, 'finalize');
        $progress->complete($project, $scan, $commitSha);

        return [
            'commit_sha' => $commitSha,
            'total_files' => $project->total_files,
            'total_chunks' => $project->chunks()->count(),
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
