<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'repo_full_name',
        'repo_id',
        'default_branch',
        'selected_branch',
        'status',
        'current_stage',
        'stage_percent',
        'scanned_at',
        'last_commit_sha',
        'parent_commit_sha',
        'scan_output_version',
        'exclusion_rules_version',
        'last_migration_at',
        'last_error',
        'stack_info',
        'total_files',
        'total_lines',
        'total_size_bytes',
        'last_kb_scan_id',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'last_migration_at' => 'datetime',
            'stack_info' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(ProjectScan::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ProjectFileChunk::class);
    }

    public function latestScan(): ?ProjectScan
    {
        return $this->scans()->latest()->first();
    }

    // -------------------------------------------------------------------------
    // Path Accessors - Unified Storage
    // -------------------------------------------------------------------------

    public function getStoragePathAttribute(): string
    {
        return config('projects.storage_path') . '/' . $this->id;
    }

    public function getRepoPathAttribute(): string
    {
        return $this->storage_path . '/repo';
    }

    public function getKnowledgePathAttribute(): string
    {
        return $this->storage_path . '/knowledge';
    }

    public function getChunksPathAttribute(): string
    {
        return $this->knowledge_path . '/chunks';
    }

    public function getIndexesPathAttribute(): string
    {
        return $this->knowledge_path . '/indexes';
    }

    /**
     * Get the base path for KB scan outputs (unified under knowledge_path).
     */
    public function getKbBasePathAttribute(): string
    {
        return $this->knowledge_path . '/scans';
    }

    /**
     * Get the path for a specific scan's knowledge base output.
     */
    public function getKbScanPath(string $scanId): string
    {
        return $this->kb_base_path . '/' . $scanId;
    }

    /**
     * Get the path for the latest scan's knowledge base output.
     */
    public function getLatestKbPathAttribute(): ?string
    {
        if (!$this->last_kb_scan_id) {
            return null;
        }
        return $this->getKbScanPath($this->last_kb_scan_id);
    }

    /**
     * Get the scan_meta.json path for a scan.
     */
    public function getKbScanMetaPath(string $scanId): string
    {
        return $this->getKbScanPath($scanId) . '/scan_meta.json';
    }

    /**
     * Get the files_index path for a scan (json or ndjson).
     */
    public function getKbFilesIndexPath(string $scanId): string
    {
        $basePath = $this->getKbScanPath($scanId);
        if (file_exists($basePath . '/files_index.ndjson')) {
            return $basePath . '/files_index.ndjson';
        }
        return $basePath . '/files_index.json';
    }

    /**
     * Get the chunks.ndjson path for a scan.
     */
    public function getKbChunksPath(string $scanId): string
    {
        return $this->getKbScanPath($scanId) . '/chunks.ndjson';
    }

    /**
     * Get the directory_stats.json path for a scan.
     */
    public function getKbDirectoryStatsPath(string $scanId): string
    {
        return $this->getKbScanPath($scanId) . '/directory_stats.json';
    }

    /**
     * List all available scan IDs for this project.
     */
    public function listKbScans(): array
    {
        $basePath = $this->kb_base_path;
        if (!is_dir($basePath)) {
            return [];
        }

        $scans = [];
        foreach (scandir($basePath) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (is_dir($basePath . '/' . $entry) && str_starts_with($entry, 'scan_')) {
                $metaPath = $basePath . '/' . $entry . '/scan_meta.json';
                if (file_exists($metaPath)) {
                    $meta = json_decode(file_get_contents($metaPath), true);
                    $scans[] = [
                        'scan_id' => $entry,
                        'scanned_at' => $meta['scanned_at_iso'] ?? null,
                        'head_commit_sha' => $meta['head_commit_sha'] ?? null,
                        'total_chunks' => $meta['stats']['total_chunks'] ?? 0,
                    ];
                }
            }
        }

        usort($scans, fn($a, $b) => ($b['scanned_at'] ?? '') <=> ($a['scanned_at'] ?? ''));

        return $scans;
    }

    // -------------------------------------------------------------------------
    // Repository Accessors
    // -------------------------------------------------------------------------

    public function getOwnerAttribute(): string
    {
        return explode('/', $this->repo_full_name)[0] ?? '';
    }

    public function getRepoNameAttribute(): string
    {
        return explode('/', $this->repo_full_name)[1] ?? $this->repo_full_name;
    }

    public function getActiveBranchAttribute(): string
    {
        return $this->selected_branch ?? $this->default_branch;
    }

    // -------------------------------------------------------------------------
    // Status Checks
    // -------------------------------------------------------------------------

    public function isScanning(): bool
    {
        return $this->status === 'scanning';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function needsMigration(): bool
    {
        return $this->scan_output_version === null || version_compare($this->scan_output_version, '2.1.0', '<');
    }

    public function hasLocalRepo(): bool
    {
        return is_dir($this->repo_path . '/.git');
    }

    // -------------------------------------------------------------------------
    // Status Updates
    // -------------------------------------------------------------------------

    public function markScanning(): void
    {
        $this->update([
            'status' => 'scanning',
            'last_error' => null,
        ]);
    }

    public function markReady(string $commitSha): void
    {
        $this->update([
            'status' => 'ready',
            'last_commit_sha' => $commitSha,
            'scanned_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
        ]);
    }

    public function updateProgress(string $stage, int $percent): void
    {
        $this->update([
            'current_stage' => $stage,
            'stage_percent' => $percent,
        ]);
    }

    public function updateStats(int $totalFiles, int $totalLines, int $totalBytes): void
    {
        $this->update([
            'total_files' => $totalFiles,
            'total_lines' => $totalLines,
            'total_size_bytes' => $totalBytes,
        ]);
    }

    public function updateStackInfo(array $stack): void
    {
        $this->update(['stack_info' => $stack]);
    }

    // -------------------------------------------------------------------------
    // URL Helpers
    // -------------------------------------------------------------------------

    public function getGitCloneUrl(): string
    {
        return 'https://github.com/' . $this->repo_full_name . '.git';
    }

    public function getGitHubUrl(): string
    {
        return 'https://github.com/' . $this->repo_full_name;
    }

    public function getGitHubFileUrl(string $path, ?int $line = null): string
    {
        $url = $this->getGitHubUrl() . '/blob/' . $this->active_branch . '/' . $path;

        if ($line !== null) {
            $url .= '#L' . $line;
        }

        return $url;
    }

    // -------------------------------------------------------------------------
    // File/Chunk Queries
    // -------------------------------------------------------------------------

    public function getIncludedFiles()
    {
        return $this->files()->where('is_excluded', false);
    }

    public function getExcludedFiles()
    {
        return $this->files()->where('is_excluded', true);
    }

    public function getFilesByLanguage(string $language)
    {
        return $this->files()->where('language', $language)->where('is_excluded', false);
    }

    public function getChunksForFile(string $path)
    {
        return $this->chunks()->where('path', $path)->orderBy('start_line');
    }

    public function findChunkById(string $chunkId): ?ProjectFileChunk
    {
        return $this->chunks()->where('chunk_id', $chunkId)->first();
    }

    // -------------------------------------------------------------------------
    // Cleanup
    // -------------------------------------------------------------------------

    public function cleanupStorage(): void
    {
        $path = $this->storage_path;
        if (is_dir($path)) {
            $this->recursiveDelete($path);
        }
    }

    /**
     * Clean up old KB scans, keeping only the latest N.
     */
    public function cleanupOldKbScans(int $keep = 3): void
    {
        $scans = $this->listKbScans();

        if (count($scans) <= $keep) {
            return;
        }

        $toDelete = array_slice($scans, $keep);
        foreach ($toDelete as $scan) {
            $scanPath = $this->getKbScanPath($scan['scan_id']);
            if (is_dir($scanPath)) {
                $this->recursiveDelete($scanPath);
            }
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $path = $dir . '/' . $object;
                    if (is_dir($path)) {
                        $this->recursiveDelete($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
