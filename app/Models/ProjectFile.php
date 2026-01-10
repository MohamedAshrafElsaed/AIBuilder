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
        'path',
        'extension',
        'size_bytes',
        'sha1',
        'line_count',
        'is_binary',
        'mime_type',
        'file_modified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_binary' => 'boolean',
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

    public function getDirectoryAttribute(): string
    {
        return dirname($this->path) ?: '.';
    }

    public function getFilenameAttribute(): string
    {
        return basename($this->path);
    }
}
