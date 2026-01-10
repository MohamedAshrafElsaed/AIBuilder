<?php

namespace App\Models;

use Database\Factories\ProjectFileChunkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFileChunk extends Model
{
    /** @use HasFactory<ProjectFileChunkFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'chunk_id',
        'old_chunk_id',
        'path',
        'start_line',
        'end_line',
        'chunk_index',
        'sha1',
        'chunk_sha1',
        'is_complete_file',
        'chunk_file_path',
        'chunk_size_bytes',
        'symbols_declared',
        'symbols_used',
        'imports',
        'references',
    ];

    protected function casts(): array
    {
        return [
            'is_complete_file' => 'boolean',
            'symbols_declared' => 'array',
            'symbols_used' => 'array',
            'imports' => 'array',
            'references' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class, 'path', 'path')
            ->where('project_id', $this->project_id);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getLineCountAttribute(): int
    {
        return $this->end_line - $this->start_line + 1;
    }

    public function getPathHashAttribute(): string
    {
        return substr(sha1($this->path), 0, 12);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForFile($query, string $path)
    {
        return $query->where('path', $path)->orderBy('start_line');
    }

    public function scopeCompleteFiles($query)
    {
        return $query->where('is_complete_file', true);
    }

    public function scopePartialFiles($query)
    {
        return $query->where('is_complete_file', false);
    }

    public function scopeByChunkId($query, string $chunkId)
    {
        return $query->where('chunk_id', $chunkId);
    }

    public function scopeByOldChunkId($query, string $oldChunkId)
    {
        return $query->where('old_chunk_id', $oldChunkId);
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Get the content of this chunk from the repository
     */
    public function getContent(Project $project): ?string
    {
        $fullPath = $project->repo_path . '/' . $this->path;

        if (!file_exists($fullPath)) {
            return null;
        }

        $content = @file_get_contents($fullPath);
        if ($content === false) {
            return null;
        }

        $lines = explode("\n", $content);
        $chunkLines = array_slice($lines, $this->start_line - 1, $this->end_line - $this->start_line + 1);

        return implode("\n", $chunkLines);
    }

    /**
     * Verify that the stored chunk_sha1 matches the current content
     */
    public function verifySha1(Project $project): bool
    {
        $content = $this->getContent($project);

        if ($content === null) {
            return false;
        }

        return sha1($content) === $this->chunk_sha1;
    }

    /**
     * Generate a stable chunk_id from path and line range
     */
    public static function generateChunkId(string $path, int $startLine, int $endLine): string
    {
        $pathHash = substr(sha1($path), 0, 12);
        return "{$pathHash}:{$startLine}-{$endLine}";
    }

    /**
     * Parse a chunk_id into its components
     */
    public static function parseChunkId(string $chunkId): ?array
    {
        if (preg_match('/^([a-f0-9]{12}):(\d+)-(\d+)$/', $chunkId, $matches)) {
            return [
                'path_hash' => $matches[1],
                'start_line' => (int)$matches[2],
                'end_line' => (int)$matches[3],
            ];
        }

        return null;
    }

    /**
     * Check if this is an old-format chunk_id
     */
    public static function isOldFormat(string $chunkId): bool
    {
        return (bool)preg_match('/^chunk_\d{4}$/', $chunkId);
    }
}

