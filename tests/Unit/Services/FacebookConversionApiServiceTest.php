<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\FacebookEventData;
use App\DataTransferObjects\FacebookUserData;
use App\Enums\FacebookEventType;
use App\Exceptions\FacebookConversionApiException;
use App\Services\FacebookConversionApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookConversionApiServiceTest extends TestCase
{
    private FacebookConversionApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'facebook.pixel_id' => 'test_pixel_id',
            'facebook.access_token' => 'test_access_token',
            'facebook.api_version' => 'v18.0',
        ]);

        $this->service = new FacebookConversionApiService();
    }

    public function test_send_event_successfully(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test_trace_id',
            ], 200),
        ]);

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com/checkout',
            actionSource: 'website',
            eventId: 'test_event_123',
            value: 99.99,
            currency: 'USD'
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            phone: '+1234567890',
            firstName: 'John',
            lastName: 'Doe',
            city: 'New York',
            state: 'NY',
            zip: '10001',
            country: 'US',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $response = $this->service->sendEvent($eventData, $userData);

        $this->assertTrue($response['success']);
        $this->assertEquals(1, $response['events_received']);
        $this->assertEquals('test_trace_id', $response['fbtrace_id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://graph.facebook.com/v18.0/test_pixel_id/events' &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request['access_token'] === 'test_access_token';
        });
    }

    public function test_send_event_with_custom_data(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test_trace_id',
            ], 200),
        ]);

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::ADD_TO_CART,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com/product',
            actionSource: 'website',
            eventId: 'test_event_456',
            value: 49.99,
            currency: 'USD',
            customData: [
                'content_ids' => ['product_123'],
                'content_type' => 'product',
                'content_name' => 'Test Product',
            ]
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $response = $this->service->sendEvent($eventData, $userData);

        $this->assertTrue($response['success']);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            $event = $data['data'][0];

            return isset($event['custom_data']['content_ids']) &&
                   $event['custom_data']['content_ids'] === ['product_123'] &&
                   $event['custom_data']['content_name'] === 'Test Product';
        });
    }

    public function test_send_event_throws_exception_on_api_error(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com',
            actionSource: 'website'
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $this->expectException(FacebookConversionApiException::class);
        $this->expectExceptionMessage('Facebook Conversion API Error: Invalid OAuth access token');

        $this->service->sendEvent($eventData, $userData);
    }

    public function test_send_event_throws_exception_on_network_error(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(null, 500),
        ]);

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com',
            actionSource: 'website'
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $this->expectException(FacebookConversionApiException::class);

        $this->service->sendEvent($eventData, $userData);
    }

    public function test_hash_user_data_hashes_pii_fields(): void
    {
        $userData = new FacebookUserData(
            email: 'test@example.com',
            phone: '+1234567890',
            firstName: 'John',
            lastName: 'Doe',
            city: 'New York',
            state: 'NY',
            zip: '10001',
            country: 'US',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $hashedData = $this->service->hashUserData($userData);

        // Email should be hashed
        $this->assertNotEquals('test@example.com', $hashedData['em']);
        $this->assertEquals(hash('sha256', strtolower(trim('test@example.com'))), $hashedData['em']);

        // Phone should be hashed
        $this->assertNotEquals('+1234567890', $hashedData['ph']);
        $this->assertEquals(hash('sha256', preg_replace('/[^0-9]/', '', '+1234567890')), $hashedData['ph']);

        // First name should be hashed
        $this->assertEquals(hash('sha256', strtolower(trim('John'))), $hashedData['fn']);

        // Last name should be hashed
        $this->assertEquals(hash('sha256', strtolower(trim('Doe'))), $hashedData['ln']);

        // City should be hashed
        $this->assertEquals(hash('sha256', strtolower(trim('New York'))), $hashedData['ct']);

        // State should be hashed
        $this->assertEquals(hash('sha256', strtolower(trim('NY'))), $hashedData['st']);

        // Zip should be hashed
        $this->assertEquals(hash('sha256', strtolower(trim('10001'))), $hashedData['zp']);

        // Country should be hashed
        $this->assertEquals(hash('sha256', strtolower(trim('US'))), $hashedData['country']);

        // IP and User Agent should not be hashed
        $this->assertEquals('192.168.1.1', $hashedData['client_ip_address']);
        $this->assertEquals('Mozilla/5.0', $hashedData['client_user_agent']);
    }

    public function test_hash_user_data_handles_null_values(): void
    {
        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $hashedData = $this->service->hashUserData($userData);

        $this->assertArrayHasKey('em', $hashedData);
        $this->assertArrayHasKey('client_ip_address', $hashedData);
        $this->assertArrayHasKey('client_user_agent', $hashedData);
        $this->assertArrayNotHasKey('ph', $hashedData);
        $this->assertArrayNotHasKey('fn', $hashedData);
        $this->assertArrayNotHasKey('ln', $hashedData);
    }

    public function test_build_event_payload_creates_correct_structure(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: 1234567890,
            eventSourceUrl: 'https://example.com/checkout',
            actionSource: 'website',
            eventId: 'test_event_123',
            value: 99.99,
            currency: 'USD',
            customData: ['order_id' => 'order_123']
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $payload = $this->service->buildEventPayload($eventData, $userData);

        $this->assertArrayHasKey('data', $payload);
        $this->assertIsArray($payload['data']);
        $this->assertCount(1, $payload['data']);

        $event = $payload['data'][0];

        $this->assertEquals('Purchase', $event['event_name']);
        $this->assertEquals(1234567890, $event['event_time']);
        $this->assertEquals('https://example.com/checkout', $event['event_source_url']);
        $this->assertEquals('website', $event['action_source']);
        $this->assertEquals('test_event_123', $event['event_id']);

        $this->assertArrayHasKey('user_data', $event);
        $this->assertArrayHasKey('custom_data', $event);

        $this->assertEquals(99.99, $event['custom_data']['value']);
        $this->assertEquals('USD', $event['custom_data']['currency']);
        $this->assertEquals('order_123', $event['custom_data']['order_id']);
    }

    public function test_build_event_payload_without_optional_fields(): void
    {
        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PAGE_VIEW,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com',
            actionSource: 'website'
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $payload = $this->service->buildEventPayload($eventData, $userData);

        $event = $payload['data'][0];

        $this->assertArrayNotHasKey('event_id', $event);
        $this->assertArrayHasKey('custom_data', $event);
        $this->assertArrayNotHasKey('value', $event['custom_data']);
        $this->assertArrayNotHasKey('currency', $event['custom_data']);
    }

    public function test_send_event_with_test_event_code(): void
    {
        config(['facebook.test_event_code' => 'TEST12345']);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test_trace_id',
            ], 200),
        ]);

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com',
            actionSource: 'website'
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $this->service->sendEvent($eventData, $userData);

        Http::assertSent(function (Request $request) {
            return $request['test_event_code'] === 'TEST12345';
        });
    }

    public function test_send_event_handles_facebook_warnings(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [
                    'Some warning message',
                ],
                'fbtrace_id' => 'test_trace_id',
            ], 200),
        ]);

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com',
            actionSource: 'website'
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $response = $this->service->sendEvent($eventData, $userData);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('messages', $response);
        $this->assertCount(1, $response['messages']);
    }

    public function test_hash_user_data_normalizes_email(): void
    {
        $userData = new FacebookUserData(
            email: '  TEST@EXAMPLE.COM  ',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $hashedData = $this->service->hashUserData($userData);

        // Should be normalized to lowercase and trimmed before hashing
        $expectedHash = hash('sha256', 'test@example.com');
        $this->assertEquals($expectedHash, $hashedData['em']);
    }

    public function test_hash_user_data_normalizes_phone(): void
    {
        $userData = new FacebookUserData(
            email: 'test@example.com',
            phone: '+1 (234) 567-8900',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $hashedData = $this->service->hashUserData($userData);

        // Should remove all non-numeric characters before hashing
        $expectedHash = hash('sha256', '12345678900');
        $this->assertEquals($expectedHash, $hashedData['ph']);
    }

    public function test_send_event_includes_all_custom_data_fields(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test_trace_id',
            ], 200),
        ]);

        $customData = [
            'content_ids' => ['product_1', 'product_2'],
            'content_type' => 'product',
            'content_name' => 'Test Products',
            'content_category' => 'Electronics',
            'num_items' => 2,
            'predicted_ltv' => 500.00,
            'search_string' => 'test search',
            'status' => 'completed',
        ];

        $eventData = new FacebookEventData(
            eventName: FacebookEventType::PURCHASE,
            eventTime: now()->timestamp,
            eventSourceUrl: 'https://example.com',
            actionSource: 'website',
            value: 99.99,
            currency: 'USD',
            customData: $customData
        );

        $userData = new FacebookUserData(
            email: 'test@example.com',
            clientIpAddress: '192.168.1.1',
            clientUserAgent: 'Mozilla/5.0'
        );

        $this->service->sendEvent($eventData, $userData);

        Http::assertSent(function (Request $request) use ($customData) {
            $data = $request->data();
            $event = $data['data'][0];
            $sentCustomData = $event['custom_data'];

            foreach ($customData as $key => $value) {
                if (!isset($sentCustomData[$key]) || $sentCustomData[$key] !== $value) {
                    return false;
                }
            }

            return true;
        });
    }
}
