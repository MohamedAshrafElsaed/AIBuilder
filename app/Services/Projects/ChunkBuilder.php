<?php

namespace App\Services\Projects;
namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFileChunk;

class ChunkBuilder
{
    private int $maxChunkBytes;
    private int $maxChunkLines;
    private ExclusionMatcher $exclusionMatcher;
    private LanguageDetector $languageDetector;
    private SymbolExtractor $symbolExtractor;

    private array $priorityDirs = [
        'app',
        'routes',
        'config',
        'database/migrations',
        'resources/views',
        'resources/js',
        'resources/css',
        'tests',
    ];

    public function __construct()
    {
        $config = config('projects.chunking');
        $this->maxChunkBytes = $config['max_bytes'] ?? 200 * 1024;
        $this->maxChunkLines = $config['max_lines'] ?? 500;
        $this->languageDetector = new LanguageDetector();
        $this->symbolExtractor = new SymbolExtractor();
    }

    public function build(Project $project, ?callable $progressCallback = null): array
    {
        $this->exclusionMatcher = new ExclusionMatcher($project);

        // Clear existing chunks
        $project->chunks()->delete();
        $this->clearChunkFiles($project);

        $files = $project->files()
            ->where('is_binary', false)
            ->where('is_excluded', false)
            ->where('size_bytes', '>', 0)
            ->where('size_bytes', '<=', config('projects.max_file_size'))
            ->orderByRaw($this->getDirectoryPriorityOrder())
            ->get();

        $totalFiles = $files->count();
        $processed = 0;
        $allChunks = [];
        $fileToChunks = [];

        foreach ($files as $file) {
            $filePath = $project->repo_path . '/' . $file->path;

            if (!file_exists($filePath)) {
                $processed++;
                continue;
            }

            $content = @file_get_contents($filePath);
            if ($content === false) {
                $processed++;
                continue;
            }

            $chunks = $this->chunkFile($project, $file, $content);

            foreach ($chunks as $chunk) {
                $allChunks[] = $chunk;
                $fileToChunks[$file->path][] = $chunk['chunk_id'];
            }

            $processed++;

            if ($progressCallback && $processed % 50 === 0) {
                $progressCallback($processed, $totalFiles);
            }
        }

        // Save all chunks
        $this->saveChunks($project, $allChunks);

        // Save path index
        $this->savePathIndex($project, $fileToChunks);

        // Save manifest and directories
        $this->saveManifest($project);
        $this->saveDirectories($project);

        return [
            'total_chunks' => count($allChunks),
            'file_to_chunks' => $fileToChunks,
            'exclusion_rules_version' => $this->exclusionMatcher->getRulesVersion(),
        ];
    }

    private function chunkFile(Project $project, ProjectFile $file, string $content): array
    {
        $lines = explode("\n", $content);
        $totalLines = count($lines);
        $fileSize = strlen($content);

        // Compute path hash for chunk_id generation
        $pathHash = substr(sha1($file->path), 0, 12);

        $chunks = [];

        // Single chunk for small files
        if ($totalLines <= $this->maxChunkLines && $fileSize <= $this->maxChunkBytes) {
            $chunkContent = $content;
            $chunkSha1 = sha1($chunkContent);

            $chunks[] = [
                'chunk_id' => "{$pathHash}:1-{$totalLines}",
                'file_path' => $file->path,
                'file_sha1' => $file->sha1,
                'start_line' => 1,
                'end_line' => $totalLines,
                'chunk_index' => 0,
                'is_complete_file' => true,
                'chunk_bytes' => $fileSize,
                'chunk_lines' => $totalLines,
                'chunk_sha1' => $chunkSha1,
                'content' => $chunkContent,
                'symbols_declared' => $this->symbolExtractor->extractDeclarations($content, $file->extension),
                'symbols_used' => $this->symbolExtractor->extractUsages($content, $file->extension),
                'imports' => $this->symbolExtractor->extractImports($content, $file->extension),
                'references' => [],
            ];

            return $chunks;
        }

        // Split large files
        $segments = $this->splitLargeFile($lines, $file->path);

        foreach ($segments as $index => $segment) {
            $chunkLines = array_slice($lines, $segment['start'] - 1, $segment['end'] - $segment['start'] + 1);
            $chunkContent = implode("\n", $chunkLines);
            $chunkSha1 = sha1($chunkContent);

            $chunks[] = [
                'chunk_id' => "{$pathHash}:{$segment['start']}-{$segment['end']}",
                'file_path' => $file->path,
                'file_sha1' => $file->sha1,
                'start_line' => $segment['start'],
                'end_line' => $segment['end'],
                'chunk_index' => $index,
                'is_complete_file' => false,
                'chunk_bytes' => strlen($chunkContent),
                'chunk_lines' => count($chunkLines),
                'chunk_sha1' => $chunkSha1,
                'content' => $chunkContent,
                'symbols_declared' => $this->symbolExtractor->extractDeclarations($chunkContent, $file->extension),
                'symbols_used' => $this->symbolExtractor->extractUsages($chunkContent, $file->extension),
                'imports' => $this->symbolExtractor->extractImports($chunkContent, $file->extension),
                'references' => [],
            ];
        }

        return $chunks;
    }

