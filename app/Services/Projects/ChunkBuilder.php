<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFileChunk;

class ChunkBuilder
{
    private int $maxChunkBytes;
    private int $maxChunkLines;
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
        $this->maxChunkBytes = config('projects.chunk_max_bytes', 200 * 1024);
        $this->maxChunkLines = config('projects.chunk_max_lines', 500);
    }

    public function build(Project $project, ?callable $progressCallback = null): int
    {
        // Clear existing chunks
        $project->chunks()->delete();
        $this->clearChunkFiles($project);

        $files = $project->files()
            ->where('is_binary', false)
            ->where('size_bytes', '>', 0)
            ->where('size_bytes', '<=', config('projects.max_file_size'))
            ->orderByRaw($this->getDirectoryPriorityOrder())
            ->get();

        $totalFiles = $files->count();
        $processed = 0;
        $chunkIndex = 0;
        $currentChunk = $this->initChunk();
        $pathIndex = [];

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

            $fileSize = strlen($content);
            $lines = explode("\n", $content);
            $totalLines = count($lines);

            // If file fits in current chunk
            if ($currentChunk['size'] + $fileSize <= $this->maxChunkBytes &&
                $currentChunk['lines'] + $totalLines <= $this->maxChunkLines * 2) {

                $this->addFileToChunk($currentChunk, $file, $content, 1, $totalLines);
                $pathIndex[$file->path][] = [
                    'chunk_id' => $this->formatChunkId($chunkIndex),
                    'start_line' => 1,
                    'end_line' => $totalLines,
                ];

            } elseif ($fileSize > $this->maxChunkBytes || $totalLines > $this->maxChunkLines) {
                // Large file - needs to be split across multiple chunks

                // First, save current chunk if it has content
                if ($currentChunk['files_count'] > 0) {
                    $this->saveChunk($project, $chunkIndex, $currentChunk);
                    $chunkIndex++;
                    $currentChunk = $this->initChunk();
                }

                // Split large file into segments
                $segments = $this->splitLargeFile($lines, $file->path);

                foreach ($segments as $segment) {
                    $segmentContent = implode("\n", array_slice($lines, $segment['start'] - 1, $segment['end'] - $segment['start'] + 1));
                    $segmentSize = strlen($segmentContent);

                    if ($currentChunk['size'] + $segmentSize > $this->maxChunkBytes) {
                        if ($currentChunk['files_count'] > 0) {
                            $this->saveChunk($project, $chunkIndex, $currentChunk);
                            $chunkIndex++;
                            $currentChunk = $this->initChunk();
                        }
                    }

                    $this->addFileToChunk($currentChunk, $file, $segmentContent, $segment['start'], $segment['end']);
                    $pathIndex[$file->path][] = [
                        'chunk_id' => $this->formatChunkId($chunkIndex),
                        'start_line' => $segment['start'],
                        'end_line' => $segment['end'],
                    ];
                }

            } else {
                // File doesn't fit - save current chunk and start new one
                if ($currentChunk['files_count'] > 0) {
                    $this->saveChunk($project, $chunkIndex, $currentChunk);
                    $chunkIndex++;
                    $currentChunk = $this->initChunk();
                }

                $this->addFileToChunk($currentChunk, $file, $content, 1, $totalLines);
                $pathIndex[$file->path][] = [
                    'chunk_id' => $this->formatChunkId($chunkIndex),
                    'start_line' => 1,
                    'end_line' => $totalLines,
                ];
            }

            $processed++;

            if ($progressCallback && $processed % 50 === 0) {
                $progressCallback($processed, $totalFiles);
            }
        }

        // Save final chunk if it has content
        if ($currentChunk['files_count'] > 0) {
            $this->saveChunk($project, $chunkIndex, $currentChunk);
            $chunkIndex++;
        }

        // Save path index
        $this->savePathIndex($project, $pathIndex);

        // Save manifest summary
        $this->saveManifest($project);

        // Save directories summary
        $this->saveDirectories($project);

        return $chunkIndex;
    }

    public function rebuildForFiles(Project $project, array $filePaths): void
    {
        // Get chunks that contain these files
        $affectedChunkIds = $project->chunks()
            ->whereIn('path', $filePaths)
            ->pluck('chunk_id')
            ->unique()
            ->toArray();

        // Delete affected chunk records
        $project->chunks()->whereIn('chunk_id', $affectedChunkIds)->delete();

        // Delete chunk files
        foreach ($affectedChunkIds as $chunkId) {
            $chunkFile = $project->chunks_path . '/' . $chunkId . '.json';
            if (file_exists($chunkFile)) {
                unlink($chunkFile);
            }
        }

        // Get all files that were in affected chunks and rebuild
        $filesToRebuild = $project->files()
            ->where('is_binary', false)
            ->whereIn('path', $filePaths)
            ->get();

        $chunkIndex = $this->getNextChunkIndex($project);
        $currentChunk = $this->initChunk();
        $pathIndex = $this->loadPathIndex($project);

        foreach ($filesToRebuild as $file) {
            $filePath = $project->repo_path . '/' . $file->path;

            if (!file_exists($filePath)) {
                continue;
            }

            $content = @file_get_contents($filePath);
            if ($content === false) {
                continue;
            }

            // Remove old index entries
            unset($pathIndex[$file->path]);

            $fileSize = strlen($content);
            $lines = explode("\n", $content);
            $totalLines = count($lines);

            if ($currentChunk['size'] + $fileSize <= $this->maxChunkBytes) {
                $this->addFileToChunk($currentChunk, $file, $content, 1, $totalLines);
                $pathIndex[$file->path][] = [
                    'chunk_id' => $this->formatChunkId($chunkIndex),
                    'start_line' => 1,
                    'end_line' => $totalLines,
                ];
            } else {
                if ($currentChunk['files_count'] > 0) {
                    $this->saveChunk($project, $chunkIndex, $currentChunk);
                    $chunkIndex++;
                    $currentChunk = $this->initChunk();
                }

                $this->addFileToChunk($currentChunk, $file, $content, 1, $totalLines);
                $pathIndex[$file->path][] = [
                    'chunk_id' => $this->formatChunkId($chunkIndex),
                    'start_line' => 1,
                    'end_line' => $totalLines,
                ];
            }
        }

        if ($currentChunk['files_count'] > 0) {
            $this->saveChunk($project, $chunkIndex, $currentChunk);
        }

        $this->savePathIndex($project, $pathIndex);
    }

    private function initChunk(): array
    {
        return [
            'files' => [],
            'files_count' => 0,
            'size' => 0,
            'lines' => 0,
        ];
    }

    private function addFileToChunk(array &$chunk, ProjectFile $file, string $content, int $startLine, int $endLine): void
    {
        $chunk['files'][] = [
            'path' => $file->path,
            'start_line' => $startLine,
            'end_line' => $endLine,
            'sha1' => $file->sha1,
            'content' => $content,
        ];
        $chunk['files_count']++;
        $chunk['size'] += strlen($content);
        $chunk['lines'] += ($endLine - $startLine + 1);
    }

    private function splitLargeFile(array $lines, string $path): array
    {
        $segments = [];
        $totalLines = count($lines);
        $currentStart = 1;

        while ($currentStart <= $totalLines) {
            $endLine = min($currentStart + $this->maxChunkLines - 1, $totalLines);

            // Try to break at a logical point (empty line or function boundary)
            if ($endLine < $totalLines) {
                $breakPoint = $this->findBreakPoint($lines, $currentStart - 1, $endLine - 1);
                if ($breakPoint !== null) {
                    $endLine = $breakPoint + 1;
                }
            }

            $segments[] = [
                'start' => $currentStart,
                'end' => $endLine,
            ];

            $currentStart = $endLine + 1;
        }

        return $segments;
    }

    private function findBreakPoint(array $lines, int $start, int $end): ?int
    {
        // Look backwards from end for a good break point
        for ($i = $end; $i > $start + ($end - $start) / 2; $i--) {
            $line = trim($lines[$i] ?? '');

            // Empty line
            if (empty($line)) {
                return $i;
            }

            // Function/class/method boundaries
            if (preg_match('/^(function|class|public|private|protected|}\s*$)/', $line)) {
                return $i;
            }
        }

        return null;
    }

    private function saveChunk(Project $project, int $index, array $chunk): void
    {
        $chunkId = $this->formatChunkId($index);
        $chunkData = [
            'chunk_id' => $chunkId,
            'files' => $chunk['files'],
            'stats' => [
                'total_files' => $chunk['files_count'],
                'total_lines' => $chunk['lines'],
                'total_bytes' => $chunk['size'],
            ],
        ];

        // Save chunk file
        $chunkPath = $project->chunks_path . '/' . $chunkId . '.json';
        file_put_contents($chunkPath, json_encode($chunkData, JSON_PRETTY_PRINT));

        // Save chunk records to database
        foreach ($chunk['files'] as $file) {
            ProjectFileChunk::create([
                'project_id' => $project->id,
                'chunk_id' => $chunkId,
                'path' => $file['path'],
                'start_line' => $file['start_line'],
                'end_line' => $file['end_line'],
                'sha1' => $file['sha1'],
                'chunk_file_path' => $chunkPath,
                'chunk_size_bytes' => $chunk['size'],
            ]);
        }
    }

    private function formatChunkId(int $index): string
    {
        return sprintf('chunk_%04d', $index);
    }

    private function getNextChunkIndex(Project $project): int
    {
        $lastChunk = $project->chunks()
            ->orderByDesc('chunk_id')
            ->first();

        if (!$lastChunk) {
            return 0;
        }

        preg_match('/chunk_(\d+)/', $lastChunk->chunk_id, $matches);
        return isset($matches[1]) ? ((int) $matches[1]) + 1 : 0;
    }

    private function savePathIndex(Project $project, array $pathIndex): void
    {
        $indexPath = $project->indexes_path . '/path_index.json';
        file_put_contents($indexPath, json_encode($pathIndex, JSON_PRETTY_PRINT));
    }

    private function loadPathIndex(Project $project): array
    {
        $indexPath = $project->indexes_path . '/path_index.json';
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath);
            return json_decode($content, true) ?? [];
        }
        return [];
    }

    private function saveManifest(Project $project): void
    {
        $files = $project->files()->get();

        $manifest = [
            'project_id' => $project->id,
            'repo_full_name' => $project->repo_full_name,
            'last_commit_sha' => $project->last_commit_sha,
            'scanned_at' => now()->toIso8601String(),
            'stats' => [
                'total_files' => $project->total_files,
                'total_lines' => $project->total_lines,
                'total_bytes' => $project->total_size_bytes,
            ],
            'files' => $files->map(fn($f) => [
                'path' => $f->path,
                'extension' => $f->extension,
                'size_bytes' => $f->size_bytes,
                'line_count' => $f->line_count,
                'is_binary' => $f->is_binary,
                'sha1' => $f->sha1,
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
            $cases[] = "WHEN path LIKE '{$dir}%' THEN {$index}";
        }
        $caseStatement = implode(' ', $cases);
        return "CASE {$caseStatement} ELSE 999 END, path";
    }
}
