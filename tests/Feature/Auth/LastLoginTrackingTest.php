<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Test last login tracking functionality.
 */
class LastLoginTrackingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that last_login_at is null for a newly created user.
     *
     * @return void
     */
    public function test_last_login_at_is_null_for_new_user(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->last_login_at);
    }

    /**
     * Test that last_login_at is updated on first login.
     *
     * @return void
     */
    public function test_last_login_at_is_updated_on_first_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertNull($user->last_login_at);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->last_login_at->timestamp,
            5
        );
    }

    /**
     * Test that last_login_at is updated on subsequent logins.
     *
     * @return void
     */
    public function test_last_login_at_is_updated_on_subsequent_logins(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'last_login_at' => now()->subDays(7),
        ]);

        $previousLoginAt = $user->last_login_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertTrue($user->last_login_at->isAfter($previousLoginAt));
    }

    /**
     * Test that last_login_at is not updated on failed login.
     *
     * @return void
     */
    public function test_last_login_at_is_not_updated_on_failed_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertNull($user->last_login_at);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();

        $user->refresh();
        $this->assertNull($user->last_login_at);
    }

    /**
     * Test that last_login_at is updated when using actingAs.
     *
     * @return void
     */
    public function test_last_login_at_is_updated_with_acting_as(): void
    {
        Event::fake();

        $user = User::factory()->create();

        $this->assertNull($user->last_login_at);

        // When using actingAs in tests, the Login event is not fired
        // This test verifies the behavior when manually triggering the event
        event(new \Illuminate\Auth\Events\Login('web', $user, false));

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    /**
     * Test that last_login_at timestamp is recent after login.
     *
     * @return void
     */
    public function test_last_login_at_timestamp_is_recent(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $beforeLogin = now();

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $afterLogin = now();

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertTrue($user->last_login_at->between($beforeLogin, $afterLogin));
    }

    /**
     * Test that multiple users can login and have their own last_login_at.
     *
     * @return void
     */
    public function test_multiple_users_have_independent_last_login_tracking(): void
    {
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        // User 1 logs in
        $this->post('/login', [
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $user1->refresh();
        $user2->refresh();

        $this->assertNotNull($user1->last_login_at);
        $this->assertNull($user2->last_login_at);

        // Logout user 1
        $this->post('/logout');

        sleep(1);

        // User 2 logs in
        $this->post('/login', [
            'email' => 'user2@example.com',
            'password' => 'password',
        ]);

        $user1->refresh();
        $user2->refresh();

        $this->assertNotNull($user1->last_login_at);
        $this->assertNotNull($user2->last_login_at);
        $this->assertTrue($user2->last_login_at->isAfter($user1->last_login_at));
    }
}
