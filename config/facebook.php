<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Facebook Pixel ID
    |--------------------------------------------------------------------------
    |
    | Your Facebook Pixel ID. This can be found in your Facebook Events
    | Manager under Data Sources.
    |
    */

    'pixel_id' => env('FACEBOOK_PIXEL_ID'),

    /*
    |--------------------------------------------------------------------------
    | Access Token
    |--------------------------------------------------------------------------
    |
    | Your Facebook Conversion API access token. Generate this from your
    | Facebook Business Manager settings.
    |
    */

    'access_token' => env('FACEBOOK_CONVERSION_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The Facebook Graph API version to use. Format: v{major}.{minor}
    |
    */

    'api_version' => env('FACEBOOK_API_VERSION', 'v18.0'),

    /*
    |--------------------------------------------------------------------------
    | Test Event Code
    |--------------------------------------------------------------------------
    |
    | Optional test event code for testing your Conversion API integration.
    | Events sent with this code will appear in the Test Events tool.
    |
    */

    'test_event_code' => env('FACEBOOK_TEST_EVENT_CODE'),

];
