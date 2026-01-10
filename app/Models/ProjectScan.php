<?php

namespace App\Models;

use Database\Factories\ProjectScanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectScan extends Model
{
    /** @use HasFactory<ProjectScanFactory> */
    use HasFactory;
    protected $fillable = [
        'project_id',
        'status',
        'current_stage',
        'stage_percent',
        'commit_sha',
        'trigger',
        'started_at',
        'finished_at',
        'last_error',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function markStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'finished_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'finished_at' => now(),
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
}
