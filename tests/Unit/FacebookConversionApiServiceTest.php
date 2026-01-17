<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DataTransferObjects\FacebookEventData;
use App\Enums\FacebookEventType;
use App\Services\FacebookConversionApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit tests for FacebookConversionApiService.
 */
class FacebookConversionApiServiceTest extends TestCase
{
    private FacebookConversionApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('facebook.pixel_id', 'test_pixel_id');
        Config::set('facebook.access_token', 'test_access_token');
        Config::set('facebook.api_version', 'v18.0');
        Config::set('facebook.test_event_code', null);

        $this->service = new FacebookConversionApiService();
    }

    /**
     * Test that user data is properly hashed with SHA256.
     *
     * @return void
     */
    public function test_user_data_is_hashed_with_sha256(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com/checkout',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890',
            fbc: 'fb.1.1234567890.AbCdEfGhIjKlMnOpQrStUvWxYz',
            email: 'test@example.com',
            phone: '+1234567890',
            firstName: 'John',
            lastName: 'Doe',
            city: 'New York',
            state: 'NY',
            zipCode: '10001',
            country: 'US',
            customData: ['value' => 99.99, 'currency' => 'USD']
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            $userData = $body['data'][0]['user_data'];

            // Verify email is hashed
            $this->assertEquals(
                hash('sha256', 'test@example.com'),
                $userData['em'],
                'Email should be hashed with SHA256'
            );

            // Verify phone is hashed
            $this->assertEquals(
                hash('sha256', '+1234567890'),
                $userData['ph'],
                'Phone should be hashed with SHA256'
            );

            // Verify first name is hashed
            $this->assertEquals(
                hash('sha256', 'john'),
                $userData['fn'],
                'First name should be lowercased and hashed with SHA256'
            );

            // Verify last name is hashed
            $this->assertEquals(
                hash('sha256', 'doe'),
                $userData['ln'],
                'Last name should be lowercased and hashed with SHA256'
            );

            // Verify city is hashed
            $this->assertEquals(
                hash('sha256', 'newyork'),
                $userData['ct'],
                'City should be normalized and hashed with SHA256'
            );

            // Verify state is hashed
            $this->assertEquals(
                hash('sha256', 'ny'),
                $userData['st'],
                'State should be lowercased and hashed with SHA256'
            );

            // Verify zip code is hashed
            $this->assertEquals(
                hash('sha256', '10001'),
                $userData['zp'],
                'Zip code should be hashed with SHA256'
            );

            // Verify country is hashed
            $this->assertEquals(
                hash('sha256', 'us'),
                $userData['country'],
                'Country should be lowercased and hashed with SHA256'
            );

            return true;
        });
    }

    /**
     * Test that payload is built correctly with all required fields.
     *
     * @return void
     */
    public function test_payload_is_built_correctly(): void
    {
        $eventTime = time();
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: $eventTime,
            eventSourceUrl: 'https://example.com/checkout',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890',
            fbc: 'fb.1.1234567890.AbCdEfGhIjKlMnOpQrStUvWxYz',
            email: 'test@example.com',
            customData: ['value' => 99.99, 'currency' => 'USD']
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) use ($eventTime) {
            $body = json_decode($request->body(), true);

            // Verify top-level structure
            $this->assertArrayHasKey('data', $body);
            $this->assertIsArray($body['data']);
            $this->assertCount(1, $body['data']);

            $event = $body['data'][0];

            // Verify event name
            $this->assertEquals('Purchase', $event['event_name']);

            // Verify event time
            $this->assertEquals($eventTime, $event['event_time']);

            // Verify event source URL
            $this->assertEquals('https://example.com/checkout', $event['event_source_url']);

            // Verify action source
            $this->assertEquals('website', $event['action_source']);

            // Verify user data structure
            $this->assertArrayHasKey('user_data', $event);
            $userData = $event['user_data'];

            $this->assertEquals('192.168.1.1', $userData['client_ip_address']);
            $this->assertEquals('Mozilla/5.0', $userData['client_user_agent']);
            $this->assertEquals('fb.1.1234567890.1234567890', $userData['fbp']);
            $this->assertEquals('fb.1.1234567890.AbCdEfGhIjKlMnOpQrStUvWxYz', $userData['fbc']);

            // Verify custom data
            $this->assertArrayHasKey('custom_data', $event);
            $this->assertEquals(99.99, $event['custom_data']['value']);
            $this->assertEquals('USD', $event['custom_data']['currency']);

            return true;
        });
    }

    /**
     * Test that event data is formatted correctly for different event types.
     *
     * @return void
     */
    public function test_event_data_formatting_for_different_event_types(): void
    {
        $eventTypes = [
            FacebookEventType::PAGE_VIEW,
            FacebookEventType::VIEW_CONTENT,
            FacebookEventType::ADD_TO_CART,
            FacebookEventType::INITIATE_CHECKOUT,
            FacebookEventType::PURCHASE,
            FacebookEventType::LEAD,
            FacebookEventType::COMPLETE_REGISTRATION,
        ];

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        foreach ($eventTypes as $eventType) {
            $eventData = new FacebookEventData(
                eventName: $eventType,
                eventTime: time(),
                eventSourceUrl: 'https://example.com',
                userAgent: 'Mozilla/5.0',
                ipAddress: '192.168.1.1',
                fbp: 'fb.1.1234567890.1234567890'
            );

            $this->service->trackEvent($eventData);
        }

        Http::assertSentCount(count($eventTypes));

        $sentRequests = Http::recorded();
        foreach ($sentRequests as $index => [$request, $response]) {
            $body = json_decode($request->body(), true);
            $event = $body['data'][0];

            $this->assertEquals(
                $eventTypes[$index]->value,
                $event['event_name'],
                "Event name should match for {$eventTypes[$index]->value}"
            );
        }
    }

    /**
     * Test that test event code is included when configured.
     *
     * @return void
     */
    public function test_test_event_code_is_included_when_configured(): void
    {
        Config::set('facebook.test_event_code', 'TEST12345');

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890'
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);

            $this->assertArrayHasKey('test_event_code', $body);
            $this->assertEquals('TEST12345', $body['test_event_code']);

            return true;
        });
    }

    /**
     * Test that test event code is not included when not configured.
     *
     * @return void
     */
    public function test_test_event_code_is_not_included_when_not_configured(): void
    {
        Config::set('facebook.test_event_code', null);

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890'
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);

            $this->assertArrayNotHasKey('test_event_code', $body);

            return true;
        });
    }

    /**
     * Test that API request is sent to correct endpoint.
     *
     * @return void
     */
    public function test_api_request_is_sent_to_correct_endpoint(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890'
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $expectedUrl = 'https://graph.facebook.com/v18.0/test_pixel_id/events';
            $this->assertEquals($expectedUrl, $request->url());

            // Verify access token is sent as query parameter
            $this->assertStringContainsString('access_token=test_access_token', $request->url());

            return true;
        });
    }

    /**
     * Test error handling when API returns error response.
     *
     * @return void
     */
    public function test_error_handling_when_api_returns_error(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890'
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Facebook Conversion API error');

        $this->service->trackEvent($eventData);
    }

    /**
     * Test error handling when API request fails.
     *
     * @return void
     */
    public function test_error_handling_when_api_request_fails(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890'
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(null, 500),
        ]);

        $this->expectException(\Exception::class);

        $this->service->trackEvent($eventData);
    }

    /**
     * Test that optional user data fields are excluded when null.
     *
     * @return void
     */
    public function test_optional_user_data_fields_are_excluded_when_null(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890',
            email: null,
            phone: null,
            firstName: null,
            lastName: null,
            city: null,
            state: null,
            zipCode: null,
            country: null
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            $userData = $body['data'][0]['user_data'];

            $this->assertArrayNotHasKey('em', $userData);
            $this->assertArrayNotHasKey('ph', $userData);
            $this->assertArrayNotHasKey('fn', $userData);
            $this->assertArrayNotHasKey('ln', $userData);
            $this->assertArrayNotHasKey('ct', $userData);
            $this->assertArrayNotHasKey('st', $userData);
            $this->assertArrayNotHasKey('zp', $userData);
            $this->assertArrayNotHasKey('country', $userData);

            // Required fields should still be present
            $this->assertArrayHasKey('client_ip_address', $userData);
            $this->assertArrayHasKey('client_user_agent', $userData);
            $this->assertArrayHasKey('fbp', $userData);

            return true;
        });
    }

    /**
     * Test that custom data is properly formatted.
     *
     * @return void
     */
    public function test_custom_data_is_properly_formatted(): void
    {
        $customData = [
            'value' => 149.99,
            'currency' => 'USD',
            'content_name' => 'Product Name',
            'content_category' => 'Electronics',
            'content_ids' => ['prod_123', 'prod_456'],
            'content_type' => 'product',
            'num_items' => 2,
        ];

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890',
            customData: $customData
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) use ($customData) {
            $body = json_decode($request->body(), true);
            $sentCustomData = $body['data'][0]['custom_data'];

            $this->assertEquals($customData['value'], $sentCustomData['value']);
            $this->assertEquals($customData['currency'], $sentCustomData['currency']);
            $this->assertEquals($customData['content_name'], $sentCustomData['content_name']);
            $this->assertEquals($customData['content_category'], $sentCustomData['content_category']);
            $this->assertEquals($customData['content_ids'], $sentCustomData['content_ids']);
            $this->assertEquals($customData['content_type'], $sentCustomData['content_type']);
            $this->assertEquals($customData['num_items'], $sentCustomData['num_items']);

            return true;
        });
    }

    /**
     * Test that event ID is generated and included in payload.
     *
     * @return void
     */
    public function test_event_id_is_generated_and_included(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890'
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            $event = $body['data'][0];

            $this->assertArrayHasKey('event_id', $event);
            $this->assertIsString($event['event_id']);
            $this->assertNotEmpty($event['event_id']);

            return true;
        });
    }

    /**
     * Test that fbc parameter is properly included when provided.
     *
     * @return void
     */
    public function test_fbc_parameter_is_included_when_provided(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890',
            fbc: 'fb.1.1234567890.AbCdEfGhIjKlMnOpQrStUvWxYz'
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            $userData = $body['data'][0]['user_data'];

            $this->assertArrayHasKey('fbc', $userData);
            $this->assertEquals('fb.1.1234567890.AbCdEfGhIjKlMnOpQrStUvWxYz', $userData['fbc']);

            return true;
        });
    }

    /**
     * Test that fbc parameter is excluded when not provided.
     *
     * @return void
     */
    public function test_fbc_parameter_is_excluded_when_not_provided(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: time(),
            eventSourceUrl: 'https://example.com',
            userAgent: 'Mozilla/5.0',
            ipAddress: '192.168.1.1',
            fbp: 'fb.1.1234567890.1234567890',
            fbc: null
        );

        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->service->trackEvent($eventData);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            $userData = $body['data'][0]['user_data'];

            $this->assertArrayNotHasKey('fbc', $userData);

            return true;
        });
    }
}
