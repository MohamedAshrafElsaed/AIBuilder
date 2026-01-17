<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use InvalidArgumentException;

/**
 * Data Transfer Object for Facebook Conversion API events.
 *
 * Handles the structure and formatting of event data sent to Facebook's
 * Conversion API, including proper hashing of user data according to
 * Facebook's requirements.
 */
class FacebookEventData
{
    /**
     * Create a new FacebookEventData instance.
     *
     * @param string $event_name The name of the event (e.g., 'Purchase', 'Lead')
     * @param int $event_time Unix timestamp when the event occurred
     * @param string|null $event_id Unique identifier for the event (for deduplication)
     * @param string|null $event_source_url The URL where the event occurred
     * @param array $user_data User information (email, phone, first_name, last_name, city, state, zip, country, external_id, client_ip_address, client_user_agent, fbc, fbp)
     * @param array $custom_data Custom event data (value, currency, content_name, content_category, content_ids, contents, num_items)
     * @param string $action_source The source of the action (default: 'website')
     */
    public function __construct(
        public readonly string $event_name,
        public readonly int $event_time,
        public readonly ?string $event_id = null,
        public readonly ?string $event_source_url = null,
        public readonly array $user_data = [],
        public readonly array $custom_data = [],
        public readonly string $action_source = 'website'
    ) {
        $this->validateEventName();
        $this->validateEventTime();
        $this->validateActionSource();
    }

    /**
     * Convert the DTO to an array suitable for Facebook Conversion API.
     *
     * Hashes sensitive user data according to Facebook's requirements.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'event_name' => $this->event_name,
            'event_time' => $this->event_time,
            'action_source' => $this->action_source,
        ];

        if ($this->event_id !== null) {
            $data['event_id'] = $this->event_id;
        }

        if ($this->event_source_url !== null) {
            $data['event_source_url'] = $this->event_source_url;
        }

        if (!empty($this->user_data)) {
            $data['user_data'] = $this->formatUserData();
        }

        if (!empty($this->custom_data)) {
            $data['custom_data'] = $this->formatCustomData();
        }

        return $data;
    }

    /**
     * Format and hash user data according to Facebook's requirements.
     *
     * @return array
     */
    protected function formatUserData(): array
    {
        $formatted = [];

        // Fields that need to be hashed
        $hashableFields = ['email', 'phone', 'first_name', 'last_name', 'city', 'state', 'zip', 'country', 'external_id'];

        foreach ($hashableFields as $field) {
            if (isset($this->user_data[$field]) && $this->user_data[$field] !== null && $this->user_data[$field] !== '') {
                $formatted[$field] = $this->hashValue($this->user_data[$field], $field);
            }
        }

        // Fields that should NOT be hashed
        $nonHashableFields = ['client_ip_address', 'client_user_agent', 'fbc', 'fbp'];

        foreach ($nonHashableFields as $field) {
            if (isset($this->user_data[$field]) && $this->user_data[$field] !== null && $this->user_data[$field] !== '') {
                $formatted[$field] = $this->user_data[$field];
            }
        }

        return $formatted;
    }

    /**
     * Format custom data for the event.
     *
     * @return array
     */
    protected function formatCustomData(): array
    {
        $formatted = [];

        $allowedFields = ['value', 'currency', 'content_name', 'content_category', 'content_ids', 'contents', 'num_items'];

        foreach ($allowedFields as $field) {
            if (isset($this->custom_data[$field]) && $this->custom_data[$field] !== null) {
                // Convert value to float if present
                if ($field === 'value' && is_numeric($this->custom_data[$field])) {
                    $formatted[$field] = (float) $this->custom_data[$field];
                } elseif ($field === 'num_items' && is_numeric($this->custom_data[$field])) {
                    $formatted[$field] = (int) $this->custom_data[$field];
                } else {
                    $formatted[$field] = $this->custom_data[$field];
                }
            }
        }

        return $formatted;
    }

    /**
     * Hash a value according to Facebook's requirements.
     *
     * @param string $value The value to hash
     * @param string $field The field name (for field-specific normalization)
     * @return string The hashed value
     */
    protected function hashValue(string $value, string $field): string
    {
        // Normalize the value before hashing
        $normalized = $this->normalizeValue($value, $field);

        // Hash using SHA-256
        return hash('sha256', $normalized);
    }

    /**
     * Normalize a value according to Facebook's requirements.
     *
     * @param string $value The value to normalize
     * @param string $field The field name
     * @return string The normalized value
     */
    protected function normalizeValue(string $value, string $field): string
    {
        // Trim whitespace
        $normalized = trim($value);

        // Convert to lowercase
        $normalized = strtolower($normalized);

        // Field-specific normalization
        switch ($field) {
            case 'email':
                // Remove all whitespace
                $normalized = preg_replace('/\s+/', '', $normalized);
                break;

            case 'phone':
                // Remove all non-numeric characters
                $normalized = preg_replace('/[^0-9]/', '', $normalized);
                break;

            case 'first_name':
            case 'last_name':
            case 'city':
                // Remove all whitespace and non-alphabetic characters
                $normalized = preg_replace('/[^a-z]/', '', $normalized);
                break;

            case 'state':
            case 'country':
                // Use ISO codes - just remove whitespace and special chars
                $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);
                break;

            case 'zip':
                // Remove whitespace and dashes
                $normalized = preg_replace('/[\s-]/', '', $normalized);
                break;
        }

        return $normalized;
    }

    /**
     * Validate the event name.
     *
     * @throws InvalidArgumentException
     */
    protected function validateEventName(): void
    {
        if (empty($this->event_name)) {
            throw new InvalidArgumentException('Event name cannot be empty.');
        }
    }

    /**
     * Validate the event time.
     *
     * @throws InvalidArgumentException
     */
    protected function validateEventTime(): void
    {
        if ($this->event_time <= 0) {
            throw new InvalidArgumentException('Event time must be a valid Unix timestamp.');
        }

        // Facebook requires events to be within the last 7 days
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        if ($this->event_time < $sevenDaysAgo) {
            throw new InvalidArgumentException('Event time cannot be older than 7 days.');
        }

        // Event time cannot be in the future (with 5 minute buffer for clock skew)
        $futureBuffer = time() + (5 * 60);
        if ($this->event_time > $futureBuffer) {
            throw new InvalidArgumentException('Event time cannot be in the future.');
        }
    }

    /**
     * Validate the action source.
     *
     * @throws InvalidArgumentException
     */
    protected function validateActionSource(): void
    {
        $validSources = ['website', 'app', 'email', 'phone_call', 'chat', 'physical_store', 'system_generated', 'other'];

        if (!in_array($this->action_source, $validSources, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid action source "%s". Must be one of: %s',
                    $this->action_source,
                    implode(', ', $validSources)
                )
            );
        }
    }
}
