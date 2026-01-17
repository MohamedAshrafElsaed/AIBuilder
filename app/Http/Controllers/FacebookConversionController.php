<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\FacebookEventData;
use App\DataTransferObjects\FacebookUserData;
use App\Enums\FacebookEventType;
use App\Http\Requests\SendFacebookEventRequest;
use App\Services\FacebookConversionApiService;
use App\Services\FacebookEventBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacebookConversionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly FacebookConversionApiService $conversionApiService
    ) {}

    /**
     * Send a single event to Facebook Conversion API.
     */
    public function sendEvent(SendFacebookEventRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $userData = new FacebookUserData(
                email: $validated['user_data']['email'] ?? null,
                phone: $validated['user_data']['phone'] ?? null,
                firstName: $validated['user_data']['first_name'] ?? null,
                lastName: $validated['user_data']['last_name'] ?? null,
                city: $validated['user_data']['city'] ?? null,
                state: $validated['user_data']['state'] ?? null,
                zip: $validated['user_data']['zip_code'] ?? null,
                country: $validated['user_data']['country'] ?? null,
                externalId: $validated['user_data']['external_id'] ?? null,
                clientIpAddress: $validated['user_data']['client_ip_address'] ?? $request->ip(),
                clientUserAgent: $validated['user_data']['client_user_agent'] ?? $request->userAgent(),
                fbc: $validated['user_data']['fbc'] ?? null,
                fbp: $validated['user_data']['fbp'] ?? null
            );

            $event = FacebookEventBuilder::create()
                ->eventName(FacebookEventType::from($validated['event_name']))
                ->eventTime($validated['event_time'] ?? now()->timestamp)
                ->eventSourceUrl($validated['event_source_url'] ?? $request->url())
                ->userData($userData)
                ->customData($validated['custom_data'] ?? [])
                ->actionSource($validated['action_source'] ?? 'website')
                ->build();

            $response = $this->conversionApiService->sendEvent($event);

            Log::info('Facebook Conversion API event sent successfully', [
                'event_name' => $validated['event_name'],
                'event_id' => $response['event_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event sent successfully',
                'data' => $response,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to send Facebook Conversion API event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send multiple events to Facebook Conversion API in batch.
     */
    public function sendBatchEvents(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'events' => ['required', 'array', 'min:1', 'max:1000'],
                'events.*.event_name' => ['required', 'string'],
                'events.*.event_time' => ['nullable', 'integer'],
                'events.*.event_source_url' => ['nullable', 'url'],
                'events.*.action_source' => ['nullable', 'string'],
                'events.*.user_data' => ['required', 'array'],
                'events.*.user_data.email' => ['nullable', 'email'],
                'events.*.user_data.phone' => ['nullable', 'string'],
                'events.*.custom_data' => ['nullable', 'array'],
            ]);

            $events = [];

            foreach ($request->input('events') as $eventData) {
                $userData = new FacebookUserData(
                    email: $eventData['user_data']['email'] ?? null,
                    phone: $eventData['user_data']['phone'] ?? null,
                    firstName: $eventData['user_data']['first_name'] ?? null,
                    lastName: $eventData['user_data']['last_name'] ?? null,
                    city: $eventData['user_data']['city'] ?? null,
                    state: $eventData['user_data']['state'] ?? null,
                    zip: $eventData['user_data']['zip_code'] ?? null,
                    country: $eventData['user_data']['country'] ?? null,
                    externalId: $eventData['user_data']['external_id'] ?? null,
                    clientIpAddress: $eventData['user_data']['client_ip_address'] ?? $request->ip(),
                    clientUserAgent: $eventData['user_data']['client_user_agent'] ?? $request->userAgent(),
                    fbc: $eventData['user_data']['fbc'] ?? null,
                    fbp: $eventData['user_data']['fbp'] ?? null
                );

                $events[] = FacebookEventBuilder::create()
                    ->eventName(FacebookEventType::from($eventData['event_name']))
                    ->eventTime($eventData['event_time'] ?? now()->timestamp)
                    ->eventSourceUrl($eventData['event_source_url'] ?? $request->url())
                    ->userData($userData)
                    ->customData($eventData['custom_data'] ?? [])
                    ->actionSource($eventData['action_source'] ?? 'website')
                    ->build();
            }

            $response = $this->conversionApiService->sendBatchEvents($events);

            Log::info('Facebook Conversion API batch events sent successfully', [
                'events_count' => count($events),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Batch events sent successfully',
                'data' => $response,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to send Facebook Conversion API batch events', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send batch events',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a test event to Facebook Conversion API.
     */
    public function testEvent(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'test_event_code' => ['required', 'string'],
                'event_name' => ['required', 'string'],
                'user_data' => ['required', 'array'],
                'user_data.email' => ['nullable', 'email'],
                'user_data.phone' => ['nullable', 'string'],
                'custom_data' => ['nullable', 'array'],
            ]);

            $userData = new FacebookUserData(
                email: $request->input('user_data.email'),
                phone: $request->input('user_data.phone'),
                firstName: $request->input('user_data.first_name'),
                lastName: $request->input('user_data.last_name'),
                city: $request->input('user_data.city'),
                state: $request->input('user_data.state'),
                zip: $request->input('user_data.zip_code'),
                country: $request->input('user_data.country'),
                externalId: $request->input('user_data.external_id'),
                clientIpAddress: $request->input('user_data.client_ip_address') ?? $request->ip(),
                clientUserAgent: $request->input('user_data.client_user_agent') ?? $request->userAgent(),
                fbc: $request->input('user_data.fbc'),
                fbp: $request->input('user_data.fbp')
            );

            $event = FacebookEventBuilder::create()
                ->eventName(FacebookEventType::from($request->input('event_name')))
                ->eventTime($request->input('event_time') ?? now()->timestamp)
                ->eventSourceUrl($request->input('event_source_url') ?? $request->url())
                ->userData($userData)
                ->customData($request->input('custom_data', []))
                ->actionSource($request->input('action_source', 'website'))
                ->build();

            $response = $this->conversionApiService->sendTestEvent(
                $event,
                $request->input('test_event_code')
            );

            Log::info('Facebook Conversion API test event sent successfully', [
                'event_name' => $request->input('event_name'),
                'test_event_code' => $request->input('test_event_code'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test event sent successfully',
                'data' => $response,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to send Facebook Conversion API test event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
