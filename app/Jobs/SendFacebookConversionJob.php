<?php

namespace App\Jobs;

use App\DataTransferObjects\FacebookEventData;
use App\DataTransferObjects\FacebookUserData;
use App\Exceptions\FacebookConversionApiException;
use App\Models\FacebookConversionLog;
use App\Services\FacebookConversionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFacebookConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public int $timeout = 30;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FacebookEventData $eventData,
        public FacebookUserData $userData,
        public ?int $logId = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(FacebookConversionApiService $service): void
    {
        try {
            Log::info('Sending Facebook conversion event', [
                'event_name' => $this->eventData->eventName->value,
                'event_id' => $this->eventData->eventId,
                'attempt' => $this->attempts(),
            ]);

            // Merge user data into event data before sending
            $this->eventData->userData = $this->userData;

            $response = $service->sendEvent($this->eventData);

            $this->updateLog('success', $response);

            Log::info('Facebook conversion event sent successfully', [
                'event_name' => $this->eventData->eventName->value,
                'event_id' => $this->eventData->eventId,
                'response' => $response,
            ]);
        } catch (FacebookConversionApiException $e) {
            $this->handleFailure($e);
            throw $e;
        } catch (Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Facebook conversion event failed permanently', [
            'event_name' => $this->eventData->eventName->value,
            'event_id' => $this->eventData->eventId,
            'attempts' => $this->attempts(),
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        $this->updateLog('failed', [
            'error' => $exception?->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Handle job failure and update log.
     */
    protected function handleFailure(Throwable $exception): void
    {
        Log::warning('Facebook conversion event attempt failed', [
            'event_name' => $this->eventData->eventName->value,
            'event_id' => $this->eventData->eventId,
            'attempt' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        $this->updateLog('retrying', [
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);
    }

    /**
     * Update the conversion log with status and response.
     */
    protected function updateLog(string $status, array $response): void
    {
        if ($this->logId === null) {
            return;
        }

        try {
            FacebookConversionLog::where('id', $this->logId)->update([
                'status' => $status,
                'response' => $response,
                'attempts' => $this->attempts(),
                'sent_at' => $status === 'success' ? now() : null,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update Facebook conversion log', [
                'log_id' => $this->logId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'facebook-conversion',
            'event:' . $this->eventData->eventName->value,
            'event-id:' . $this->eventData->eventId,
        ];
    }
}
