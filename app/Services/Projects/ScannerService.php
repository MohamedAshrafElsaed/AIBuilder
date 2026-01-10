<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ScannerService
{
    private array $excludedDirs;
    private array $excludedExts;
    private array $binaryExts;
    private int $maxFileSize;

    public function __construct()
    {
        $this->excludedDirs = config('projects.excluded_directories', []);
        $this->excludedExts = config('projects.excluded_extensions', []);
        $this->binaryExts = config('projects.binary_extensions', []);
        $this->maxFileSize = config('projects.max_file_size', 1024 * 1024);

        // Add conditional exclusions based on config
        if (!config('projects.include_vendor', false)) {
            $this->excludedDirs[] = 'vendor';
        }

        if (!config('projects.include_storage', false)) {
            $this->excludedDirs[] = 'storage';
        }

        if (!config('projects.include_build_output', false)) {
            $this->excludedDirs[] = 'dist';
            $this->excludedDirs[] = 'build';
        }

        // Always exclude cache directories
        $this->excludedDirs[] = 'cache';
    }

    public function scanDirectory(Project $project, ?callable $progressCallback = null): array
    {
        $repoPath = realpath($project->repo_path);

        if (!$repoPath || !is_dir($repoPath)) {
            throw new \Exception("Repository path does not exist: {$project->repo_path}");
        }

        $files = [];
        $totalFiles = 0;
        $totalLines = 0;
        $totalBytes = 0;

        // Collect all files first
        $allFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $repoPath,
                RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $allFiles[] = $file;
            }
        }

        $totalCount = count($allFiles);
        $processed = 0;

        foreach ($allFiles as $file) {
            $relativePath = $this->getRelativePath($repoPath, $file->getPathname());

            // Skip if should be excluded
            if ($this->shouldExclude($relativePath)) {
                $processed++;
                continue;
            }

            $fileData = $this->scanFile($file, $relativePath);
            if ($fileData) {
                $files[] = $fileData;
                $totalFiles++;
                $totalLines += $fileData['line_count'];
                $totalBytes += $fileData['size_bytes'];
            }

            $processed++;

            if ($progressCallback && $processed % 100 === 0) {
                $progressCallback($processed, $totalCount);
            }
        }

        return [
            'files' => $files,
            'stats' => [
                'total_files' => $totalFiles,
                'total_lines' => $totalLines,
                'total_bytes' => $totalBytes,
            ],
        ];
    }

    public function scanFile(SplFileInfo $file, string $relativePath): ?array
    {
        $extension = strtolower($file->getExtension());
        $size = $file->getSize();
        $isBinary = $this->isBinaryFile($extension, $file->getPathname());

        $lineCount = 0;
        $sha1 = null;
        $mimeType = null;

        if (!$isBinary && $size <= $this->maxFileSize && $size > 0) {
            $content = @file_get_contents($file->getPathname());
            if ($content !== false) {
                $sha1 = sha1($content);
                $lineCount = substr_count($content, "\n") + 1;
            }
        } elseif ($size > 0) {
            $sha1 = @sha1_file($file->getPathname());
        }

        // Get mime type
        if (function_exists('mime_content_type')) {
            $mimeType = @mime_content_type($file->getPathname());
        }

        return [
            'path' => $relativePath,
            'extension' => $extension ?: null,
            'size_bytes' => $size,
            'sha1' => $sha1,
            'line_count' => $lineCount,
            'is_binary' => $isBinary,
            'mime_type' => $mimeType,
            'file_modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
        ];
    }

    public function persistManifest(Project $project, array $files): void
    {
        // Delete existing files
        $project->files()->delete();

        // Batch insert in chunks for performance
        $chunks = array_chunk($files, 500);

        foreach ($chunks as $chunk) {
            $records = array_map(fn($file) => array_merge(
                ['project_id' => $project->id],
                $file,
                ['created_at' => now(), 'updated_at' => now()]
            ), $chunk);

            ProjectFile::insert($records);
        }
    }

    public function updateChangedFiles(Project $project, array $changes): array
    {
        $repoPath = $project->repo_path;
        $updatedFiles = [];

        // Delete removed files
        if (!empty($changes['deleted'])) {
            $project->files()->whereIn('path', $changes['deleted'])->delete();
            $project->chunks()->whereIn('path', $changes['deleted'])->delete();
        }

        // Process added and modified files
        $toProcess = array_merge($changes['added'], $changes['modified']);

        foreach ($toProcess as $relativePath) {
            $fullPath = $repoPath . '/' . $relativePath;

            if (!file_exists($fullPath)) {
                continue;
            }

            $file = new SplFileInfo($fullPath);
            $fileData = $this->scanFile($file, $relativePath);

            if ($fileData) {
                $project->files()->updateOrCreate(
                    ['path' => $relativePath],
                    $fileData
                );
                $updatedFiles[] = $relativePath;
            }
        }

        return $updatedFiles;
    }

    public function buildDirectoryTree(Project $project): array
    {
        $files = $project->files()->get();
        $tree = [];

        foreach ($files as $file) {
            $parts = explode('/', $file->path);
            $current = &$tree;

            foreach ($parts as $i => $part) {
                if ($i === count($parts) - 1) {
                    // It's a file
                    $current['_files'][] = [
                        'name' => $part,
                        'path' => $file->path,
                        'size' => $file->size_bytes,
                        'lines' => $file->line_count,
                    ];
                } else {
                    // It's a directory
                    if (!isset($current[$part])) {
                        $current[$part] = ['_files' => []];
                    }
                    $current = &$current[$part];
                }
            }
        }

        return $tree;
    }

    public function getDirectorySummary(Project $project): array
    {
        $files = $project->files()->get();

        $directories = [];

        foreach ($files as $file) {
            $path = $file->path;

            // Get the directory path (not just top-level)
            $dir = dirname($path);

            // Handle root level files
            if ($dir === '.') {
                $dir = '(root)';
            }

            if (!isset($directories[$dir])) {
                $directories[$dir] = [
                    'directory' => $dir,
                    'file_count' => 0,
                    'total_size' => 0,
                    'total_lines' => 0,
                    'depth' => $dir === '(root)' ? 0 : substr_count($dir, '/') + 1,
                ];
            }

            $directories[$dir]['file_count']++;
            $directories[$dir]['total_size'] += $file->size_bytes;
            $directories[$dir]['total_lines'] += $file->line_count;
        }

        // Sort by path for hierarchical display
        ksort($directories);

        return array_values($directories);
    }

    public function getTopLevelDirectorySummary(Project $project): array
    {
        $files = $project->files()->get();

        $directories = [];

        foreach ($files as $file) {
            $path = $file->path;

            // Get top-level directory (first segment of path)
            if (str_contains($path, '/')) {
                $parts = explode('/', $path);
                $topDir = $parts[0];
            } else {
                $topDir = '(root)';
            }

            if (!isset($directories[$topDir])) {
                $directories[$topDir] = [
                    'directory' => $topDir,
                    'file_count' => 0,
                    'total_size' => 0,
                    'total_lines' => 0,
                ];
            }

            $directories[$topDir]['file_count']++;
            $directories[$topDir]['total_size'] += $file->size_bytes;
            $directories[$topDir]['total_lines'] += $file->line_count;
        }

        // Sort by file count descending
        usort($directories, fn($a, $b) => $b['file_count'] <=> $a['file_count']);

        return array_values($directories);
    }

    public function getFullDirectoryTree(Project $project): array
    {
        $files = $project->files()->get();

        $directories = [];

        foreach ($files as $file) {
            $path = $file->path;
            $dir = dirname($path);

            // Handle root files
            if ($dir === '.') {
                $dir = '/';
            }

            // Build all parent directories
            $parts = explode('/', $path);
            array_pop($parts); // Remove filename

            $currentPath = '';
            foreach ($parts as $part) {
                $currentPath = $currentPath ? $currentPath . '/' . $part : $part;

                if (!isset($directories[$currentPath])) {
                    $directories[$currentPath] = [
                        'path' => $currentPath,
                        'name' => $part,
                        'depth' => substr_count($currentPath, '/'),
                        'file_count' => 0,
                        'total_size' => 0,
                        'total_lines' => 0,
                    ];
                }
            }

            // Add file stats to its immediate parent directory
            $parentDir = dirname($path);
            if ($parentDir !== '.' && isset($directories[$parentDir])) {
                $directories[$parentDir]['file_count']++;
                $directories[$parentDir]['total_size'] += $file->size_bytes;
                $directories[$parentDir]['total_lines'] += $file->line_count;
            }
        }

        // Sort by path
        ksort($directories);

        return array_values($directories);
    }

    private function getRelativePath(string $basePath, string $fullPath): string
    {
        // Normalize paths
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $fullPath = str_replace('\\', '/', $fullPath);

        if (str_starts_with($fullPath, $basePath . '/')) {
            return substr($fullPath, strlen($basePath) + 1);
        }

        // Fallback: try realpath comparison
        $realBase = realpath($basePath);
        $realFull = realpath($fullPath);

        if ($realBase && $realFull && str_starts_with($realFull, $realBase . DIRECTORY_SEPARATOR)) {
            $relative = substr($realFull, strlen($realBase) + 1);
            return str_replace('\\', '/', $relative);
        }

        return basename($fullPath);
    }

    private function shouldExclude(string $relativePath): bool
    {
        // Normalize path separators
        $relativePath = str_replace('\\', '/', $relativePath);
        $pathParts = explode('/', $relativePath);

        // Check if any part of the path matches excluded directories
        foreach ($pathParts as $part) {
            if (in_array($part, $this->excludedDirs, true)) {
                return true;
            }
        }

        // Check excluded extensions
        foreach ($this->excludedExts as $excludedExt) {
            if (str_ends_with(strtolower($relativePath), '.' . strtolower($excludedExt))) {
                return true;
            }
        }

        return false;
    }

    private function isBinaryFile(string $extension, string $path): bool
    {
        if (in_array(strtolower($extension), $this->binaryExts, true)) {
            return true;
        }

        // Check first few bytes for binary content
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return true;
        }

        $bytes = fread($handle, 8192);
        fclose($handle);

        if ($bytes === false || $bytes === '') {
            return true;
        }

        // Check for null bytes (common in binary files)
        if (str_contains($bytes, "\0")) {
            return true;
        }

        return false;
    }
}
