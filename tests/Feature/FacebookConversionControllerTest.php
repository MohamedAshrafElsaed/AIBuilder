<?php

namespace Tests\Feature;

use App\Enums\FacebookEventType;
use App\Events\FacebookConversionEvent;
use App\Models\FacebookConversionLog;
use App\Models\User;
use App\Services\FacebookConversionApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FacebookConversionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_send_event_successfully_dispatches_event(): void
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_id' => 'test-event-123',
                'event_time' => now()->timestamp,
                'event_source_url' => 'https://example.com/checkout',
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                    'phone' => '+1234567890',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                    'country' => 'US',
                    'external_id' => 'user-123',
                    'client_ip_address' => '192.168.1.1',
                    'client_user_agent' => 'Mozilla/5.0',
                    'fbc' => 'fb.1.1234567890.abcd',
                    'fbp' => 'fb.1.1234567890.1234567890',
                ],
                'custom_data' => [
                    'currency' => 'USD',
                    'value' => 99.99,
                    'content_name' => 'Test Product',
                    'content_category' => 'Electronics',
                    'content_ids' => ['prod-123'],
                    'content_type' => 'product',
                    'num_items' => 1,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Facebook conversion event queued successfully',
            ]);

        Event::assertDispatched(FacebookConversionEvent::class);
    }

    public function test_send_event_with_minimal_data(): void
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PAGE_VIEW->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        Event::assertDispatched(FacebookConversionEvent::class);
    }

    public function test_send_event_requires_authentication(): void
    {
        $response = $this->postJson('/api/facebook/conversion/send', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'action_source' => 'website',
            'user_data' => [
                'email' => 'test@example.com',
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_send_event_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'event_name',
                'event_time',
                'action_source',
                'user_data',
            ]);
    }

    public function test_send_event_validates_event_name_enum(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => 'InvalidEventName',
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_name']);
    }

    public function test_send_event_validates_action_source(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'invalid_source',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action_source']);
    }

    public function test_send_event_validates_user_data_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_data']);
    }

    public function test_send_event_validates_email_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'invalid-email',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_data.email']);
    }

    public function test_send_event_validates_custom_data_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
                'custom_data' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['custom_data']);
    }

    public function test_send_event_validates_custom_data_value_numeric(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
                'custom_data' => [
                    'value' => 'not-a-number',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['custom_data.value']);
    }

    public function test_send_event_validates_custom_data_currency_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
                'custom_data' => [
                    'currency' => 'INVALID',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['custom_data.currency']);
    }

    public function test_send_event_validates_event_source_url_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'event_source_url' => 'not-a-url',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_source_url']);
    }

    public function test_send_event_respects_rate_limiting(): void
    {
        // Make requests up to the rate limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/facebook/conversion/send', [
                    'event_name' => FacebookEventType::PAGE_VIEW->value,
                    'event_time' => now()->timestamp,
                    'action_source' => 'website',
                    'user_data' => [
                        'email' => 'test@example.com',
                    ],
                ]);

            if ($i < 60) {
                $response->assertStatus(200);
            }
        }

        // Next request should be rate limited
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PAGE_VIEW->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(429);
    }

    public function test_send_event_direct_successfully_sends_to_facebook(): void
    {
        Http::fake([
            'https://graph.facebook.com/*/events' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test-trace-id',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send-direct', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_id' => 'test-event-123',
                'event_time' => now()->timestamp,
                'event_source_url' => 'https://example.com/checkout',
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                    'phone' => '+1234567890',
                ],
                'custom_data' => [
                    'currency' => 'USD',
                    'value' => 99.99,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Facebook conversion event sent successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'events_received',
                    'fbtrace_id',
                ],
            ]);

        $this->assertDatabaseHas('facebook_conversion_logs', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_id' => 'test-event-123',
            'status' => 'success',
        ]);
    }

    public function test_send_event_direct_handles_facebook_api_error(): void
    {
        Http::fake([
            'https://graph.facebook.com/*/events' => Http::response([
                'error' => [
                    'message' => 'Invalid access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 400),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send-direct', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to send Facebook conversion event',
            ]);

        $this->assertDatabaseHas('facebook_conversion_logs', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'status' => 'failed',
        ]);
    }

    public function test_send_event_direct_requires_authentication(): void
    {
        $response = $this->postJson('/api/facebook/conversion/send-direct', [
            'event_name' => FacebookEventType::PURCHASE->value,
            'event_time' => now()->timestamp,
            'action_source' => 'website',
            'user_data' => [
                'email' => 'test@example.com',
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_send_event_direct_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send-direct', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'event_name',
                'event_time',
                'action_source',
                'user_data',
            ]);
    }

    public function test_send_event_direct_respects_rate_limiting(): void
    {
        Http::fake([
            'https://graph.facebook.com/*/events' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'test-trace-id',
            ], 200),
        ]);

        // Make requests up to the rate limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/facebook/conversion/send-direct', [
                    'event_name' => FacebookEventType::PAGE_VIEW->value,
                    'event_time' => now()->timestamp,
                    'action_source' => 'website',
                    'user_data' => [
                        'email' => 'test@example.com',
                    ],
                ]);

            if ($i < 60) {
                $response->assertStatus(200);
            }
        }

        // Next request should be rate limited
        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send-direct', [
                'event_name' => FacebookEventType::PAGE_VIEW->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(429);
    }

    public function test_send_event_with_all_user_data_fields(): void
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                    'phone' => '+1234567890',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'date_of_birth' => '19900101',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                    'country' => 'US',
                    'external_id' => 'user-123',
                    'client_ip_address' => '192.168.1.1',
                    'client_user_agent' => 'Mozilla/5.0',
                    'fbc' => 'fb.1.1234567890.abcd',
                    'fbp' => 'fb.1.1234567890.1234567890',
                    'subscription_id' => 'sub-123',
                    'fb_login_id' => 'fb-login-123',
                    'lead_id' => 'lead-123',
                ],
            ]);

        $response->assertStatus(200);
        Event::assertDispatched(FacebookConversionEvent::class);
    }

    public function test_send_event_with_all_custom_data_fields(): void
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
                'custom_data' => [
                    'currency' => 'USD',
                    'value' => 99.99,
                    'content_name' => 'Test Product',
                    'content_category' => 'Electronics',
                    'content_ids' => ['prod-123', 'prod-456'],
                    'content_type' => 'product',
                    'order_id' => 'order-123',
                    'predicted_ltv' => 500.00,
                    'num_items' => 2,
                    'search_string' => 'test search',
                    'status' => 'completed',
                ],
            ]);

        $response->assertStatus(200);
        Event::assertDispatched(FacebookConversionEvent::class);
    }

    public function test_send_event_with_test_event_code(): void
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/api/facebook/conversion/send', [
                'event_name' => FacebookEventType::PURCHASE->value,
                'event_time' => now()->timestamp,
                'action_source' => 'website',
                'test_event_code' => 'TEST12345',
                'user_data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(200);
        Event::assertDispatched(FacebookConversionEvent::class);
    }
}
