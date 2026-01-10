<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectScan;
use App\Services\Projects\Concerns\HasDeterministicChunkId;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Builds standardized knowledge base output with deterministic chunk IDs.
 *
 * Output structure:
 * storage/app/project_kb/{project_id}/{scan_id}/
 *   ├── scan_meta.json
 *   ├── files_index.json (or .ndjson if > 10k files)
 *   ├── chunks.ndjson
 *   └── directory_stats.json
 */
class KnowledgeBaseBuilder
{
    use HasDeterministicChunkId;
    private const string SCANNER_VERSION = '2.1.0';
    private const int LARGE_FILE_THRESHOLD = 10000;

    private Project $project;
    private ProjectScan $scan;
    private ExclusionMatcher $exclusionMatcher;
    private string $scanId;
    private string $outputPath;
    private int $startTimeMs;

    // Validation counters
    private int $filesIndexCount = 0;
    private int $chunksCount = 0;
    private array $chunkIdsInIndex = [];
    private array $chunkIdsInChunks = [];

    public function __construct(Project $project, ProjectScan $scan)
    {
        $this->project = $project;
        $this->scan = $scan;
        $this->exclusionMatcher = new ExclusionMatcher($project);
        $this->startTimeMs = (int)(microtime(true) * 1000);
    }

    /**
     * Build the complete knowledge base output.
     *
     * @throws Exception if git state is invalid
     * @return array Validation summary
     */
    public function build(): array
    {
        // Validate git state first
        $this->validateGitState();

        // Generate deterministic scan ID
        $this->scanId = $this->generateScanId();

        // Create output directory
        $this->outputPath = $this->createOutputDirectory();

        // Build all artifacts
        $this->buildScanMeta();
        $this->buildFilesIndex();
        $this->buildChunksNdjson();
        $this->buildDirectoryStats();

        // Validate consistency
        $validation = $this->validateConsistency();

        // Update scan record with output path
        $this->scan->update([
            'meta' => array_merge($this->scan->meta ?? [], [
                'kb_output_path' => $this->outputPath,
                'kb_scan_id' => $this->scanId,
            ])
        ]);

        Log::info('KnowledgeBaseBuilder: Build complete', [
            'project_id' => $this->project->id,
            'scan_id' => $this->scanId,
            'output_path' => $this->outputPath,
            'validation' => $validation,
        ]);

        return $validation;
    }

    /**
     * Validate that the repository is in a clean, checked-out state.
     */
    private function validateGitState(): void
    {
        $repoPath = $this->project->repo_path;

        if (!is_dir($repoPath . '/.git')) {
            throw new Exception("Repository not found at: {$repoPath}");
        }

        // Get current HEAD commit
        $headFile = $repoPath . '/.git/HEAD';
        if (!file_exists($headFile)) {
            throw new Exception("Invalid git repository: HEAD file missing");
        }

        $headContent = trim(file_get_contents($headFile));

        // HEAD should point to a ref or be a detached commit
        if (str_starts_with($headContent, 'ref: ')) {
            $refPath = $repoPath . '/.git/' . substr($headContent, 5);
            if (!file_exists($refPath)) {
                throw new Exception("Git ref not found: " . substr($headContent, 5));
            }
        }

        // Verify we have a valid commit SHA
        $commitSha = $this->getHeadCommitSha();
        if (!$commitSha || strlen($commitSha) !== 40) {
            throw new Exception("Invalid HEAD commit SHA: {$commitSha}");
        }
    }

    /**
     * Get the current HEAD commit SHA directly from git files.
     */
    private function getHeadCommitSha(): string
    {
        $repoPath = $this->project->repo_path;
        $headFile = $repoPath . '/.git/HEAD';
        $headContent = trim(file_get_contents($headFile));

        if (str_starts_with($headContent, 'ref: ')) {
            $refPath = $repoPath . '/.git/' . substr($headContent, 5);
            if (file_exists($refPath)) {
                return trim(file_get_contents($refPath));
            }
            // Try packed-refs
            $packedRefs = $repoPath . '/.git/packed-refs';
            if (file_exists($packedRefs)) {
                $ref = substr($headContent, 5);
                foreach (file($packedRefs) as $line) {
                    $line = trim($line);
                    if (empty($line) || $line[0] === '#') continue;
                    $parts = preg_split('/\s+/', $line);
                    if (count($parts) >= 2 && $parts[1] === $ref) {
                        return $parts[0];
                    }
                }
            }
            throw new Exception("Cannot resolve ref: " . substr($headContent, 5));
        }

        // Detached HEAD - content is the SHA
        return $headContent;
    }

    /**
     * Generate a deterministic scan ID based on project + commit + timestamp.
     */
    private function generateScanId(): string
    {
        $timestamp = now()->format('YmdHis');
        $commitShort = substr($this->getHeadCommitSha(), 0, 8);
        return "scan_{$this->project->id}_{$commitShort}_{$timestamp}";
    }

    /**
     * Create the output directory structure.
     */
    private function createOutputDirectory(): string
    {
        $basePath = storage_path('app/project_kb');
        $outputPath = "{$basePath}/{$this->project->id}/{$this->scanId}";

        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        return $outputPath;
    }

