<?php

namespace Tests\Unit\Listeners;

use App\Listeners\UpdateUserLastLogin;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * Class UpdateUserLastLoginTest
 *
 * Unit tests for the UpdateUserLastLogin listener.
 *
 * @package Tests\Unit\Listeners
 */
class UpdateUserLastLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the listener updates the user's last_login_at timestamp.
     *
     * @return void
     */
    public function test_it_updates_user_last_login_at_timestamp(): void
    {
        // Arrange
        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        $event = new Login('web', $user, false);
        $listener = new UpdateUserLastLogin();

        // Act
        $listener->handle($event);

        // Assert
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->last_login_at);
    }

    /**
     * Test that the listener updates the timestamp on subsequent logins.
     *
     * @return void
     */
    public function test_it_updates_timestamp_on_subsequent_logins(): void
    {
        // Arrange
        $initialLoginTime = now()->subDays(7);
        $user = User::factory()->create([
            'last_login_at' => $initialLoginTime,
        ]);

        Date::setTestNow(now());

        $event = new Login('web', $user, false);
        $listener = new UpdateUserLastLogin();

        // Act
        $listener->handle($event);

        // Assert
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotEquals($initialLoginTime, $user->last_login_at);
        $this->assertTrue($user->last_login_at->isAfter($initialLoginTime));
    }

    /**
     * Test that the listener sets the correct timestamp.
     *
     * @return void
     */
    public function test_it_sets_correct_timestamp(): void
    {
        // Arrange
        $fixedTime = now();
        Date::setTestNow($fixedTime);

        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        $event = new Login('web', $user, false);
        $listener = new UpdateUserLastLogin();

        // Act
        $listener->handle($event);

        // Assert
        $user->refresh();
        $this->assertTrue($user->last_login_at->equalTo($fixedTime));
    }

    /**
     * Test that the listener works with different guard types.
     *
     * @return void
     */
    public function test_it_works_with_different_guards(): void
    {
        // Arrange
        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        $guards = ['web', 'api', 'admin'];
        $listener = new UpdateUserLastLogin();

        foreach ($guards as $guard) {
            // Act
            $event = new Login($guard, $user, false);
            $listener->handle($event);

            // Assert
            $user->refresh();
            $this->assertNotNull($user->last_login_at);
        }
    }

    /**
     * Test that the listener works with remember me option.
     *
     * @return void
     */
    public function test_it_works_with_remember_me_option(): void
    {
        // Arrange
        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        $event = new Login('web', $user, true);
        $listener = new UpdateUserLastLogin();

        // Act
        $listener->handle($event);

        // Assert
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    /**
     * Test that the listener only updates the last_login_at field.
     *
     * @return void
     */
    public function test_it_only_updates_last_login_at_field(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'last_login_at' => null,
        ]);

        $originalUpdatedAt = $user->updated_at;

        $event = new Login('web', $user, false);
        $listener = new UpdateUserLastLogin();

        // Act
        $listener->handle($event);

        // Assert
        $user->refresh();
        $this->assertEquals('Original Name', $user->name);
        $this->assertEquals('original@example.com', $user->email);
        $this->assertNotNull($user->last_login_at);
    }

    /**
     * Test that the listener handles multiple rapid logins.
     *
     * @return void
     */
    public function test_it_handles_multiple_rapid_logins(): void
    {
        // Arrange
        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        $listener = new UpdateUserLastLogin();
        $timestamps = [];

        // Act
        for ($i = 0; $i < 3; $i++) {
            $event = new Login('web', $user, false);
            $listener->handle($event);
            $user->refresh();
            $timestamps[] = $user->last_login_at->copy();

            // Small delay to ensure different timestamps
            usleep(10000); // 10ms
        }

        // Assert
        $this->assertCount(3, $timestamps);
        $this->assertTrue($timestamps[1]->greaterThanOrEqualTo($timestamps[0]));
        $this->assertTrue($timestamps[2]->greaterThanOrEqualTo($timestamps[1]));
    }

    /**
     * Clean up after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }
}
