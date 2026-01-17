<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\FacebookEventData;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Handles communication with Facebook Conversion API.
 *
 * This service sends conversion events to Facebook's Conversion API
 * for tracking user actions and conversions.
 */
class FacebookConversionApiService
{
    /**
     * The HTTP client instance.
     */
    private PendingRequest $client;

    /**
     * Facebook Pixel ID.
     */
    private string $pixelId;

    /**
     * Facebook Conversion API Access Token.
     */
    private string $accessToken;

    /**
     * Facebook Conversion API endpoint.
     */
    private string $apiEndpoint;

    /**
     * Test event code for testing events.
     */
    private ?string $testEventCode;

    /**
     * Create a new FacebookConversionApiService instance.
     *
     * @param PendingRequest|null $client
     * @throws InvalidArgumentException
     */
    public function __construct(?PendingRequest $client = null)
    {
        $this->pixelId = config('facebook.pixel_id', '');
        $this->accessToken = config('facebook.conversion_api_token', '');
        $this->apiEndpoint = config('facebook.conversion_api_endpoint', 'https://graph.facebook.com/v18.0');
        $this->testEventCode = config('facebook.test_event_code');

        if (empty($this->pixelId)) {
            throw new InvalidArgumentException('Facebook Pixel ID is not configured.');
        }

        if (empty($this->accessToken)) {
            throw new InvalidArgumentException('Facebook Conversion API Access Token is not configured.');
        }

        $this->client = $client ?? Http::timeout(30)
            ->retry(3, 100)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * Track a single event to Facebook Conversion API.
     *
     * @param FacebookEventData $eventData
     * @return array
     * @throws RuntimeException
     */
    public function trackEvent(FacebookEventData $eventData): array
    {
        try {
            $payload = $this->buildPayload($eventData);

            Log::info('Sending event to Facebook Conversion API', [
                'event_name' => $eventData->eventName,
                'event_id' => $eventData->eventId,
            ]);

            $response = $this->client->post(
                $this->buildEndpointUrl(),
                $payload
            );

            $this->validateResponse($response);

            $responseData = $response->json();

            Log::info('Facebook Conversion API event tracked successfully', [
                'event_name' => $eventData->eventName,
                'event_id' => $eventData->eventId,
                'events_received' => $responseData['events_received'] ?? 0,
            ]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Failed to track Facebook Conversion API event', [
                'event_name' => $eventData->eventName,
                'event_id' => $eventData->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                "Failed to track Facebook event: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Track multiple events to Facebook Conversion API in a single request.
     *
     * @param array<FacebookEventData> $events
     * @return array
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function trackMultipleEvents(array $events): array
    {
        if (empty($events)) {
            throw new InvalidArgumentException('Events array cannot be empty.');
        }

        if (count($events) > 1000) {
            throw new InvalidArgumentException('Cannot send more than 1000 events in a single request.');
        }

        foreach ($events as $event) {
            if (!$event instanceof FacebookEventData) {
                throw new InvalidArgumentException('All events must be instances of FacebookEventData.');
            }
        }

        try {
            $eventPayloads = array_map(
                fn(FacebookEventData $event) => $this->buildEventPayload($event),
                $events
            );

            $payload = [
                'data' => $eventPayloads,
            ];

            if ($this->testEventCode) {
                $payload['test_event_code'] = $this->testEventCode;
            }

            Log::info('Sending multiple events to Facebook Conversion API', [
                'event_count' => count($events),
            ]);

            $response = $this->client->post(
                $this->buildEndpointUrl(),
                $payload
            );

            $this->validateResponse($response);

            $responseData = $response->json();

            Log::info('Facebook Conversion API events tracked successfully', [
                'event_count' => count($events),
                'events_received' => $responseData['events_received'] ?? 0,
            ]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Failed to track multiple Facebook Conversion API events', [
                'event_count' => count($events),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                "Failed to track Facebook events: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build the complete payload for a single event.
     *
     * @param FacebookEventData $eventData
     * @return array
     */
    public function buildPayload(FacebookEventData $eventData): array
    {
        $payload = [
            'data' => [$this->buildEventPayload($eventData)],
        ];

        if ($this->testEventCode) {
            $payload['test_event_code'] = $this->testEventCode;
        }

        return $payload;
    }

    /**
     * Build the event payload from FacebookEventData.
     *
     * @param FacebookEventData $eventData
     * @return array
     */
    private function buildEventPayload(FacebookEventData $eventData): array
    {
        $payload = [
            'event_name' => $eventData->eventName,
            'event_time' => $eventData->eventTime,
            'event_source_url' => $eventData->eventSourceUrl,
            'action_source' => $eventData->actionSource,
        ];

        if ($eventData->eventId) {
            $payload['event_id'] = $eventData->eventId;
        }

        if (!empty($eventData->userData)) {
            $payload['user_data'] = $this->hashUserData($eventData->userData);
        }

        if (!empty($eventData->customData)) {
            $payload['custom_data'] = $eventData->customData;
        }

        if ($eventData->optOut !== null) {
            $payload['opt_out'] = $eventData->optOut;
        }

        return $payload;
    }

    /**
     * Hash user data according to Facebook's requirements.
     *
     * Facebook requires certain user data fields to be hashed using SHA256.
     * Fields that should be hashed: em (email), ph (phone), ge (gender),
     * db (date of birth), ln (last name), fn (first name), ct (city),
     * st (state), zp (zip), country.
     *
     * @param array $userData
     * @return array
     */
    public function hashUserData(array $userData): array
    {
        $fieldsToHash = [
            'em',      // email
            'ph',      // phone
            'ge',      // gender
            'db',      // date of birth
            'ln',      // last name
            'fn',      // first name
            'ct',      // city
            'st',      // state
            'zp',      // zip
            'country', // country
        ];

        $hashedData = [];

        foreach ($userData as $key => $value) {
            if (in_array($key, $fieldsToHash) && !empty($value)) {
                // Normalize the value before hashing
                $normalizedValue = $this->normalizeValue($key, $value);
                $hashedData[$key] = hash('sha256', $normalizedValue);
            } else {
                // Don't hash fields that shouldn't be hashed (like fbc, fbp, client_ip_address, client_user_agent)
                $hashedData[$key] = $value;
            }
        }

        return $hashedData;
    }

    /**
     * Normalize a value before hashing according to Facebook's requirements.
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function normalizeValue(string $key, mixed $value): string
    {
        $value = (string) $value;

        // Remove all whitespace
        $value = preg_replace('/\s+/', '', $value);

        // Convert to lowercase
        $value = strtolower($value);

        // Special handling for phone numbers
        if ($key === 'ph') {
            // Remove all non-numeric characters
            $value = preg_replace('/[^0-9]/', '', $value);
        }

        // Special handling for email
        if ($key === 'em') {
            // Remove dots from gmail addresses before @
            if (str_contains($value, '@gmail.com')) {
                $parts = explode('@', $value);
                $parts[0] = str_replace('.', '', $parts[0]);
                $value = implode('@', $parts);
            }
        }

        return $value;
    }

    /**
     * Validate the response from Facebook Conversion API.
     *
     * @param Response $response
     * @return void
     * @throws RuntimeException
     */
    public function validateResponse(Response $response): void
    {
        if (!$response->successful()) {
            $errorMessage = 'Facebook Conversion API request failed';
            $errorDetails = [];

            if ($response->json()) {
                $json = $response->json();
                $errorDetails = [
                    'status' => $response->status(),
                    'error' => $json['error'] ?? null,
                    'message' => $json['error']['message'] ?? $response->body(),
                ];

                if (isset($json['error']['message'])) {
                    $errorMessage .= ": {$json['error']['message']}";
                }
            } else {
                $errorDetails = [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            Log::error('Facebook Conversion API validation failed', $errorDetails);

            throw new RuntimeException($errorMessage, $response->status());
        }

        $responseData = $response->json();

        // Check for events_received
        if (!isset($responseData['events_received'])) {
            Log::warning('Facebook Conversion API response missing events_received', [
                'response' => $responseData,
            ]);
        }

        // Check for messages (warnings or errors)
        if (isset($responseData['messages']) && !empty($responseData['messages'])) {
            Log::warning('Facebook Conversion API returned messages', [
                'messages' => $responseData['messages'],
            ]);
        }

        // Check for fbtrace_id for debugging
        if (isset($responseData['fbtrace_id'])) {
            Log::debug('Facebook Conversion API trace ID', [
                'fbtrace_id' => $responseData['fbtrace_id'],
            ]);
        }
    }

    /**
     * Build the full endpoint URL.
     *
     * @return string
     */
    private function buildEndpointUrl(): string
    {
        return sprintf(
            '%s/%s/events?access_token=%s',
            rtrim($this->apiEndpoint, '/'),
            $this->pixelId,
            $this->accessToken
        );
    }

    /**
     * Set test event code for testing.
     *
     * @param string|null $testEventCode
     * @return self
     */
    public function setTestEventCode(?string $testEventCode): self
    {
        $this->testEventCode = $testEventCode;

        return $this;
    }

    /**
     * Get the current test event code.
     *
     * @return string|null
     */
    public function getTestEventCode(): ?string
    {
        return $this->testEventCode;
    }
}
