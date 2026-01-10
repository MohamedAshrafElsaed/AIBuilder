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
        public int $projectId,
        public string $trigger = 'manual',
    ) {}

    public function handle(
        GitService $git,
        ScannerService $scanner,
        StackDetector $stackDetector,
        ChunkBuilder $chunkBuilder,
        ProgressReporter $progress,
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
        ]);

        // Start scanning
        $project->markScanning('workspace', 0);

        try {
            $commitSha = $this->runPipeline($project, $scan, $git, $scanner, $stackDetector, $chunkBuilder, $progress);
            $progress->complete($project, $scan, $commitSha);

            Log::info('ScanProjectPipelineJob: Completed successfully', [
                'project_id' => $project->id,
                'commit_sha' => $commitSha,
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
        Project $project,
        ProjectScan $scan,
        GitService $git,
        ScannerService $scanner,
        StackDetector $stackDetector,
        ChunkBuilder $chunkBuilder,
        ProgressReporter $progress,
    ): string {
        // Stage 1: Workspace
        $progress->startStage($project, $scan, 'workspace');
        $git->ensureWorkspace($project);
        $progress->completeStage($project, $scan, 'workspace');

        // Stage 2: Clone/Update Repository
        $progress->startStage($project, $scan, 'clone');
        $token = $this->getGitHubToken($project);
        $commitSha = $git->cloneOrUpdate($project, $token);
        $scan->update(['commit_sha' => $commitSha]);
        $progress->completeStage($project, $scan, 'clone');

        // Stage 3: Build File Manifest
        $progress->startStage($project, $scan, 'manifest');
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

        // Stage 4: Detect Stack
        $progress->startStage($project, $scan, 'stack');
        $stack = $stackDetector->detect($project);
        $project->updateStackInfo($stack);
        $stackDetector->saveStackJson($project, $stack);
        $progress->completeStage($project, $scan, 'stack');

        // Stage 5: Build Knowledge Chunks
        $progress->startStage($project, $scan, 'chunks');
        $chunkBuilder->build($project, function($processed, $total) use ($project, $scan, $progress) {
            $percent = $total > 0 ? (int) (($processed / $total) * 100) : 0;
            $progress->updateStage($project, $scan, 'chunks', $percent);
        });
        $progress->completeStage($project, $scan, 'chunks');

        // Stage 6: Finalize
        $progress->startStage($project, $scan, 'finalize');
        $this->saveRoutes($project);
        $progress->completeStage($project, $scan, 'finalize');

        return $commitSha;
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

    private function saveRoutes(Project $project): void
    {
        $repoPath = $project->repo_path;
        $routesDir = $repoPath . '/routes';

        if (!is_dir($routesDir)) {
            return;
        }

        $routes = [];
        $routeFiles = ['web.php', 'api.php', 'channels.php', 'console.php'];

        foreach ($routeFiles as $routeFile) {
            $path = $routesDir . '/' . $routeFile;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $routes[str_replace('.php', '', $routeFile)] = $this->extractRouteInfo($content);
            }
        }

        $routesPath = $project->knowledge_path . '/routes.json';
        file_put_contents($routesPath, json_encode($routes, JSON_PRETTY_PRINT));
    }

    private function extractRouteInfo(string $content): array
    {
        $routes = [];

        // Match Route::get/post/etc patterns
        preg_match_all(
            "/Route::(get|post|put|patch|delete|any|match|resource|apiResource)\s*\(\s*['\"]([^'\"]+)['\"]/",
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $routes[] = [
                'method' => strtoupper($match[1]),
                'uri' => $match[2],
            ];
        }

        return $routes;
    }

    public function failed(Exception $exception): void
    {
        $project = Project::find($this->projectId);

        if ($project) {
            $project->markFailed('Job failed after retries: ' . $exception->getMessage());
        }

        Log::error('ScanProjectPipelineJob: Permanently failed', [
            'project_id' => $this->projectId,
            'error' => $exception->getMessage(),
        ]);
    }
}
