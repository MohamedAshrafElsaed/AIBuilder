<?php

namespace App\Events;

use App\DataTransferObjects\FacebookEventData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Facebook Conversion Event
 *
 * Dispatched when a conversion tracking event needs to be sent to Facebook.
 */
class FacebookConversionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly FacebookEventData $eventData;

    /**
     * Create a new event instance.
     *
     * @param string $eventType The type of Facebook event (e.g., 'Purchase', 'Lead')
     * @param string|null $email User's email address
     * @param string|null $phone User's phone number
     * @param string|null $firstName User's first name
     * @param string|null $lastName User's last name
     * @param string|null $city User's city
     * @param string|null $state User's state
     * @param string|null $zip User's zip code
     * @param string|null $country User's country code
     * @param array $customData Additional custom data for the event
     * @param string|null $eventSourceUrl The URL where the event occurred
     * @param string|null $fbp Facebook browser ID (_fbp cookie)
     * @param string|null $fbc Facebook click ID (_fbc cookie)
     */
    public function __construct(
        string $eventType,
        ?string $email = null,
        ?string $phone = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $city = null,
        ?string $state = null,
        ?string $zip = null,
        ?string $country = null,
        array $customData = [],
        ?string $eventSourceUrl = null,
        ?string $fbp = null,
        ?string $fbc = null
    ) {
        $this->eventData = new FacebookEventData(
            eventType: $eventType,
            email: $email,
            phone: $phone,
            firstName: $firstName,
            lastName: $lastName,
            city: $city,
            state: $state,
            zip: $zip,
            country: $country,
            customData: $customData,
            eventSourceUrl: $eventSourceUrl,
            fbp: $fbp,
            fbc: $fbc
        );
    }
}