    private function splitLargeFile(array $lines, string $path): array
    {
        $segments = [];
        $totalLines = count($lines);
        $currentStart = 1;

        while ($currentStart <= $totalLines) {
            $candidateEnd = min($currentStart + $this->maxChunkLines - 1, $totalLines);

            // Try to break at a logical point
            if ($candidateEnd < $totalLines) {
                $breakPoint = $this->findBreakPoint($lines, $currentStart - 1, $candidateEnd - 1);
                if ($breakPoint !== null) {
                    $candidateEnd = $breakPoint + 1;
                }
            }

            $segments[] = [
                'start' => $currentStart,
                'end' => $candidateEnd,
            ];

            $currentStart = $candidateEnd + 1;
        }

        return $segments;
    }

    private function findBreakPoint(array $lines, int $start, int $end): ?int
    {
        $weights = config('projects.chunking.break_weights', [
            'empty_line' => 10,
            'function_boundary' => 8,
            'class_boundary' => 9,
            'block_end' => 7,
        ]);

        $bestPoint = null;
        $bestWeight = 0;

        // Search from end backwards to middle
        $midPoint = $start + (int)(($end - $start) / 2);

        for ($i = $end; $i > $midPoint; $i--) {
            $line = trim($lines[$i] ?? '');
            $weight = 0;

            // Empty line
            if (empty($line)) {
                $weight = $weights['empty_line'];
            }
            // Function/class boundaries
            elseif (preg_match('/^(function|class|trait|interface)\s/', $line)) {
                $weight = $weights['class_boundary'];
            }
            elseif (preg_match('/^(public|private|protected)\s+(function|static)/', $line)) {
                $weight = $weights['function_boundary'];
            }
            // Block end
            elseif (preg_match('/^\}\s*$/', $line)) {
                $weight = $weights['block_end'];
            }

            if ($weight > $bestWeight) {
                $bestWeight = $weight;
                $bestPoint = $i;
            }
        }

        return $bestPoint;
    }

