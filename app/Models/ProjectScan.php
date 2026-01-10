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
        'previous_commit_sha',
        'trigger',
        'scanner_version',
        'is_incremental',
        'files_scanned',
        'files_excluded',
        'chunks_created',
        'total_lines',
        'total_bytes',
        'duration_ms',
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
            'is_incremental' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // -------------------------------------------------------------------------
    // Status Checks
    // -------------------------------------------------------------------------

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // -------------------------------------------------------------------------
    // Status Updates
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Stats
    // -------------------------------------------------------------------------

    public function updateStats(array $stats): void
    {
        $this->update([
            'files_scanned' => $stats['files_scanned'] ?? $this->files_scanned,
            'files_excluded' => $stats['files_excluded'] ?? $this->files_excluded,
            'chunks_created' => $stats['chunks_created'] ?? $this->chunks_created,
            'total_lines' => $stats['total_lines'] ?? $this->total_lines,
            'total_bytes' => $stats['total_bytes'] ?? $this->total_bytes,
            'duration_ms' => $stats['duration_ms'] ?? $this->duration_ms,
        ]);
    }

    public function getDurationSecondsAttribute(): ?float
    {
        return $this->duration_ms ? $this->duration_ms / 1000 : null;
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (!$this->duration_ms) {
            return null;
        }

        $seconds = $this->duration_ms / 1000;

        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = round($seconds % 60);

        return "{$minutes}m {$remainingSeconds}s";
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeIncremental($query)
    {
        return $query->where('is_incremental', true);
    }

    public function scopeFull($query)
    {
        return $query->where('is_incremental', false);
    }

    public function scopeByTrigger($query, string $trigger)
    {
        return $query->where('trigger', $trigger);
    }
}
