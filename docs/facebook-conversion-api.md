# Facebook Conversion API Integration

This documentation covers the Facebook Conversion API integration for tracking server-side events.

## Table of Contents

- [Overview](#overview)
- [Setup Instructions](#setup-instructions)
- [Configuration](#configuration)
- [Event Types](#event-types)
- [Usage Examples](#usage-examples)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## Overview

The Facebook Conversion API allows you to send web events directly from your server to Facebook. This provides more reliable tracking compared to browser-based pixel tracking, especially with ad blockers and privacy features.

### Features

- ✅ Server-side event tracking
- ✅ Multiple event types support
- ✅ Automatic event logging
- ✅ Queue support for async processing
- ✅ Test event mode
- ✅ Comprehensive error handling
- ✅ Event deduplication support

## Setup Instructions

### 1. Install Dependencies

No additional dependencies required - uses Laravel's HTTP client.

### 2. Run Migrations

```bash
php artisan migrate
```

This creates the `facebook_conversion_logs` table for tracking sent events.

### 3. Configure Environment Variables

Add the following to your `.env` file:

```env
FACEBOOK_PIXEL_ID=your_pixel_id
FACEBOOK_ACCESS_TOKEN=your_access_token
FACEBOOK_TEST_EVENT_CODE=  # Optional: for testing
```

### 4. Obtain Facebook Credentials

1. Go to [Facebook Events Manager](https://business.facebook.com/events_manager)
2. Select your pixel
3. Go to Settings → Conversions API
4. Generate an access token
5. Copy your Pixel ID

### 5. Register Event Listener (Already Done)

The `EventServiceProvider` is already configured with the Facebook conversion listener.

## Configuration

### Config File: `config/facebook.php`

```php
return [
    'pixel_id' => env('FACEBOOK_PIXEL_ID'),
    'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
    'test_event_code' => env('FACEBOOK_TEST_EVENT_CODE'),
    'api_version' => env('FACEBOOK_API_VERSION', 'v18.0'),
];
```

### Configuration Options

| Option | Description | Required |
|--------|-------------|----------|
| `pixel_id` | Your Facebook Pixel ID | Yes |
| `access_token` | Facebook Conversions API access token | Yes |
| `test_event_code` | Test event code for testing mode | No |
| `api_version` | Facebook Graph API version | No (default: v18.0) |

## Event Types

Supported event types are defined in `App\Enums\FacebookEventType`:

### Standard Events

- `PAGE_VIEW` - Page view
- `VIEW_CONTENT` - Content view
- `SEARCH` - Search performed
- `ADD_TO_CART` - Item added to cart
- `ADD_TO_WISHLIST` - Item added to wishlist
- `INITIATE_CHECKOUT` - Checkout initiated
- `ADD_PAYMENT_INFO` - Payment info added
- `PURCHASE` - Purchase completed
- `LEAD` - Lead generated
- `COMPLETE_REGISTRATION` - Registration completed
- `CONTACT` - Contact form submitted
- `CUSTOMIZE_PRODUCT` - Product customized
- `DONATE` - Donation made
- `FIND_LOCATION` - Location search
- `SCHEDULE` - Appointment scheduled
- `START_TRIAL` - Trial started
- `SUBMIT_APPLICATION` - Application submitted
- `SUBSCRIBE` - Subscription created

## Usage Examples

### 1. Using Events (Recommended)

Dispatch events from anywhere in your application:

```php
use App\Events\FacebookConversionEvent;
use App\Enums\FacebookEventType;

// Simple page view
event(new FacebookConversionEvent(
    eventType: FacebookEventType::PAGE_VIEW,
    email: 'user@example.com',
    sourceUrl: 'https://example.com/page',
    userAgent: request()->userAgent(),
    ipAddress: request()->ip()
));

// Purchase event with custom data
event(new FacebookConversionEvent(
    eventType: FacebookEventType::PURCHASE,
    email: 'user@example.com',
    phone: '+1234567890',
    firstName: 'John',
    lastName: 'Doe',
    sourceUrl: 'https://example.com/checkout',
    userAgent: request()->userAgent(),
    ipAddress: request()->ip(),
    customData: [
        'value' => 99.99,
        'currency' => 'USD',
        'content_ids' => ['product_123'],
        'content_type' => 'product',
        'num_items' => 1,
    ],
    eventId: 'order_' . time() // For deduplication
));
```

### 2. Using Service Directly

```php
use App\Services\FacebookConversionApiService;
use App\DataTransferObjects\FacebookEventData;
use App\DataTransferObjects\FacebookUserData;
use App\Enums\FacebookEventType;

$service = app(FacebookConversionApiService::class);

$userData = new FacebookUserData(
    email: 'user@example.com',
    phone: '+1234567890',
    firstName: 'John',
    lastName: 'Doe',
    city: 'New York',
    state: 'NY',
    zipCode: '10001',
    country: 'US',
    externalId: 'user_123',
    clientIpAddress: request()->ip(),
    clientUserAgent: request()->userAgent(),
    fbc: request()->cookie('_fbc'),
    fbp: request()->cookie('_fbp')
);

$eventData = new FacebookEventData(
    eventName: FacebookEventType::ADD_TO_CART,
    eventTime: now()->timestamp,
    eventSourceUrl: 'https://example.com/product',
    userData: $userData,
    customData: [
        'value' => 29.99,
        'currency' => 'USD',
        'content_ids' => ['product_456'],
        'content_type' => 'product',
    ],
    eventId: 'cart_' . uniqid()
);

$response = $service->sendEvent($eventData);
```

### 3. Using Queue Job

```php
use App\Jobs\SendFacebookConversionJob;
use App\Enums\FacebookEventType;

SendFacebookConversionJob::dispatch(
    eventType: FacebookEventType::LEAD,
    email: 'lead@example.com',
    sourceUrl: 'https://example.com/contact',
    userAgent: request()->userAgent(),
    ipAddress: request()->ip(),
    customData: [
        'content_name' => 'Contact Form',
    ]
);
```

### 4. In Controllers

```php
use App\Events\FacebookConversionEvent;
use App\Enums\FacebookEventType;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $order = Order::create($request->validated());

        // Track purchase event
        event(new FacebookConversionEvent(
            eventType: FacebookEventType::PURCHASE,
            email: $order->customer_email,
            phone: $order->customer_phone,
            firstName: $order->customer_first_name,
            lastName: $order->customer_last_name,
            sourceUrl: url()->current(),
            userAgent: $request->userAgent(),
            ipAddress: $request->ip(),
            customData: [
                'value' => $order->total,
                'currency' => $order->currency,
                'content_ids' => $order->items->pluck('product_id')->toArray(),
                'content_type' => 'product',
                'num_items' => $order->items->count(),
            ],
            eventId: 'order_' . $order->id
        ));

        return response()->json($order, 201);
    }
}
```

## API Endpoints

### Send Event

**Endpoint:** `POST /api/facebook/events`

**Authentication:** Required (Sanctum)

**Request Body:**

```json
{
    "event_type": "purchase",
    "email": "user@example.com",
    "phone": "+1234567890",
    "first_name": "John",
    "last_name": "Doe",
    "city": "New York",
    "state": "NY",
    "zip_code": "10001",
    "country": "US",
    "source_url": "https://example.com/checkout",
    "user_agent": "Mozilla/5.0...",
    "ip_address": "192.168.1.1",
    "custom_data": {
        "value": 99.99,
        "currency": "USD",
        "content_ids": ["product_123"],
        "content_type": "product"
    },
    "event_id": "order_12345"
}
```

**Validation Rules:**

- `event_type`: required, string, valid FacebookEventType
- `email`: required, email
- `phone`: optional, string
- `first_name`: optional, string, max 255
- `last_name`: optional, string, max 255
- `city`: optional, string, max 255
- `state`: optional, string, max 255
- `zip_code`: optional, string, max 20
- `country`: optional, string, size 2 (ISO country code)
- `source_url`: required, url
- `user_agent`: required, string
- `ip_address`: required, ip
- `custom_data`: optional, array
- `event_id`: optional, string, max 255

**Response (Success):**

```json
{
    "success": true,
    "message": "Event sent successfully",
    "data": {
        "events_received": 1,
        "messages": [],
        "fbtrace_id": "ABC123..."
    }
}
```

**Response (Error):**

```json
{
    "success": false,
    "message": "Failed to send event",
    "error": "Invalid access token"
}
```

**Example cURL:**

```bash
curl -X POST https://your-app.com/api/facebook/events \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "event_type": "purchase",
    "email": "user@example.com",
    "source_url": "https://example.com/checkout",
    "user_agent": "Mozilla/5.0...",
    "ip_address": "192.168.1.1",
    "custom_data": {
        "value": 99.99,
        "currency": "USD"
    }
  }'
```

## Testing

### Test Event Mode

Facebook provides a test event mode to verify your integration without affecting your actual data.

#### 1. Get Test Event Code

1. Go to Facebook Events Manager
2. Select your pixel
3. Go to Test Events tab
4. Copy the test event code
5. Add to `.env`:

```env
FACEBOOK_TEST_EVENT_CODE=TEST12345
```

#### 2. Send Test Events

When `FACEBOOK_TEST_EVENT_CODE` is set, all events are sent as test events:

```php
// This will appear in Test Events tab
event(new FacebookConversionEvent(
    eventType: FacebookEventType::PURCHASE,
    email: 'test@example.com',
    sourceUrl: 'https://example.com/test',
    userAgent: 'TestAgent',
    ipAddress: '127.0.0.1',
    customData: ['value' => 10.00, 'currency' => 'USD']
));
```

#### 3. Verify in Facebook

1. Go to Events Manager → Test Events
2. You should see your test event appear within seconds
3. Check event details and data quality

### Unit Tests

Run the test suite:

```bash
php artisan test --filter FacebookConversion
```

**Available Tests:**

- `FacebookConversionApiServiceTest`
  - Test event sending
  - Test error handling
  - Test data transformation

- `FacebookConversionControllerTest`
  - Test API endpoint
  - Test validation
  - Test authentication

### Manual Testing

#### Test with Tinker

```bash
php artisan tinker
```

```php
use App\Events\FacebookConversionEvent;
use App\Enums\FacebookEventType;

event(new FacebookConversionEvent(
    eventType: FacebookEventType::PAGE_VIEW,
    email: 'test@example.com',
    sourceUrl: 'https://example.com/test',
    userAgent: 'TestAgent',
    ipAddress: '127.0.0.1'
));
```

#### Check Logs

View sent events in the database:

```php
use App\Models\FacebookConversionLog;

// Latest events
FacebookConversionLog::latest()->limit(10)->get();

// Failed events
FacebookConversionLog::whereNotNull('error')->get();

// Events by type
FacebookConversionLog::where('event_type', 'purchase')->get();
```

## Troubleshooting

### Common Issues

#### 1. Events Not Appearing in Facebook

**Symptoms:** Events sent successfully but not visible in Events Manager

**Solutions:**
- Wait 20-30 minutes for events to appear
- Check if test event code is set (events only appear in Test Events tab)
- Verify pixel ID is correct
- Check Events Manager → Data Sources → Your Pixel → Activity

#### 2. Invalid Access Token

**Error:** `Invalid OAuth access token`

**Solutions:**
- Verify `FACEBOOK_ACCESS_TOKEN` in `.env`
- Regenerate access token in Events Manager
- Ensure token has `ads_management` permission
- Check token hasn't expired

#### 3. Events Rejected

**Error:** `Events received but rejected`

**Solutions:**
- Check event data quality in Events Manager
- Ensure email/phone are properly hashed (done automatically)
- Verify custom data format matches Facebook requirements
- Check event_time is not too old (max 7 days)

#### 4. Rate Limiting

**Error:** `Rate limit exceeded`

**Solutions:**
- Use queue jobs for high-volume events
- Implement exponential backoff
- Contact Facebook to increase rate limits

#### 5. Missing User Data

**Warning:** `Low match quality`

**Solutions:**
- Include more user data (email, phone, name, address)
- Include fbp and fbc cookies
- Ensure IP address and user agent are accurate
- Use external_id for logged-in users

### Debug Mode

Enable detailed logging:

```php
// In FacebookConversionApiService
protected function sendRequest(array $data): array
{
    Log::debug('Facebook Conversion API Request', $data);
    
    $response = Http::post($this->getApiUrl(), $data);
    
    Log::debug('Facebook Conversion API Response', [
        'status' => $response->status(),
        'body' => $response->json(),
    ]);
    
    return $response->json();
}
```

### Checking Event Quality

1. Go to Events Manager
2. Select your pixel
3. Go to Overview → Event Match Quality
4. Review match quality score and recommendations

### Testing Checklist

- [ ] Test event code configured
- [ ] Test events appearing in Facebook
- [ ] Event data quality score > 6.0
- [ ] User data properly hashed
- [ ] Event deduplication working (same event_id not duplicated)
- [ ] Queue jobs processing correctly
- [ ] Error handling working
- [ ] Logs being created

## Best Practices

### 1. Event Deduplication

Always provide unique `event_id` to prevent duplicate events:

```php
event(new FacebookConversionEvent(
    eventType: FacebookEventType::PURCHASE,
    email: 'user@example.com',
    // ... other params
    eventId: 'order_' . $order->id // Unique per order
));
```

### 2. User Data Quality

Include as much user data as possible:

```php
event(new FacebookConversionEvent(
    eventType: FacebookEventType::PURCHASE,
    email: 'user@example.com',
    phone: '+1234567890', // Include country code
    firstName: 'John',
    lastName: 'Doe',
    city: 'New York',
    state: 'NY',
    zipCode: '10001',
    country: 'US', // ISO 2-letter code
    externalId: 'user_123', // Your user ID
    // ...
));
```

### 3. Use Queue Jobs

For high-traffic applications, use queue jobs:

```php
SendFacebookConversionJob::dispatch(/* ... */)
    ->onQueue('facebook-events');
```

### 4. Monitor Logs

Regularly check `facebook_conversion_logs` for errors:

```php
// Create a scheduled task
Schedule::call(function () {
    $failedEvents = FacebookConversionLog::whereNotNull('error')
        ->where('created_at', '>', now()->subHour())
        ->count();
    
    if ($failedEvents > 10) {
        // Alert admin
    }
})->hourly();
```

### 5. Cookie Tracking

Include Facebook cookies for better matching:

```php
event(new FacebookConversionEvent(
    // ...
    fbc: request()->cookie('_fbc'),
    fbp: request()->cookie('_fbp')
));
```

## Additional Resources

- [Facebook Conversion API Documentation](https://developers.facebook.com/docs/marketing-api/conversions-api)
- [Event Parameters Reference](https://developers.facebook.com/docs/marketing-api/conversions-api/parameters)
- [Best Practices Guide](https://developers.facebook.com/docs/marketing-api/conversions-api/best-practices)
- [Troubleshooting Guide](https://developers.facebook.com/docs/marketing-api/conversions-api/troubleshooting)

## Support

For issues or questions:

1. Check the troubleshooting section
2. Review Facebook's documentation
3. Check application logs
4. Contact your development team

## License

This integration is part of the AIBuilder project.
