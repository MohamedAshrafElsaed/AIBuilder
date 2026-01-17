<?php

namespace App\Services;

use App\DataTransferObjects\FacebookEventData;
use App\DataTransferObjects\FacebookUserData;
use App\Enums\FacebookEventType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FacebookConversionApiService
{
    private const API_VERSION = 'v18.0';
    private const BASE_URL = 'https://graph.facebook.com';

    private string $pixelId;
    private string $accessToken;
    private bool $testMode;

    public function __construct()
    {
        $this->pixelId = config('facebook.pixel_id');
        $this->accessToken = config('facebook.access_token');
        $this->testMode = config('facebook.test_mode', false);

        if (empty($this->pixelId) || empty($this->accessToken)) {
            throw new InvalidArgumentException('Facebook Pixel ID and Access Token must be configured.');
        }
    }

    /**
     * Send a single event to Facebook Conversion API.
     */
    public function sendEvent(FacebookEventData $eventData): Response
    {
        $this->validateEvent($eventData);

        $payload = $this->buildEventPayload($eventData);

        Log::info('Sending Facebook Conversion API event', [
            'event_name' => $eventData->eventName->value,
        ]);

        return $this->makeRequest([
            'data' => [$payload],
        ]);
    }

    /**
     * Send multiple events in a single batch request.
     *
     * @param array<FacebookEventData> $events
     */
    public function sendBatchEvents(array $events): Response
    {
        if (empty($events)) {
            throw new InvalidArgumentException('Events array cannot be empty.');
        }

        if (count($events) > 1000) {
            throw new InvalidArgumentException('Cannot send more than 1000 events in a single batch.');
        }

        $payloads = [];
        foreach ($events as $eventData) {
            $this->validateEvent($eventData);
            $payloads[] = $this->buildEventPayload($eventData);
        }

        Log::info('Sending Facebook Conversion API batch events', [
            'event_count' => count($events),
        ]);

        return $this->makeRequest([
            'data' => $payloads,
        ]);
    }

    /**
     * Hash user data according to Facebook's requirements.
     */
    public function hashUserData(string $value): string
    {
        // Normalize the value
        $normalized = trim(strtolower($value));

        // Hash using SHA-256
        return hash('sha256', $normalized);
    }

    /**
     * Build the event payload for the API request.
     */
    public function buildEventPayload(FacebookEventData $eventData): array
    {
        $payload = [
            'event_name' => $eventData->eventName->value,
            'event_time' => $eventData->eventTime,
            'action_source' => $eventData->actionSource,
        ];

        // Add event source URL if provided
        if (property_exists($eventData, 'eventSourceUrl') && $eventData->eventSourceUrl) {
            $payload['event_source_url'] = $eventData->eventSourceUrl;
        }

        // Add user data
        if ($eventData->userData) {
            $payload['user_data'] = $this->buildUserDataPayload($eventData->userData);
        }

        // Add custom data if provided
        if (property_exists($eventData, 'customData') && $eventData->customData && !empty($eventData->customData)) {
            $payload['custom_data'] = $eventData->customData;
        }

        return $payload;
    }

    /**
     * Build user data payload with hashed values.
     */
    private function buildUserDataPayload(FacebookUserData $userData): array
    {
        $payload = [];

        if (property_exists($userData, 'email') && $userData->email) {
            $payload['em'] = $this->hashUserData($userData->email);
        }

        if (property_exists($userData, 'phone') && $userData->phone) {
            $payload['ph'] = $this->hashUserData($userData->phone);
        }

        if (property_exists($userData, 'firstName') && $userData->firstName) {
            $payload['fn'] = $this->hashUserData($userData->firstName);
        }

        if (property_exists($userData, 'lastName') && $userData->lastName) {
            $payload['ln'] = $this->hashUserData($userData->lastName);
        }

        if (property_exists($userData, 'city') && $userData->city) {
            $payload['ct'] = $this->hashUserData($userData->city);
        }

        if (property_exists($userData, 'state') && $userData->state) {
            $payload['st'] = $this->hashUserData($userData->state);
        }

        if (property_exists($userData, 'zip') && $userData->zip) {
            $payload['zp'] = $this->hashUserData($userData->zip);
        }

        if (property_exists($userData, 'country') && $userData->country) {
            $payload['country'] = $this->hashUserData($userData->country);
        }

        if (property_exists($userData, 'externalId') && $userData->externalId) {
            $payload['external_id'] = $this->hashUserData($userData->externalId);
        }

        if (property_exists($userData, 'clientIpAddress') && $userData->clientIpAddress) {
            $payload['client_ip_address'] = $userData->clientIpAddress;
        }

        if (property_exists($userData, 'clientUserAgent') && $userData->clientUserAgent) {
            $payload['client_user_agent'] = $userData->clientUserAgent;
        }

        if (property_exists($userData, 'fbc') && $userData->fbc) {
            $payload['fbc'] = $userData->fbc;
        }

        if (property_exists($userData, 'fbp') && $userData->fbp) {
            $payload['fbp'] = $userData->fbp;
        }

        return $payload;
    }

    /**
     * Validate event data before sending.
     */
    public function validateEvent(FacebookEventData $eventData): void
    {
        if (!$eventData->eventName instanceof FacebookEventType) {
            throw new InvalidArgumentException('Event name must be a valid FacebookEventType.');
        }

        if ($eventData->eventTime <= 0) {
            throw new InvalidArgumentException('Event time must be a valid Unix timestamp.');
        }

        // Event time should not be more than 7 days in the past
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        if ($eventData->eventTime < $sevenDaysAgo) {
            throw new InvalidArgumentException('Event time cannot be more than 7 days in the past.');
        }

        if (empty($eventData->actionSource)) {
            throw new InvalidArgumentException('Action source is required.');
        }

        if (!$eventData->userData) {
            throw new InvalidArgumentException('User data is required.');
        }
    }

    /**
     * Make the HTTP request to Facebook Conversion API.
     */
    private function makeRequest(array $data): Response
    {
        $url = $this->buildApiUrl();

        $payload = array_merge($data, [
            'access_token' => $this->accessToken,
        ]);

        if ($this->testMode) {
            $payload['test_event_code'] = config('facebook.test_event_code', 'TEST12345');
        }

        $response = $this->getHttpClient()
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Facebook Conversion API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } else {
            Log::info('Facebook Conversion API request successful', [
                'events_received' => $response->json('events_received'),
                'messages' => $response->json('messages'),
            ]);
        }

        return $response;
    }

    /**
     * Build the API URL.
     */
    private function buildApiUrl(): string
    {
        return sprintf(
            '%s/%s/%s/events',
            self::BASE_URL,
            self::API_VERSION,
            $this->pixelId
        );
    }

    /**
     * Get configured HTTP client.
     */
    private function getHttpClient(): PendingRequest
    {
        return Http::timeout(30)
            ->retry(3, 100)
            ->acceptJson();
    }
}
