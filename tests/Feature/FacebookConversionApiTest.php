<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FacebookEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for Facebook Conversion API integration.
 */
class FacebookConversionApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Facebook configuration for testing
        Config::set('facebook.pixel_id', 'test_pixel_123');
        Config::set('facebook.access_token', 'test_token_abc');
        Config::set('facebook.api_version', 'v18.0');
    }

    /**
     * Test successful single event tracking.
     *
     * @return void
     */
    public function test_successful_single_event_tracking(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test_trace_123',
            ], 200),
        ]);

        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'ph' => hash('sha256', '+1234567890'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'custom_data' => [
                'currency' => 'USD',
                'value' => 99.99,
            ],
            'event_source_url' => 'https://example.com/checkout',
            'action_source' => 'website',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'events_received' => 1,
            ])
            ->assertJsonStructure([
                'success',
                'events_received',
                'fbtrace_id',
            ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://graph.facebook.com/v18.0/test_pixel_123/events' &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request['access_token'] === 'test_token_abc' &&
                   count($request['data']) === 1 &&
                   $request['data'][0]['event_name'] === FacebookEventType::PURCHASE->value;
        });
    }

    /**
     * Test successful batch event tracking.
     *
     * @return void
     */
    public function test_successful_batch_event_tracking(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 3,
                'messages' => [],
                'fbtrace_id' => 'test_trace_456',
            ], 200),
        ]);

        $events = [
            [
                'event_name' => FacebookEventType::VIEW_CONTENT->value,
                'event_time' => now()->timestamp,
                'user_data' => [
                    'em' => hash('sha256', 'user1@example.com'),
                    'client_ip_address' => '127.0.0.1',
                    'client_user_agent' => 'Mozilla/5.0',
                ],
                'custom_data' => [
                    'content_name' => 'Product Page',
                ],
                'event_source_url' => 'https://example.com/product/1',
                'action_source' => 'website',
            ],
            [
                'event_name' => FacebookEventType::ADD_TO_CART->value,
                'event_time' => now()->timestamp,
                'user_data' => [
                    'em' => hash('sha256', 'user2@example.com'),
                    'client_ip_address' => '127.0.0.2',
                    'client_user_agent' => 'Mozilla/5.0',
                ],
                'custom_data' => [
                    'currency' => 'USD',
                    'value' => 49.99,
                ],
                'event_source_url' => 'https://example.com/cart',
                'action_source' => 'website',
            ],
            [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'user_data' => [
                    'em' => hash('sha256', 'user3@example.com'),
                    'client_ip_address' => '127.0.0.3',
                    'client_user_agent' => 'Mozilla/5.0',
                ],
                'custom_data' => [
                    'currency' => 'USD',
                    'value' => 149.99,
                ],
                'event_source_url' => 'https://example.com/success',
                'action_source' => 'website',
            ],
        ];

        $response = $this->postJson('/api/facebook/track-batch', [
            'events' => $events,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'events_received' => 3,
            ])
            ->assertJsonStructure([
                'success',
                'events_received',
                'fbtrace_id',
            ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://graph.facebook.com/v18.0/test_pixel_123/events' &&
                   count($request['data']) === 3;
        });
    }

    /**
     * Test validation error for missing required fields.
     *
     * @return void
     */
    public function test_validation_error_missing_required_fields(): void
    {
        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            // Missing event_time, user_data, action_source
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_time', 'user_data', 'action_source']);
    }

    /**
     * Test validation error for invalid event name.
     *
     * @return void
     */
    public function test_validation_error_invalid_event_name(): void
    {
        $response = $this->postJson('/api/facebook/track', [
            'event_name' => 'InvalidEventName',
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_name']);
    }

    /**
     * Test validation error for invalid event time.
     *
     * @return void
     */
    public function test_validation_error_invalid_event_time(): void
    {
        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => 'invalid_timestamp',
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_time']);
    }

    /**
     * Test validation error for missing user data fields.
     *
     * @return void
     */
    public function test_validation_error_missing_user_data_fields(): void
    {
        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                // Missing required fields
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_data.client_ip_address',
                'user_data.client_user_agent',
            ]);
    }

    /**
     * Test validation error for invalid action source.
     *
     * @return void
     */
    public function test_validation_error_invalid_action_source(): void
    {
        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'invalid_source',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action_source']);
    }

    /**
     * Test validation error for batch events with invalid structure.
     *
     * @return void
     */
    public function test_validation_error_batch_events_invalid_structure(): void
    {
        $response = $this->postJson('/api/facebook/track-batch', [
            'events' => [
                [
                    'event_name' => 'InvalidEvent',
                    // Missing required fields
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'events.0.event_name',
                'events.0.event_time',
                'events.0.user_data',
                'events.0.action_source',
            ]);
    }

    /**
     * Test validation error for empty batch events array.
     *
     * @return void
     */
    public function test_validation_error_empty_batch_events(): void
    {
        $response = $this->postJson('/api/facebook/track-batch', [
            'events' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events']);
    }

    /**
     * Test invalid credentials handling.
     *
     * @return void
     */
    public function test_invalid_credentials_handling(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'fbtrace_id' => 'error_trace_789',
                ],
            ], 401),
        ]);

        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * Test Facebook API error handling.
     *
     * @return void
     */
    public function test_facebook_api_error_handling(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'An unknown error occurred.',
                    'type' => 'FacebookApiException',
                    'code' => 1,
                    'fbtrace_id' => 'error_trace_999',
                ],
            ], 500),
        ]);

        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test API endpoint accessibility without authentication.
     *
     * @return void
     */
    public function test_api_endpoint_accessibility(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test_trace_access',
            ], 200),
        ]);

        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PAGE_VIEW->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test missing Facebook configuration.
     *
     * @return void
     */
    public function test_missing_facebook_configuration(): void
    {
        Config::set('facebook.pixel_id', null);
        Config::set('facebook.access_token', null);

        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test event with all optional fields.
     *
     * @return void
     */
    public function test_event_with_all_optional_fields(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test_trace_full',
            ], 200),
        ]);

        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'event_id' => 'unique_event_123',
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'ph' => hash('sha256', '+1234567890'),
                'fn' => hash('sha256', 'John'),
                'ln' => hash('sha256', 'Doe'),
                'ct' => hash('sha256', 'New York'),
                'st' => hash('sha256', 'NY'),
                'zp' => hash('sha256', '10001'),
                'country' => hash('sha256', 'US'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
                'fbc' => 'fb.1.1234567890.abcdef',
                'fbp' => 'fb.1.1234567890.123456789',
            ],
            'custom_data' => [
                'currency' => 'USD',
                'value' => 199.99,
                'content_name' => 'Premium Product',
                'content_category' => 'Electronics',
                'content_ids' => ['prod_123', 'prod_456'],
                'content_type' => 'product',
                'num_items' => 2,
            ],
            'event_source_url' => 'https://example.com/checkout/success',
            'action_source' => 'website',
            'opt_out' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'events_received' => 1,
            ]);

        Http::assertSent(function (Request $request) {
            $eventData = $request['data'][0];
            return isset($eventData['event_id']) &&
                   isset($eventData['user_data']['fn']) &&
                   isset($eventData['custom_data']['content_name']) &&
                   $eventData['opt_out'] === false;
        });
    }

    /**
     * Test network timeout handling.
     *
     * @return void
     */
    public function test_network_timeout_handling(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        });

        $response = $this->postJson('/api/facebook/track', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'user_data' => [
                'em' => hash('sha256', 'test@example.com'),
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Mozilla/5.0',
            ],
            'action_source' => 'website',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test batch events exceeding maximum limit.
     *
     * @return void
     */
    public function test_batch_events_exceeding_maximum_limit(): void
    {
        $events = [];
        for ($i = 0; $i < 1001; $i++) {
            $events[] = [
                'event_name' => FacebookEventType::PAGE_VIEW->value,
                'event_time' => now()->timestamp,
                'user_data' => [
                    'em' => hash('sha256', "user{$i}@example.com"),
                    'client_ip_address' => '127.0.0.1',
                    'client_user_agent' => 'Mozilla/5.0',
                ],
                'action_source' => 'website',
            ];
        }

        $response = $this->postJson('/api/facebook/track-batch', [
            'events' => $events,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events']);
    }
}
