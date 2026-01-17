<?php

namespace App\Listeners;

use App\Events\FacebookConversionEvent;
use App\Services\FacebookConversionApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listener for Facebook Conversion Events.
 *
 * Sends conversion events to Facebook Conversion API when triggered.
 */
class SendFacebookConversionEvent implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 60;

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly FacebookConversionApiService $facebookService
    ) {}

    /**
     * Handle the event.
     *
     * @param  FacebookConversionEvent  $event
     * @return void
     */
    public function handle(FacebookConversionEvent $event): void
    {
        try {
            Log::info('Sending Facebook conversion event', [
                'event_type' => $event->eventData->eventName,
                'event_id' => $event->eventData->eventId,
            ]);

            $response = $this->facebookService->sendEvent($event->eventData);

            Log::info('Facebook conversion event sent successfully', [
                'event_type' => $event->eventData->eventName,
                'event_id' => $event->eventData->eventId,
                'response' => $response,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to send Facebook conversion event', [
                'event_type' => $event->eventData->eventName,
                'event_id' => $event->eventData->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  FacebookConversionEvent  $event
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(FacebookConversionEvent $event, Throwable $exception): void
    {
        Log::critical('Facebook conversion event failed after all retries', [
            'event_type' => $event->eventData->eventName,
            'event_id' => $event->eventData->eventId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