    private function saveChunks(Project $project, array $chunks): void
    {
        // Batch insert chunk records
        $records = [];

        foreach ($chunks as $chunk) {
            $records[] = [
                'project_id' => $project->id,
                'chunk_id' => $chunk['chunk_id'],
                'path' => $chunk['file_path'],
                'start_line' => $chunk['start_line'],
                'end_line' => $chunk['end_line'],
                'chunk_index' => $chunk['chunk_index'],
                'sha1' => $chunk['file_sha1'],
                'chunk_sha1' => $chunk['chunk_sha1'],
                'is_complete_file' => $chunk['is_complete_file'],
                'chunk_size_bytes' => $chunk['chunk_bytes'],
                'symbols_declared' => json_encode($chunk['symbols_declared']),
                'symbols_used' => json_encode($chunk['symbols_used']),
                'imports' => json_encode($chunk['imports']),
                'references' => json_encode($chunk['references']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in batches of 500
        foreach (array_chunk($records, 500) as $batch) {
            ProjectFileChunk::insert($batch);
        }

        // Save chunk files grouped by path hash
        $chunksByPathHash = [];
        foreach ($chunks as $chunk) {
            $pathHash = explode(':', $chunk['chunk_id'])[0];
            $chunksByPathHash[$pathHash][] = $chunk;
        }

        foreach ($chunksByPathHash as $pathHash => $pathChunks) {
            $chunkFile = $project->chunks_path . '/' . $pathHash . '.json';
            file_put_contents($chunkFile, json_encode([
                'path_hash' => $pathHash,
                'file_path' => $pathChunks[0]['file_path'],
                'chunks' => $pathChunks,
            ], JSON_PRETTY_PRINT));
        }
    }

    private function savePathIndex(Project $project, array $fileToChunks): void
    {
        $indexPath = $project->indexes_path . '/path_index.json';
        file_put_contents($indexPath, json_encode($fileToChunks, JSON_PRETTY_PRINT));
    }

    private function saveManifest(Project $project): void
    {
        $files = $project->files()->get();

        $manifest = [
            'version' => '2.0.0',
            'project_id' => $project->id,
            'repo_full_name' => $project->repo_full_name,
            'default_branch' => $project->default_branch,
            'head_commit_sha' => $project->last_commit_sha,
            'scanned_at' => now()->toIso8601String(),
            'exclusion_rules_version' => $this->exclusionMatcher->getRulesVersion(),
            'stats' => [
                'total_files' => $project->total_files,
                'total_lines' => $project->total_lines,
                'total_bytes' => $project->total_size_bytes,
            ],
            'files' => $files->map(fn($f) => [
                'file_id' => 'f_' . substr(sha1($f->path), 0, 12),
                'path' => $f->path,
                'extension' => $f->extension,
                'language' => $f->language,
                'size_bytes' => $f->size_bytes,
                'line_count' => $f->line_count,
                'is_binary' => $f->is_binary,
                'is_excluded' => $f->is_excluded,
                'sha1' => $f->sha1,
                'framework_hints' => $f->framework_hints ?? [],
            ])->toArray(),
        ];

        $manifestPath = $project->knowledge_path . '/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    private function saveDirectories(Project $project): void
    {
        $scanner = new ScannerService();
        $directories = $scanner->getDirectorySummary($project);

        $dirPath = $project->knowledge_path . '/directories.json';
        file_put_contents($dirPath, json_encode($directories, JSON_PRETTY_PRINT));
    }

    private function clearChunkFiles(Project $project): void
    {
        $chunksPath = $project->chunks_path;
        if (is_dir($chunksPath)) {
            $files = glob($chunksPath . '/*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    private function getDirectoryPriorityOrder(): string
    {
        $cases = [];
        foreach ($this->priorityDirs as $index => $dir) {
            $escapedDir = addslashes($dir);
            $cases[] = "WHEN path LIKE '{$escapedDir}%' THEN {$index}";
        }
        $caseStatement = implode(' ', $cases);
        return "CASE {$caseStatement} ELSE 999 END, path";
    }

    public function rebuildForFiles(Project $project, array $filePaths): void
    {
        $this->exclusionMatcher = new ExclusionMatcher($project);

        // Delete existing chunks for these files
        $project->chunks()->whereIn('path', $filePaths)->delete();

        $fileToChunks = $this->loadPathIndex($project);

        // Remove old entries
        foreach ($filePaths as $path) {
            unset($fileToChunks[$path]);
        }

        // Rebuild chunks for specified files
        $files = $project->files()
            ->whereIn('path', $filePaths)
            ->where('is_binary', false)
            ->where('is_excluded', false)
            ->get();

        $allChunks = [];

        foreach ($files as $file) {
            $fullPath = $project->repo_path . '/' . $file->path;

            if (!file_exists($fullPath)) {
                continue;
            }

            $content = @file_get_contents($fullPath);
            if ($content === false) {
                continue;
            }

            // Update file SHA1
            $file->update(['sha1' => sha1($content)]);

            $chunks = $this->chunkFile($project, $file, $content);

            foreach ($chunks as $chunk) {
                $allChunks[] = $chunk;
                $fileToChunks[$file->path][] = $chunk['chunk_id'];
            }
        }

        if (!empty($allChunks)) {
            $this->saveChunks($project, $allChunks);
        }

        $this->savePathIndex($project, $fileToChunks);
    }

    private function loadPathIndex(Project $project): array
    {
        $indexPath = $project->indexes_path . '/path_index.json';
        if (file_exists($indexPath)) {
            return json_decode(file_get_contents($indexPath), true) ?? [];
        }
        return [];
    }
}
