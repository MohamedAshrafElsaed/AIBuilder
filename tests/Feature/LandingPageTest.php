<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    /**
     * Test that the landing page returns a successful response.
     */
    public function test_landing_page_returns_successful_response(): void
    {
        $response = $this->get('/landing');

        $response->assertStatus(200);
    }

    /**
     * Test that the landing page uses the correct Inertia component.
     */
    public function test_landing_page_uses_correct_inertia_component(): void
    {
        $response = $this->get('/landing');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Landing/Index')
        );
    }
}
