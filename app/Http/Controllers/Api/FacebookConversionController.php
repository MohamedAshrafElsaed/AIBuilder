<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrackFacebookEventRequest;
use App\Services\FacebookConversionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Handles Facebook Conversion API event tracking.
 */
class FacebookConversionController extends Controller
{
    /**
     * Track a single Facebook Conversion API event.
     *
     * @param TrackFacebookEventRequest $request
     * @param FacebookConversionApiService $service
     * @return JsonResponse
     */
    public function trackEvent(
        TrackFacebookEventRequest $request,
        FacebookConversionApiService $service
    ): JsonResponse {
        try {
            $validated = $request->validated();

            $response = $service->trackEvent(
                eventName: $validated['event_name'],
                eventData: $validated['event_data'] ?? [],
                userData: $validated['user_data'] ?? [],
                customData: $validated['custom_data'] ?? [],
                eventSourceUrl: $validated['event_source_url'] ?? null,
                actionSource: $validated['action_source'] ?? 'website'
            );

            Log::info('Facebook Conversion API event tracked successfully', [
                'event_name' => $validated['event_name'],
                'response' => $response,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event tracked successfully',
                'data' => $response,
            ], 200);
        } catch (\FacebookAds\Exception\Exception $e) {
            Log::error('Facebook Conversion API error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'event_name' => $validated['event_name'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track event',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error tracking Facebook event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Track multiple Facebook Conversion API events in a batch.
     *
     * @param Request $request
     * @param FacebookConversionApiService $service
     * @return JsonResponse
     */
    public function trackBatch(
        Request $request,
        FacebookConversionApiService $service
    ): JsonResponse {
        try {
            $validator = Validator::make($request->all(), [
                'events' => ['required', 'array', 'min:1', 'max:1000'],
                'events.*.event_name' => ['required', 'string', 'max:255'],
                'events.*.event_data' => ['sometimes', 'array'],
                'events.*.event_data.event_time' => ['sometimes', 'integer', 'min:1'],
                'events.*.event_data.event_id' => ['sometimes', 'string', 'max:255'],
                'events.*.user_data' => ['sometimes', 'array'],
                'events.*.user_data.em' => ['sometimes', 'array'],
                'events.*.user_data.em.*' => ['email'],
                'events.*.user_data.ph' => ['sometimes', 'array'],
                'events.*.user_data.ph.*' => ['string'],
                'events.*.user_data.fn' => ['sometimes', 'array'],
                'events.*.user_data.fn.*' => ['string'],
                'events.*.user_data.ln' => ['sometimes', 'array'],
                'events.*.user_data.ln.*' => ['string'],
                'events.*.user_data.ct' => ['sometimes', 'array'],
                'events.*.user_data.ct.*' => ['string'],
                'events.*.user_data.st' => ['sometimes', 'array'],
                'events.*.user_data.st.*' => ['string'],
                'events.*.user_data.zp' => ['sometimes', 'array'],
                'events.*.user_data.zp.*' => ['string'],
                'events.*.user_data.country' => ['sometimes', 'array'],
                'events.*.user_data.country.*' => ['string', 'size:2'],
                'events.*.user_data.external_id' => ['sometimes', 'array'],
                'events.*.user_data.external_id.*' => ['string'],
                'events.*.user_data.client_ip_address' => ['sometimes', 'ip'],
                'events.*.user_data.client_user_agent' => ['sometimes', 'string'],
                'events.*.user_data.fbc' => ['sometimes', 'string'],
                'events.*.user_data.fbp' => ['sometimes', 'string'],
                'events.*.custom_data' => ['sometimes', 'array'],
                'events.*.custom_data.value' => ['sometimes', 'numeric'],
                'events.*.custom_data.currency' => ['sometimes', 'string', 'size:3'],
                'events.*.custom_data.content_name' => ['sometimes', 'string'],
                'events.*.custom_data.content_category' => ['sometimes', 'string'],
                'events.*.custom_data.content_ids' => ['sometimes', 'array'],
                'events.*.custom_data.content_type' => ['sometimes', 'string'],
                'events.*.custom_data.num_items' => ['sometimes', 'integer', 'min:0'],
                'events.*.custom_data.predicted_ltv' => ['sometimes', 'numeric'],
                'events.*.custom_data.status' => ['sometimes', 'string'],
                'events.*.event_source_url' => ['sometimes', 'url', 'max:2048'],
                'events.*.action_source' => ['sometimes', 'string', 'in:website,email,app,phone_call,chat,physical_store,system_generated,other'],
            ], [
                'events.required' => 'Events array is required',
                'events.array' => 'Events must be an array',
                'events.min' => 'At least one event is required',
                'events.max' => 'Maximum 1000 events allowed per batch',
                'events.*.event_name.required' => 'Event name is required for all events',
                'events.*.user_data.em.*.email' => 'Invalid email format in user data',
                'events.*.user_data.country.*.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2)',
                'events.*.custom_data.currency.size' => 'Currency must be 3 characters (ISO 4217)',
                'events.*.event_source_url.url' => 'Event source URL must be a valid URL',
                'events.*.action_source.in' => 'Invalid action source',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();
            $events = $validated['events'];

            $response = $service->trackBatch($events);

            Log::info('Facebook Conversion API batch tracked successfully', [
                'event_count' => count($events),
                'response' => $response,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Batch events tracked successfully',
                'data' => [
                    'events_sent' => count($events),
                    'response' => $response,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\FacebookAds\Exception\Exception $e) {
            Log::error('Facebook Conversion API batch error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'event_count' => count($events ?? []),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track batch events',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error tracking Facebook batch events', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
