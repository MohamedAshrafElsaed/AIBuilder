<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectScan;
use App\Services\Projects\ChunkBuilder;
use App\Services\Projects\GitService;
use App\Services\Projects\KnowledgeBaseBuilder;
use App\Services\Projects\ProgressReporter;
use App\Services\Projects\RoutesExtractor;
use App\Services\Projects\ScannerService;
use App\Services\Projects\StackDetector;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScanProjectPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public int    $projectId,
        public string $trigger = 'manual',
        public bool   $forceFull = false,
    ) {}

    public function handle(
        GitService       $git,
        ScannerService   $scanner,
        StackDetector    $stackDetector,
        ChunkBuilder     $chunkBuilder,
        ProgressReporter $progress,
        RoutesExtractor  $routesExtractor,
    ): void {
        $project = Project::find($this->projectId);

        if (!$project) {
            Log::error('ScanProjectPipelineJob: Project not found', ['project_id' => $this->projectId]);
            return;
        }

        // Create scan record
        $scan = ProjectScan::create([
            'project_id' => $project->id,
            'status' => 'running',
            'trigger' => $this->trigger,
            'started_at' => now(),
            'scanner_version' => '2.1.0',
        ]);

        // Start scanning
        $project->markScanning('workspace', 0);

        try {
            $result = $this->runPipeline(
                $project,
                $scan,
                $git,
                $scanner,
                $stackDetector,
                $chunkBuilder,
                $progress,
                $routesExtractor,
            );

            $progress->complete($project, $scan, $result['commit_sha']);

            Log::info('ScanProjectPipelineJob: Completed successfully', [
                'project_id' => $project->id,
                'scan_id' => $result['kb_scan_id'] ?? null,
                'commit_sha' => $result['commit_sha'],
                'total_files' => $result['total_files'],
                'total_chunks' => $result['total_chunks'],
                'excluded_files' => $result['excluded_files'],
                'kb_validation' => $result['kb_validation'] ?? null,
            ]);
        } catch (Exception $e) {
            Log::error('ScanProjectPipelineJob: Failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $progress->fail($project, $scan, $e->getMessage());

            throw $e;
        }
    }

    private function runPipeline(
        Project          $project,
        ProjectScan      $scan,
        GitService       $git,
        ScannerService   $scanner,
        StackDetector    $stackDetector,
        ChunkBuilder     $chunkBuilder,
        ProgressReporter $progress,
        RoutesExtractor  $routesExtractor,
    ): array {
        $startTime = microtime(true);

        // Stage 1: Workspace
        $progress->startStage($project, $scan, 'workspace');
        $git->ensureWorkspace($project);
        $progress->completeStage($project, $scan, 'workspace');

        // Stage 2: Clone/Update Repository
        $progress->startStage($project, $scan, 'clone');
        $token = $this->getGitHubToken($project);
        $commitSha = $git->cloneOrUpdate($project, $token);
        $scan->update([
            'commit_sha' => $commitSha,
            'previous_commit_sha' => $project->last_commit_sha,
        ]);
        $progress->completeStage($project, $scan, 'clone');

        // Stage 3: Build File Manifest
        $progress->startStage($project, $scan, 'manifest');
        $result = $scanner->scanDirectory($project, function ($processed, $total) use ($project, $scan, $progress) {
            $percent = $total > 0 ? (int)(($processed / $total) * 100) : 0;
            $progress->updateStage($project, $scan, 'manifest', $percent);
        });

        // Persist manifest to database
        $scanner->persistManifest($project, $result['files']);

        // Update project stats
        $project->updateStats(
            $result['stats']['total_files'],
            $result['stats']['total_lines'],
            $result['stats']['total_bytes']
        );

        // Store exclusion rules version
        $project->update([
            'exclusion_rules_version' => $result['exclusion_rules_version'] ?? null,
        ]);

        // Save exclusion log for debugging
        $this->saveExclusionLog($project, $result['exclusion_log'] ?? []);

        $progress->completeStage($project, $scan, 'manifest');

        // Stage 4: Detect Stack
        $progress->startStage($project, $scan, 'stack');
        $stack = $stackDetector->detect($project);
        $project->updateStackInfo($stack);
        $stackDetector->saveStackJson($project, $stack);
        $progress->completeStage($project, $scan, 'stack');

        // Stage 5: Build Knowledge Chunks
        $progress->startStage($project, $scan, 'chunks');
        $chunkResult = $chunkBuilder->build($project, function ($processed, $total) use ($project, $scan, $progress) {
            $percent = $total > 0 ? (int)(($processed / $total) * 100) : 0;
            $progress->updateStage($project, $scan, 'chunks', $percent);
        });
        $progress->completeStage($project, $scan, 'chunks');

        // Stage 6: Build Knowledge Base Output
        $progress->startStage($project, $scan, 'finalize');

        // Extract routes from all route files recursively
        $routes = $routesExtractor->save($project);

        // Build standardized knowledge base output
        $kbBuilder = new KnowledgeBaseBuilder($project, $scan);
        $kbValidation = $kbBuilder->build();

        // Update project with KB scan ID
        $project->update([
            'scan_output_version' => '2.1.0',
            'last_commit_sha' => $commitSha,
            'last_kb_scan_id' => $kbBuilder->getScanId(),
            'scanned_at' => now(),
        ]);

        // Update scan record with stats
        $durationMs = (int)((microtime(true) - $startTime) * 1000);
        $scan->update([
            'files_scanned' => $result['stats']['total_files'],
            'files_excluded' => $result['stats']['excluded_count'] ?? 0,
            'chunks_created' => $chunkResult['total_chunks'] ?? 0,
            'total_lines' => $result['stats']['total_lines'],
            'total_bytes' => $result['stats']['total_bytes'],
            'duration_ms' => $durationMs,
        ]);

        // Cleanup old KB scans (keep last 3)
        $project->cleanupOldKbScans(3);

        $progress->completeStage($project, $scan, 'finalize');

        return [
            'commit_sha' => $commitSha,
            'total_files' => $result['stats']['total_files'],
            'total_chunks' => $chunkResult['total_chunks'] ?? 0,
            'excluded_files' => $result['stats']['excluded_count'] ?? 0,
            'routes_files' => count($routes),
            'duration_ms' => $durationMs,
            'kb_scan_id' => $kbBuilder->getScanId(),
            'kb_output_path' => $kbBuilder->getOutputPath(),
            'kb_validation' => $kbValidation,
        ];
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

    private function saveExclusionLog(Project $project, array $exclusionLog): void
    {
        if (empty($exclusionLog)) {
            return;
        }

        $logPath = $project->knowledge_path . '/exclusion_log.json';
        file_put_contents($logPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'total_excluded' => count($exclusionLog),
            'entries' => $exclusionLog,
        ], JSON_PRETTY_PRINT));
    }

    public function failed(Exception $exception): void
    {
        $project = Project::find($this->projectId);

        if ($project) {
            $project->markFailed('Job failed after retries: ' . $exception->getMessage());
        }

        Log::error('ScanProjectPipelineJob: Job failed permanently', [
            'project_id' => $this->projectId,
            'error' => $exception->getMessage(),
        ]);
    }
}
