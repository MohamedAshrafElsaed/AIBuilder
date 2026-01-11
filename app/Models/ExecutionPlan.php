<?php

namespace App\Models;

use App\DTOs\FileOperation;
use App\DTOs\RiskAssessment;
use App\Enums\ComplexityLevel;
use App\Enums\PlanStatus;
use Database\Factories\ExecutionPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @property string $id
 * @property string $project_id
 * @property string $conversation_id
 * @property string|null $intent_analysis_id
 * @property PlanStatus $status
 * @property string $title
 * @property string $description
 * @property array $plan_data
 * @property array $file_operations
 * @property ComplexityLevel $estimated_complexity
 * @property int $estimated_files_affected
 * @property array|null $risks
 * @property array|null $prerequisites
 * @property string|null $user_feedback
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $execution_started_at
 * @property \Illuminate\Support\Carbon|null $execution_completed_at
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @property-read IntentAnalysis|null $intentAnalysis
 * @property-read User|null $approver
 */
class ExecutionPlan extends Model
{
    /** @use HasFactory<ExecutionPlanFactory> */
    use HasFactory, HasUuids;

    protected $table = 'execution_plans';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'conversation_id',
        'intent_analysis_id',
        'status',
        'title',
        'description',
        'plan_data',
        'file_operations',
        'estimated_complexity',
        'estimated_files_affected',
        'risks',
        'prerequisites',
        'user_feedback',
        'approved_at',
        'approved_by',
        'execution_started_at',
        'execution_completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlanStatus::class,
            'plan_data' => 'array',
            'file_operations' => 'array',
            'estimated_complexity' => ComplexityLevel::class,
            'estimated_files_affected' => 'integer',
            'risks' => 'array',
            'prerequisites' => 'array',
            'metadata' => 'array',
            'approved_at' => 'datetime',
            'execution_started_at' => 'datetime',
            'execution_completed_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function intentAnalysis(): BelongsTo
    {
        return $this->belongsTo(IntentAnalysis::class, 'intent_analysis_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * @param Builder<ExecutionPlan> $query
     * @return Builder<ExecutionPlan>
     */
    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', PlanStatus::PendingReview);
    }

    /**
     * @param Builder<ExecutionPlan> $query
     * @return Builder<ExecutionPlan>
     */
    public function scopeForProject(Builder $query, string $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * @param Builder<ExecutionPlan> $query
     * @return Builder<ExecutionPlan>
     */
    public function scopeForConversation(Builder $query, string $conversationId): Builder
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * @param Builder<ExecutionPlan> $query
     * @return Builder<ExecutionPlan>
     */
    public function scopeWithStatus(Builder $query, PlanStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param Builder<ExecutionPlan> $query
     * @return Builder<ExecutionPlan>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            PlanStatus::Completed->value,
            PlanStatus::Failed->value,
            PlanStatus::Rejected->value,
        ]);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get file operations as DTOs.
     *
     * @return Collection<int, FileOperation>
     */
    public function getFileOperationsDtosAttribute(): Collection
    {
        $operations = $this->file_operations ?? [];

        return collect($operations)->map(fn($op) => FileOperation::fromArray($op));
    }

    /**
     * Get total files affected.
     */
    public function getTotalFilesAttribute(): int
    {
        return count($this->file_operations ?? []);
    }

    /**
     * Get risk assessment DTO.
     */
    public function getRiskAssessmentAttribute(): RiskAssessment
    {
        return RiskAssessment::calculate(
            $this->risks ?? [],
            $this->prerequisites ?? [],
            $this->plan_data['manual_steps'] ?? []
        );
    }

    /**
     * Get execution duration in seconds.
     */
    public function getExecutionDurationAttribute(): ?int
    {
        if (!$this->execution_started_at || !$this->execution_completed_at) {
            return null;
        }

        return $this->execution_completed_at->diffInSeconds($this->execution_started_at);
    }

    /**
     * Check if plan is modifiable.
     */
    public function getIsModifiableAttribute(): bool
    {
        return $this->status->isModifiable();
    }

    /**
     * Check if plan can be executed.
     */
    public function getCanExecuteAttribute(): bool
    {
        return $this->status->canExecute();
    }

    // =========================================================================
    // Status Transition Methods
    // =========================================================================

    /**
     * Approve the plan for execution.
     *
     * @throws InvalidArgumentException
     */
    public function approve(?int $userId = null): void
    {
        $this->transitionTo(PlanStatus::Approved);
        $this->update([
            'approved_at' => now(),
            'approved_by' => $userId,
        ]);
    }

    /**
     * Reject the plan.
     *
     * @throws InvalidArgumentException
     */
    public function reject(string $reason): void
    {
        $this->transitionTo(PlanStatus::Rejected);
        $this->update([
            'user_feedback' => $reason,
            'metadata' => array_merge($this->metadata ?? [], [
                'rejected_at' => now()->toIso8601String(),
                'rejection_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Mark plan as executing.
     *
     * @throws InvalidArgumentException
     */
    public function markExecuting(): void
    {
        $this->transitionTo(PlanStatus::Executing);
        $this->update([
            'execution_started_at' => now(),
        ]);
    }

    /**
     * Mark plan as completed.
     *
     * @throws InvalidArgumentException
     */
    public function markCompleted(): void
    {
        $this->transitionTo(PlanStatus::Completed);
        $this->update([
            'execution_completed_at' => now(),
        ]);
    }

    /**
     * Mark plan as failed.
     *
     * @throws InvalidArgumentException
     */
    public function markFailed(string $error): void
    {
        $this->transitionTo(PlanStatus::Failed);
        $this->update([
            'execution_completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'failure_reason' => $error,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Submit for review.
     *
     * @throws InvalidArgumentException
     */
    public function submitForReview(): void
    {
        $this->transitionTo(PlanStatus::PendingReview);
    }

    /**
     * Revert to draft for editing.
     *
     * @throws InvalidArgumentException
     */
    public function revertToDraft(): void
    {
        $this->transitionTo(PlanStatus::Draft);
    }

    /**
     * Transition to a new status with validation.
     *
     * @throws InvalidArgumentException
     */
    private function transitionTo(PlanStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$this->status->value}' to '{$newStatus->value}'"
            );
        }

        $this->update(['status' => $newStatus]);
    }

    // =========================================================================
    // Other Methods
    // =========================================================================

    /**
     * Add user feedback for plan refinement.
     */
    public function addUserFeedback(string $feedback): void
    {
        $existingFeedback = $this->user_feedback ?? '';
        $separator = $existingFeedback ? "\n---\n" : '';

        $this->update([
            'user_feedback' => $existingFeedback . $separator . $feedback,
        ]);
    }

    /**
     * Get files grouped by operation type.
     *
     * @return array<string, array<string>>
     */
    public function getFilesByOperationType(): array
    {
        $grouped = [];

        foreach ($this->file_operations ?? [] as $op) {
            $type = $op['type'] ?? 'unknown';
            $grouped[$type][] = $op['path'];
        }

        return $grouped;
    }

    /**
     * Get files in execution order (sorted by priority).
     *
     * @return array<array<string, mixed>>
     */
    public function getOrderedFileOperations(): array
    {
        $operations = $this->file_operations ?? [];

        usort($operations, fn($a, $b) => ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999));

        return $operations;
    }

    /**
     * Check if a specific file is affected.
     */
    public function affectsFile(string $path): bool
    {
        foreach ($this->file_operations ?? [] as $op) {
            if ($op['path'] === $path || ($op['new_path'] ?? null) === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a short summary for display.
     */
    public function getSummary(): string
    {
        $ops = $this->getFilesByOperationType();
        $parts = [];

        foreach ($ops as $type => $files) {
            $count = count($files);
            $parts[] = "{$count} " . ($count === 1 ? $type : "{$type}s");
        }

        return implode(', ', $parts) ?: 'No file operations';
    }
}
