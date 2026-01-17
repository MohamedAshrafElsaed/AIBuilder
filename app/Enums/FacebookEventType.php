<?php

namespace App\Enums;

enum FacebookEventType: string
{
    case PAGE_VIEW = 'PageView';
    case VIEW_CONTENT = 'ViewContent';
    case ADD_TO_CART = 'AddToCart';
    case ADD_TO_WISHLIST = 'AddToWishlist';
    case INITIATE_CHECKOUT = 'InitiateCheckout';
    case ADD_PAYMENT_INFO = 'AddPaymentInfo';
    case PURCHASE = 'Purchase';
    case LEAD = 'Lead';
    case COMPLETE_REGISTRATION = 'CompleteRegistration';
    case CONTACT = 'Contact';
    case CUSTOMIZE_PRODUCT = 'CustomizeProduct';
    case DONATE = 'Donate';
    case FIND_LOCATION = 'FindLocation';
    case SCHEDULE = 'Schedule';
    case SEARCH = 'Search';
    case START_TRIAL = 'StartTrial';
    case SUBMIT_APPLICATION = 'SubmitApplication';
    case SUBSCRIBE = 'Subscribe';

    /**
     * Get the event name.
     */
    public function getName(): string
    {
        return $this->value;
    }

    /**
     * Get all event names as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if the event is a conversion event.
     */
    public function isConversionEvent(): bool
    {
        return in_array($this, [
            self::PURCHASE,
            self::LEAD,
            self::COMPLETE_REGISTRATION,
            self::SUBSCRIBE,
            self::START_TRIAL,
            self::SUBMIT_APPLICATION,
        ]);
    }

    /**
     * Check if the event is an e-commerce event.
     */
    public function isEcommerceEvent(): bool
    {
        return in_array($this, [
            self::VIEW_CONTENT,
            self::ADD_TO_CART,
            self::ADD_TO_WISHLIST,
            self::INITIATE_CHECKOUT,
            self::ADD_PAYMENT_INFO,
            self::PURCHASE,
        ]);
    }
}
