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
        'path',
        'start_line',
        'end_line',
        'sha1',
        'chunk_file_path',
        'chunk_size_bytes',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class, 'path', 'path')
            ->where('project_id', $this->project_id);
    }
}
