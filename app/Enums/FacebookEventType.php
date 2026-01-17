<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Facebook Standard Events enumeration.
 *
 * Represents the standard events that can be tracked via Facebook Pixel.
 * These events are predefined by Facebook and used for conversion tracking,
 * optimization, and audience building.
 *
 * @see https://developers.facebook.com/docs/meta-pixel/reference
 */
enum FacebookEventType: string
{
    /**
     * Track key page views (e.g. product page, landing page, article).
     */
    case PAGE_VIEW = 'PageView';

    /**
     * Track when a user views content (e.g. landing on a product details page).
     */
    case VIEW_CONTENT = 'ViewContent';

    /**
     * Track when items are added to a shopping cart.
     */
    case ADD_TO_CART = 'AddToCart';

    /**
     * Track when people enter the checkout flow.
     */
    case INITIATE_CHECKOUT = 'InitiateCheckout';

    /**
     * Track purchases or checkout flow completions.
     */
    case PURCHASE = 'Purchase';

    /**
     * Track when a user submits information with the intent of being contacted.
     */
    case LEAD = 'Lead';

    /**
     * Track when a registration form is completed.
     */
    case COMPLETE_REGISTRATION = 'CompleteRegistration';

    /**
     * Track searches on your website, app or other property.
     */
    case SEARCH = 'Search';

    /**
     * Track when someone adds an item to a wishlist.
     */
    case ADD_TO_WISHLIST = 'AddToWishlist';

    /**
     * Track when a person initiates contact with your business.
     */
    case CONTACT = 'Contact';

    /**
     * Get all available event types.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(
            fn(self $case) => $case->value,
            self::cases()
        );
    }

    /**
     * Get the event type label for display purposes.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PAGE_VIEW => 'Page View',
            self::VIEW_CONTENT => 'View Content',
            self::ADD_TO_CART => 'Add to Cart',
            self::INITIATE_CHECKOUT => 'Initiate Checkout',
            self::PURCHASE => 'Purchase',
            self::LEAD => 'Lead',
            self::COMPLETE_REGISTRATION => 'Complete Registration',
            self::SEARCH => 'Search',
            self::ADD_TO_WISHLIST => 'Add to Wishlist',
            self::CONTACT => 'Contact',
        };
    }

    /**
     * Get the event type description.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::PAGE_VIEW => 'Track key page views',
            self::VIEW_CONTENT => 'Track when a user views content',
            self::ADD_TO_CART => 'Track when items are added to a shopping cart',
            self::INITIATE_CHECKOUT => 'Track when people enter the checkout flow',
            self::PURCHASE => 'Track purchases or checkout flow completions',
            self::LEAD => 'Track when a user submits information with the intent of being contacted',
            self::COMPLETE_REGISTRATION => 'Track when a registration form is completed',
            self::SEARCH => 'Track searches on your website, app or other property',
            self::ADD_TO_WISHLIST => 'Track when someone adds an item to a wishlist',
            self::CONTACT => 'Track when a person initiates contact with your business',
        };
    }

    /**
     * Check if the event type requires transaction data.
     *
     * @return bool
     */
    public function requiresTransactionData(): bool
    {
        return in_array($this, [
            self::PURCHASE,
            self::INITIATE_CHECKOUT,
            self::ADD_TO_CART,
        ]);
    }

    /**
     * Check if the event type is a conversion event.
     *
     * @return bool
     */
    public function isConversionEvent(): bool
    {
        return in_array($this, [
            self::PURCHASE,
            self::LEAD,
            self::COMPLETE_REGISTRATION,
            self::INITIATE_CHECKOUT,
        ]);
    }
}
