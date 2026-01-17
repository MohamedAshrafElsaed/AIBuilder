<?php

namespace App\Services;

use App\DataTransferObjects\FacebookEventData;
use App\DataTransferObjects\FacebookUserData;
use App\Enums\FacebookEventType;
use InvalidArgumentException;

/**
 * Facebook Event Builder Service
 *
 * Provides a fluent interface for constructing FacebookEventData objects
 * with event properties, user data, and custom parameters.
 */
class FacebookEventBuilder
{
    private FacebookEventType $eventType;

    private ?string $eventId = null;

    private ?int $eventTime = null;

    private ?string $eventSourceUrl = null;

    private ?string $actionSource = null;

    private ?FacebookUserData $userData = null;

    private array $customData = [];

    private bool $testEvent = false;

    /**
     * Create a new Facebook Event Builder instance.
     */
    public function __construct(FacebookEventType $eventType)
    {
        $this->eventType = $eventType;
        $this->eventTime = time();
        $this->actionSource = 'website';
    }

    /**
     * Create a new builder instance for the given event type.
     */
    public static function make(FacebookEventType $eventType): self
    {
        return new self($eventType);
    }

    /**
     * Set the event ID.
     */
    public function eventId(string $eventId): self
    {
        $this->eventId = $eventId;

        return $this;
    }

    /**
     * Set the event time.
     */
    public function eventTime(int $eventTime): self
    {
        if ($eventTime <= 0) {
            throw new InvalidArgumentException('Event time must be a positive integer.');
        }

        $this->eventTime = $eventTime;

        return $this;
    }

    /**
     * Set the event source URL.
     */
    public function eventSourceUrl(string $url): self
    {
        $this->eventSourceUrl = $url;

        return $this;
    }

    /**
     * Set the action source.
     */
    public function actionSource(string $actionSource): self
    {
        $validSources = ['website', 'app', 'email', 'phone_call', 'chat', 'physical_store', 'system_generated', 'other'];

        if (! in_array($actionSource, $validSources)) {
            throw new InvalidArgumentException("Invalid action source. Must be one of: " . implode(', ', $validSources));
        }

        $this->actionSource = $actionSource;

        return $this;
    }

    /**
     * Set the user data.
     */
    public function userData(FacebookUserData $userData): self
    {
        $this->userData = $userData;

        return $this;
    }

    /**
     * Set user data from array.
     */
    public function userDataFromArray(array $data): self
    {
        $this->userData = FacebookUserData::from($data);

        return $this;
    }

    /**
     * Set a custom data parameter.
     */
    public function customData(string $key, mixed $value): self
    {
        $this->customData[$key] = $value;

        return $this;
    }

    /**
     * Set multiple custom data parameters.
     */
    public function customDataArray(array $data): self
    {
        $this->customData = array_merge($this->customData, $data);

        return $this;
    }

    /**
     * Set the currency for the event.
     */
    public function currency(string $currency): self
    {
        $this->customData['currency'] = strtoupper($currency);

        return $this;
    }

    /**
     * Set the value for the event.
     */
    public function value(float $value): self
    {
        $this->customData['value'] = $value;

        return $this;
    }

    /**
     * Set the content name.
     */
    public function contentName(string $contentName): self
    {
        $this->customData['content_name'] = $contentName;

        return $this;
    }

    /**
     * Set the content category.
     */
    public function contentCategory(string $contentCategory): self
    {
        $this->customData['content_category'] = $contentCategory;

        return $this;
    }

    /**
     * Set the content IDs.
     */
    public function contentIds(array $contentIds): self
    {
        $this->customData['content_ids'] = $contentIds;

        return $this;
    }

    /**
     * Set the content type.
     */
    public function contentType(string $contentType): self
    {
        $this->customData['content_type'] = $contentType;

        return $this;
    }

    /**
     * Set the number of items.
     */
    public function numItems(int $numItems): self
    {
        $this->customData['num_items'] = $numItems;

        return $this;
    }

    /**
     * Set the search string.
     */
    public function searchString(string $searchString): self
    {
        $this->customData['search_string'] = $searchString;

        return $this;
    }

    /**
     * Set the status.
     */
    public function status(string $status): self
    {
        $this->customData['status'] = $status;

        return $this;
    }

    /**
     * Mark this as a test event.
     */
    public function asTestEvent(bool $testEvent = true): self
    {
        $this->testEvent = $testEvent;

        return $this;
    }

    /**
     * Build and return the FacebookEventData object.
     */
    public function build(): FacebookEventData
    {
        if ($this->userData === null) {
            throw new InvalidArgumentException('User data is required. Call userData() or userDataFromArray() before building.');
        }

        return FacebookEventData::from([
            'event_name' => $this->eventType,
            'event_id' => $this->eventId ?? $this->generateEventId(),
            'event_time' => $this->eventTime,
            'event_source_url' => $this->eventSourceUrl,
            'action_source' => $this->actionSource,
            'user_data' => $this->userData,
            'custom_data' => $this->customData,
            'test_event' => $this->testEvent,
        ]);
    }

    /**
     * Generate a unique event ID.
     */
    private function generateEventId(): string
    {
        return sprintf(
            '%s_%s_%s',
            $this->eventType->value,
            time(),
            bin2hex(random_bytes(8))
        );
    }

    /**
     * Reset the builder to its initial state.
     */
    public function reset(): self
    {
        $this->eventId = null;
        $this->eventTime = time();
        $this->eventSourceUrl = null;
        $this->actionSource = 'website';
        $this->userData = null;
        $this->customData = [];
        $this->testEvent = false;

        return $this;
    }
}
