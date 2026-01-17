<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Facebook Pixel ID
    |--------------------------------------------------------------------------
    |
    | Your Facebook Pixel ID used for tracking events and conversions.
    | This can be found in your Facebook Events Manager.
    |
    */

    'pixel_id' => env('FACEBOOK_PIXEL_ID'),

    /*
    |--------------------------------------------------------------------------
    | Facebook Access Token
    |--------------------------------------------------------------------------
    |
    | Your Facebook access token for the Conversions API. This should be a
    | long-lived access token with the necessary permissions to send events.
    |
    */

    'access_token' => env('FACEBOOK_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Facebook API Version
    |--------------------------------------------------------------------------
    |
    | The version of the Facebook Graph API to use. Defaults to v18.0.
    | Update this when you want to use a newer API version.
    |
    */

    'api_version' => env('FACEBOOK_API_VERSION', 'v18.0'),

    /*
    |--------------------------------------------------------------------------
    | Test Event Code
    |--------------------------------------------------------------------------
    |
    | Optional test event code for testing the Conversions API integration.
    | This is used to verify events in the Test Events tool in Events Manager.
    | Leave null for production.
    |
    */

    'test_event_code' => env('FACEBOOK_TEST_EVENT_CODE'),

    /*
    |--------------------------------------------------------------------------
    | Conversion API Endpoint
    |--------------------------------------------------------------------------
    |
    | The endpoint URL for the Facebook Conversions API. This is typically
    | the Graph API endpoint for sending server-side events.
    |
    */

    'conversion_api_endpoint' => env(
        'FACEBOOK_CONVERSION_API_ENDPOINT',
        'https://graph.facebook.com'
    ),

];