    /**
     * Build scan_meta.json with complete metadata.
     */
    private function buildScanMeta(): void
    {
        $headCommitSha = $this->getHeadCommitSha();
        $files = $this->project->files()->where('is_excluded', false)->get();
        $chunks = $this->project->chunks()->count();
        $excludedFiles = $this->project->files()->where('is_excluded', true)->count();
        $durationMs = (int)(microtime(true) * 1000) - $this->startTimeMs;

        $meta = [
            'scan_id' => $this->scanId,
            'project_id' => $this->project->id,
            'repo_full_name' => $this->project->repo_full_name,
            'default_branch' => $this->project->default_branch,
            'selected_branch' => $this->project->selected_branch ?? $this->project->default_branch,
            'head_commit_sha' => $headCommitSha,
            'scanned_at_iso' => now()->toIso8601String(),
            'scanner_version' => self::SCANNER_VERSION,
            'exclusion_rules_version' => $this->exclusionMatcher->getRulesVersion(),
            'is_incremental' => $this->scan->is_incremental ?? false,
            'previous_scan_id' => $this->scan->meta['previous_scan_id'] ?? null,
            'stats' => [
                'total_files_scanned' => $files->count(),
                'total_files_excluded' => $excludedFiles,
                'total_chunks' => $chunks,
                'total_lines' => $files->sum('line_count'),
                'total_bytes' => $files->sum('size_bytes'),
                'scan_duration_ms' => $durationMs,
            ],
        ];

        $this->writeJson($this->outputPath . '/scan_meta.json', $meta);
    }

    /**
     * Build files_index.json with chunk ID references.
     * Uses NDJSON format for large repositories.
     */
    private function buildFilesIndex(): void
    {
        $files = $this->project->files()->orderBy('path')->get();
        $chunks = $this->project->chunks()->get()->groupBy('path');

        $useNdjson = $files->count() > self::LARGE_FILE_THRESHOLD;
        $outputFile = $this->outputPath . '/files_index.' . ($useNdjson ? 'ndjson' : 'json');

        $records = [];

        foreach ($files as $file) {
            $fileChunks = $chunks->get($file->path, collect());

            // Generate chunk IDs that match what we'll write to chunks.ndjson
            $chunkIds = $fileChunks->map(function ($chunk) use ($file) {
                return $this->generateChunkId(
                    $file->path,
                    $file->sha1,
                    $chunk->start_line,
                    $chunk->end_line
                );
            })->toArray();

            // Track for validation
            $this->chunkIdsInIndex = array_merge($this->chunkIdsInIndex, $chunkIds);

            $record = [
                'file_path' => $file->path,
                'extension' => $file->extension,
                'language' => $file->language ?? 'plaintext',
                'size_bytes' => $file->size_bytes,
                'total_lines' => $file->line_count,
                'file_sha1' => $file->sha1,
                'is_binary' => (bool)$file->is_binary,
                'is_excluded' => (bool)$file->is_excluded,
                'exclusion_reason' => $file->exclusion_reason,
                'framework_hints' => $file->framework_hints ?? [],
                'chunk_ids' => $chunkIds,
                'chunk_count' => count($chunkIds),
                'symbols_declared' => $file->symbols_declared ?? [],
                'imports' => $file->imports ?? [],
            ];

            $this->filesIndexCount++;

            if ($useNdjson) {
                file_put_contents($outputFile, json_encode($record) . "\n", FILE_APPEND);
            } else {
                $records[] = $record;
            }
        }

        if (!$useNdjson) {
            $this->writeJson($outputFile, $records);
        }
    }

    /**
     * Build chunks.ndjson with actual chunk content.
     */
    private function buildChunksNdjson(): void
    {
        $outputFile = $this->outputPath . '/chunks.ndjson';
        $handle = fopen($outputFile, 'w');

        if ($handle === false) {
            throw new Exception("Cannot open chunks output file: {$outputFile}");
        }

        $chunks = $this->project->chunks()
            ->orderBy('path')
            ->orderBy('start_line')
            ->cursor();

        foreach ($chunks as $chunk) {
            // Get the file SHA1 for this chunk
            $file = $this->project->files()->where('path', $chunk->path)->first();
            $fileSha1 = $file ? $file->sha1 : $chunk->sha1;

            // Generate the deterministic chunk ID
            $chunkId = $this->generateChunkId(
                $chunk->path,
                $fileSha1,
                $chunk->start_line,
                $chunk->end_line
            );

            // Track for validation
            $this->chunkIdsInChunks[] = $chunkId;

            // Get chunk content
            $content = $this->getChunkContent($chunk);

            $record = [
                'chunk_id' => $chunkId,
                'file_path' => $chunk->path,
                'file_sha1' => $fileSha1,
                'start_line' => $chunk->start_line,
                'end_line' => $chunk->end_line,
                'chunk_index' => $chunk->chunk_index ?? 0,
                'is_complete_file' => (bool)($chunk->is_complete_file ?? false),
                'chunk_bytes' => strlen($content ?? ''),
                'chunk_lines' => $chunk->end_line - $chunk->start_line + 1,
                'chunk_sha1' => $chunk->chunk_sha1 ?? sha1($content ?? ''),
                'content' => $content,
                'symbols_declared' => $chunk->symbols_declared ?? [],
                'symbols_used' => $chunk->symbols_used ?? [],
                'imports' => $chunk->imports ?? [],
                'references' => $chunk->references ?? [],
            ];

            fwrite($handle, json_encode($record) . "\n");
            $this->chunksCount++;
        }

        fclose($handle);
    }

