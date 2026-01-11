<?php

namespace App\Models;

use App\Enums\ComplexityLevel;
use App\Enums\IntentType;
use Database\Factories\IntentAnalysisFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $project_id
 * @property string $conversation_id
 * @property string $message_id
 * @property string $raw_input
 * @property IntentType $intent_type
 * @property float $confidence_score
 * @property array|null $extracted_entities
 * @property array|null $domain_classification
 * @property ComplexityLevel $complexity_estimate
 * @property bool $requires_clarification
 * @property array|null $clarification_questions
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @property-read string $primary_domain
 * @property-read array $secondary_domains
 * @property-read array $mentioned_files
 * @property-read array $mentioned_components
 */
class IntentAnalysis extends Model
{
    /** @use HasFactory<IntentAnalysisFactory> */
    use HasFactory, HasUuids;

    protected $table = 'intent_analyses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'conversation_id',
        'message_id',
        'raw_input',
        'intent_type',
        'confidence_score',
        'extracted_entities',
        'domain_classification',
        'complexity_estimate',
        'requires_clarification',
        'clarification_questions',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'project_id' => 'string',
            'conversation_id' => 'string',
            'message_id' => 'string',
            'intent_type' => IntentType::class,
            'confidence_score' => 'float',
            'extracted_entities' => 'array',
            'domain_classification' => 'array',
            'complexity_estimate' => ComplexityLevel::class,
            'requires_clarification' => 'boolean',
            'clarification_questions' => 'array',
            'metadata' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getPrimaryDomainAttribute(): string
    {
        return $this->domain_classification['primary'] ?? 'general';
    }

    /**
     * @return array<string>
     */
    public function getSecondaryDomainsAttribute(): array
    {
        return $this->domain_classification['secondary'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getMentionedFilesAttribute(): array
    {
        return $this->extracted_entities['files'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getMentionedComponentsAttribute(): array
    {
        return $this->extracted_entities['components'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getMentionedFeaturesAttribute(): array
    {
        return $this->extracted_entities['features'] ?? [];
    }

    public function getProcessingTimeAttribute(): ?float
    {
        return $this->metadata['processing_time_ms'] ?? null;
    }

    public function getTokensUsedAttribute(): ?int
    {
        return $this->metadata['tokens_used'] ?? null;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeForConversation(Builder $query, string $conversationId): Builder
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeOfType(Builder $query, IntentType $type): Builder
    {
        return $query->where('intent_type', $type->value);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @param array<IntentType> $types
     * @return Builder<IntentAnalysis>
     */
    public function scopeOfTypes(Builder $query, array $types): Builder
    {
        $values = array_map(fn(IntentType $t) => $t->value, $types);
        return $query->whereIn('intent_type', $values);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeWithComplexity(Builder $query, ComplexityLevel $complexity): Builder
    {
        return $query->where('complexity_estimate', $complexity->value);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeHighConfidence(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeLowConfidence(Builder $query, float $threshold = 0.5): Builder
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeNeedingClarification(Builder $query): Builder
    {
        return $query->where('requires_clarification', true);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeRequiresCodeChanges(Builder $query): Builder
    {
        $codeChangeTypes = array_filter(
            IntentType::cases(),
            fn(IntentType $t) => $t->requiresCodeChanges()
        );

        return $query->ofTypes($codeChangeTypes);
    }

    /**
     * @param Builder<IntentAnalysis> $query
     * @return Builder<IntentAnalysis>
     */
    public function scopeInDomain(Builder $query, string $domain): Builder
    {
        return $query->where(function (Builder $q) use ($domain) {
            $q->whereJsonContains('domain_classification->primary', $domain)
                ->orWhereJsonContains('domain_classification->secondary', $domain);
        });
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    public function isHighConfidence(float $threshold = 0.8): bool
    {
        return $this->confidence_score >= $threshold;
    }

    public function isLowConfidence(float $threshold = 0.5): bool
    {
        return $this->confidence_score < $threshold;
    }

    public function doesRequireCodeChanges(): bool
    {
        return $this->intent_type->requiresCodeChanges();
    }

    public function hasMentionedFiles(): bool
    {
        return !empty($this->mentioned_files);
    }

    public function hasMentionedComponents(): bool
    {
        return !empty($this->mentioned_components);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'intent_type' => $this->intent_type->value,
            'intent_label' => $this->intent_type->label(),
            'confidence' => $this->confidence_score,
            'complexity' => $this->complexity_estimate->value,
            'complexity_label' => $this->complexity_estimate->label(),
            'primary_domain' => $this->primary_domain,
            'requires_clarification' => $this->requires_clarification,
            'requires_code_changes' => $this->doesRequireCodeChanges(),
        ];
    }
}
