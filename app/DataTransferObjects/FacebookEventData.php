<?php

namespace App\DataTransferObjects;

use App\Enums\FacebookEventType;
use InvalidArgumentException;

final readonly class FacebookEventData
{
    /**
     * Create a new Facebook Event Data instance.
     *
     * @param  FacebookEventType  $eventName  The event type
     * @param  int  $eventTime  Unix timestamp in seconds
     * @param  array<string, mixed>  $userData  User data parameters (email, phone, etc.)
     * @param  array<string, mixed>  $customData  Custom data parameters specific to the event
     * @param  string  $eventSourceUrl  The URL where the event occurred
     * @param  string  $actionSource  The source of the action (e.g., 'website', 'app')
     * @param  string|null  $eventId  Unique event identifier for deduplication
     * @param  float|null  $value  Value associated with the event
     * @param  string|null  $currency  Currency for the value (ISO 4217 format)
     * @param  bool  $testEvent  Whether this is a test event
     * @param  bool  $optOut  Whether the user has opted out of tracking
     */
    public function __construct(
        public FacebookEventType $eventName,
        public int $eventTime,
        public array $userData,
        public array $customData,
        public string $eventSourceUrl,
        public string $actionSource = 'website',
        public ?string $eventId = null,
        public ?float $value = null,
        public ?string $currency = null,
        public bool $testEvent = false,
        public bool $optOut = false,
    ) {
        $this->validate();
    }

    /**
     * Create a new instance from an array.
     *
     * @param  array<string, mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $eventNameValue = $data['event_name'] ?? $data['eventName'] ?? null;

        if ($eventNameValue instanceof FacebookEventType) {
            $eventName = $eventNameValue;
        } elseif (is_string($eventNameValue)) {
            $eventName = FacebookEventType::from($eventNameValue);
        } else {
            $eventName = FacebookEventType::PageView;
        }

        return new self(
            eventName: $eventName,
            eventTime: $data['event_time'] ?? $data['eventTime'] ?? time(),
            userData: $data['user_data'] ?? $data['userData'] ?? [],
            customData: $data['custom_data'] ?? $data['customData'] ?? [],
            eventSourceUrl: $data['event_source_url'] ?? $data['eventSourceUrl'] ?? '',
            actionSource: $data['action_source'] ?? $data['actionSource'] ?? 'website',
            eventId: $data['event_id'] ?? $data['eventId'] ?? null,
            value: isset($data['value']) ? (float) $data['value'] : null,
            currency: $data['currency'] ?? null,
            testEvent: $data['test_event'] ?? $data['testEvent'] ?? false,
            optOut: $data['opt_out'] ?? $data['optOut'] ?? false,
        );
    }

    /**
     * Create a new instance with event type enum.
     *
     * @param  FacebookEventType  $eventType
     * @param  int  $eventTime
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     * @param  string  $eventSourceUrl
     * @param  string  $actionSource
     * @param  string|null  $eventId
     * @param  float|null  $value
     * @param  string|null  $currency
     * @param  bool  $testEvent
     * @param  bool  $optOut
     * @return self
     */
    public static function fromEventType(
        FacebookEventType $eventType,
        int $eventTime,
        array $userData,
        array $customData,
        string $eventSourceUrl,
        string $actionSource = 'website',
        ?string $eventId = null,
        ?float $value = null,
        ?string $currency = null,
        bool $testEvent = false,
        bool $optOut = false
    ): self {
        return new self(
            eventName: $eventType,
            eventTime: $eventTime,
            userData: $userData,
            customData: $customData,
            eventSourceUrl: $eventSourceUrl,
            actionSource: $actionSource,
            eventId: $eventId,
            value: $value,
            currency: $currency,
            testEvent: $testEvent,
            optOut: $optOut,
        );
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'event_name' => $this->eventName->value,
            'event_time' => $this->eventTime,
            'user_data' => $this->userData,
            'custom_data' => $this->customData,
            'event_source_url' => $this->eventSourceUrl,
            'action_source' => $this->actionSource,
        ];

        if ($this->eventId !== null) {
            $data['event_id'] = $this->eventId;
        }

        if ($this->value !== null) {
            $data['value'] = $this->value;
        }

        if ($this->currency !== null) {
            $data['currency'] = $this->currency;
        }

        if ($this->testEvent) {
            $data['test_event'] = $this->testEvent;
        }

        if ($this->optOut) {
            $data['opt_out'] = $this->optOut;
        }

        return $data;
    }

    /**
     * Convert the DTO to Facebook Conversions API format.
     *
     * @return array<string, mixed>
     */
    public function toFacebookFormat(): array
    {
        $data = [
            'event_name' => $this->eventName->value,
            'event_time' => $this->eventTime,
            'user_data' => $this->normalizeUserData(),
            'custom_data' => $this->customData,
            'event_source_url' => $this->eventSourceUrl,
            'action_source' => $this->actionSource,
        ];

        if ($this->eventId !== null) {
            $data['event_id'] = $this->eventId;
        }

        if ($this->value !== null) {
            $data['custom_data']['value'] = $this->value;
        }

        if ($this->currency !== null) {
            $data['custom_data']['currency'] = $this->currency;
        }

        if ($this->optOut) {
            $data['opt_out'] = $this->optOut;
        }

        return $data;
    }

    /**
     * Normalize user data by hashing sensitive information.
     *
     * @return array<string, mixed>
     */
    private function normalizeUserData(): array
    {
        $normalized = [];

        foreach ($this->userData as $key => $value) {
            if (in_array($key, ['em', 'email', 'ph', 'phone'])) {
                $normalized[$key] = $this->hashValue($value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Hash a value using SHA256.
     *
     * @param  string|null  $value
     * @return string|null
     */
    private function hashValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return hash('sha256', strtolower(trim($value)));
    }

    /**
     * Validate the DTO data.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if ($this->eventTime <= 0) {
            throw new InvalidArgumentException('Event time must be a valid Unix timestamp.');
        }

        if (empty($this->eventSourceUrl)) {
            throw new InvalidArgumentException('Event source URL is required.');
        }

        if (!filter_var($this->eventSourceUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Event source URL must be a valid URL.');
        }

        $validActionSources = ['website', 'app', 'email', 'phone_call', 'chat', 'physical_store', 'system_generated', 'other'];
        if (!in_array($this->actionSource, $validActionSources)) {
            throw new InvalidArgumentException(
                sprintf('Action source must be one of: %s', implode(', ', $validActionSources))
            );
        }

        if ($this->value !== null && $this->value < 0) {
            throw new InvalidArgumentException('Value must be a positive number.');
        }

        if ($this->currency !== null && strlen($this->currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a valid ISO 4217 3-letter code.');
        }
    }
}