    /**
     * Build directory_stats.json with aggregated statistics.
     */
    private function buildDirectoryStats(): void
    {
        $files = $this->project->files()->where('is_excluded', false)->get();

        // By directory
        $byDirectory = [];
        foreach ($files as $file) {
            $dir = dirname($file->path);
            if ($dir === '.') {
                $dir = '(root)';
            }

            if (!isset($byDirectory[$dir])) {
                $byDirectory[$dir] = [
                    'directory' => $dir,
                    'file_count' => 0,
                    'total_lines' => 0,
                    'total_bytes' => 0,
                    'depth' => $dir === '(root)' ? 0 : substr_count($dir, '/') + 1,
                ];
            }

            $byDirectory[$dir]['file_count']++;
            $byDirectory[$dir]['total_lines'] += $file->line_count ?? 0;
            $byDirectory[$dir]['total_bytes'] += $file->size_bytes ?? 0;
        }

        // Sort by path
        ksort($byDirectory);

        // By extension
        $byExtension = [];
        foreach ($files as $file) {
            $ext = $file->extension ?: 'no_extension';
            if (!isset($byExtension[$ext])) {
                $byExtension[$ext] = ['files' => 0, 'lines' => 0, 'bytes' => 0];
            }
            $byExtension[$ext]['files']++;
            $byExtension[$ext]['lines'] += $file->line_count ?? 0;
            $byExtension[$ext]['bytes'] += $file->size_bytes ?? 0;
        }

        $stats = [
            'generated_at' => now()->toIso8601String(),
            'by_directory' => array_values($byDirectory),
            'by_extension' => $byExtension,
        ];

        $this->writeJson($this->outputPath . '/directory_stats.json', $stats);
    }

    // generateChunkId() is provided by HasDeterministicChunkId trait

    /**
     * Get the raw content for a chunk, preserving original lines.
     */
    private function getChunkContent($chunk): ?string
    {
        $fullPath = $this->project->repo_path . '/' . $chunk->path;

        if (!file_exists($fullPath)) {
            return null;
        }

        $content = @file_get_contents($fullPath);
        if ($content === false) {
            return null;
        }

        $lines = explode("\n", $content);

        // Validate line numbers
        $startLine = max(1, $chunk->start_line);
        $endLine = min(count($lines), $chunk->end_line);

        if ($startLine > count($lines)) {
            return null;
        }

        // Extract lines (inclusive, 1-indexed to 0-indexed)
        $chunkLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);

        return implode("\n", $chunkLines);
    }

    /**
     * Validate that all chunk IDs in files_index exist in chunks.ndjson.
     */
    private function validateConsistency(): array
    {
        $indexSet = array_flip($this->chunkIdsInIndex);
        $chunksSet = array_flip($this->chunkIdsInChunks);

        $missingInChunks = array_diff_key($indexSet, $chunksSet);
        $orphanedChunks = array_diff_key($chunksSet, $indexSet);

        $isValid = empty($missingInChunks) && empty($orphanedChunks);

        $summary = [
            'is_valid' => $isValid,
            'files_index_entries' => $this->filesIndexCount,
            'chunks_count' => $this->chunksCount,
            'chunk_ids_in_index' => count($this->chunkIdsInIndex),
            'chunk_ids_in_chunks' => count($this->chunkIdsInChunks),
            'missing_in_chunks' => count($missingInChunks),
            'orphaned_chunks' => count($orphanedChunks),
            'coverage_percent' => count($this->chunkIdsInChunks) > 0
                ? round((count($this->chunkIdsInIndex) / max(count($this->chunkIdsInChunks), 1)) * 100, 2)
                : 100,
            'output_path' => $this->outputPath,
            'scan_id' => $this->scanId,
        ];

        if (!$isValid) {
            Log::warning('KnowledgeBaseBuilder: Validation failed', [
                'project_id' => $this->project->id,
                'missing_in_chunks' => array_keys($missingInChunks),
                'orphaned_chunks' => array_slice(array_keys($orphanedChunks), 0, 10),
            ]);
        }

        return $summary;
    }

    /**
     * Write JSON data to file with pretty printing.
     */
    private function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new Exception("JSON encoding failed for: {$path}");
        }
        file_put_contents($path, $json);
    }

    /**
     * Get the output path for the current build.
     */
    public function getOutputPath(): string
    {
        return $this->outputPath ?? '';
    }

    /**
     * Get the scan ID.
     */
    public function getScanId(): string
    {
        return $this->scanId ?? '';
    }
}
