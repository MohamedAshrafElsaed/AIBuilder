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
    // Path Accessors
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
        return $this->scan_output_version === null || version_compare($this->scan_output_version, '2.0.0', '<');
    }

    public function hasLocalRepo(): bool
    {
        return is_dir($this->repo_path . '/.git');
    }

    // -------------------------------------------------------------------------
    // Status Updates
    // -------------------------------------------------------------------------

    public function markScanning(string $stage = 'workspace', int $percent = 0): void
    {
        $this->update([
            'status' => 'scanning',
            'current_stage' => $stage,
            'stage_percent' => $percent,
            'last_error' => null,
        ]);
    }

    public function updateProgress(string $stage, int $percent): void
    {
        $this->update([
            'current_stage' => $stage,
            'stage_percent' => $percent,
        ]);
    }

    public function markReady(string $commitSha): void
    {
        $this->update([
            'status' => 'ready',
            'current_stage' => null,
            'stage_percent' => 100,
            'scanned_at' => now(),
            'last_commit_sha' => $commitSha,
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

    // -------------------------------------------------------------------------
    // Stats Updates
    // -------------------------------------------------------------------------

    public function updateStats(int $files, int $lines, int $bytes): void
    {
        $this->update([
            'total_files' => $files,
            'total_lines' => $lines,
            'total_size_bytes' => $bytes,
        ]);
    }

    public function updateStackInfo(array $stack): void
    {
        $this->update(['stack_info' => $stack]);
    }

    public function updateScanVersion(string $version, string $rulesVersion): void
    {
        $this->update([
            'scan_output_version' => $version,
            'exclusion_rules_version' => $rulesVersion,
        ]);
    }

    // -------------------------------------------------------------------------
    // GitHub URLs
    // -------------------------------------------------------------------------

    public function getGitHubCloneUrl(): string
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
