<?php

declare(strict_types=1);

namespace App\Events;

use App\DataTransferObjects\FacebookEventData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a Facebook conversion event needs to be tracked.
 *
 * This event can be used with listeners or jobs for async event tracking.
 */
class FacebookConversionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
 *
     * @param FacebookEventData $eventData The Facebook event data to be tracked
     */
    public function __construct(
        public readonly FacebookEventData $eventData
    ) {}
}
