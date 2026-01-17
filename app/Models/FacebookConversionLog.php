<?php

namespace App\Models;

use App\Enums\FacebookEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Facebook Conversion Log Model
 *
 * @property int $id
 * @property int|null $user_id
 * @property FacebookEventType $event_type
 * @property array $event_data
 * @property array $user_data
 * @property array|null $custom_data
 * @property string $event_id
 * @property int $event_time
 * @property string|null $response_status
 * @property array|null $response_data
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FacebookConversionLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'event_data',
        'user_data',
        'custom_data',
        'event_id',
        'event_time',
        'response_status',
        'response_data',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => FacebookEventType::class,
            'event_data' => 'array',
            'user_data' => 'array',
            'custom_data' => 'array',
            'response_data' => 'array',
            'event_time' => 'integer',
        ];
    }

    /**
     * Get the user that owns the conversion log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include successful conversions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('response_status', 'success');
    }

    /**
     * Scope a query to only include failed conversions.
     */
    public function scopeFailed($query)
    {
        return $query->where('response_status', 'failed');
    }

    /**
     * Scope a query to filter by event type.
     */
    public function scopeByEventType($query, FacebookEventType $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Check if the conversion was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->response_status === 'success';
    }

    /**
     * Check if the conversion failed.
     */
    public function isFailed(): bool
    {
        return $this->response_status === 'failed';
    }
}
