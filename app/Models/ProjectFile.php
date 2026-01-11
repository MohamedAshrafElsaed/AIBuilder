<?php

namespace App\Models;

use Database\Factories\ProjectFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFile extends Model
{
    /** @use HasFactory<ProjectFileFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'file_id',
        'path',
        'extension',
        'language',
        'size_bytes',
        'sha1',
        'line_count',
        'is_binary',
        'is_excluded',
        'exclusion_reason',
        'mime_type',
        'framework_hints',
        'symbols_declared',
        'imports',
        'file_modified_at',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'string',
            'is_binary' => 'boolean',
            'is_excluded' => 'boolean',
            'framework_hints' => 'array',
            'symbols_declared' => 'array',
            'imports' => 'array',
            'file_modified_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ProjectFileChunk::class, 'path', 'path')
            ->where('project_id', $this->project_id);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getDirectoryAttribute(): string
    {
        $dir = dirname($this->path);
        return $dir === '.' ? '(root)' : $dir;
    }

    public function getFilenameAttribute(): string
    {
        return basename($this->path);
    }

    public function getChunkCountAttribute(): int
    {
        return $this->chunks()->count();
    }

    public function getChunkIdsAttribute(): array
    {
        return $this->chunks()->orderBy('start_line')->pluck('chunk_id')->toArray();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeIncluded($query)
    {
        return $query->where('is_excluded', false);
    }

    public function scopeExcluded($query)
    {
        return $query->where('is_excluded', true);
    }

    public function scopeBinary($query)
    {
        return $query->where('is_binary', true);
    }

    public function scopeNonBinary($query)
    {
        return $query->where('is_binary', false);
    }

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function scopeByExtension($query, string $extension)
    {
        return $query->where('extension', $extension);
    }

    public function scopeInDirectory($query, string $directory)
    {
        return $query->where('path', 'like', $directory . '/%');
    }

    public function scopeWithFrameworkHint($query, string $hint)
    {
        return $query->whereJsonContains('framework_hints', $hint);
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    public function hasFrameworkHint(string $hint): bool
    {
        return in_array($hint, $this->framework_hints ?? [], true);
    }

    public function isChunked(): bool
    {
        return $this->chunks()->count() > 0;
    }

    public function getFullPath(Project $project): string
    {
        return $project->repo_path . '/' . $this->path;
    }

    public function getContent(Project $project): ?string
    {
        $fullPath = $this->getFullPath($project);

        if (!file_exists($fullPath)) {
            return null;
        }

        return @file_get_contents($fullPath);
    }

    public static function generateFileId(string $path): string
    {
        return 'f_' . substr(sha1($path), 0, 12);
    }
}
