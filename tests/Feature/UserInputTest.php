<?php

namespace Tests\Feature;

use App\Models\UserInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for UserInput functionality.
 *
 * Tests form display, validation, submission, and database storage.
 */
class UserInputTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the create form is displayed correctly.
     *
     * @return void
     */
    public function test_create_form_is_displayed(): void
    {
        $response = $this->get(route('user-inputs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('user-inputs.create');
        $response->assertSee('name="user_input"', false);
    }

    /**
     * Test successful form submission with valid data.
     *
     * @return void
     */
    public function test_user_input_can_be_stored_with_valid_data(): void
    {
        $data = [
            'user_input' => 'This is a test user input message.',
        ];

        $response = $this->post(route('user-inputs.store'), $data);

        $response->assertRedirect(route('user-inputs.success'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_inputs', [
            'user_input' => 'This is a test user input message.',
        ]);
    }

    /**
     * Test that user_input field is required.
     *
     * @return void
     */
    public function test_user_input_is_required(): void
    {
        $data = [
            'user_input' => '',
        ];

        $response = $this->post(route('user-inputs.store'), $data);

        $response->assertSessionHasErrors('user_input');
        $this->assertDatabaseCount('user_inputs', 0);
    }

    /**
     * Test that user_input field has maximum length validation.
     *
     * @return void
     */
    public function test_user_input_has_maximum_length(): void
    {
        $data = [
            'user_input' => str_repeat('a', 1001),
        ];

        $response = $this->post(route('user-inputs.store'), $data);

        $response->assertSessionHasErrors('user_input');
        $this->assertDatabaseCount('user_inputs', 0);
    }

    /**
     * Test that success page is displayed after successful submission.
     *
     * @return void
     */
    public function test_success_page_is_displayed(): void
    {
        $userInput = UserInput::create([
            'user_input' => 'Test message for success page.',
        ]);

        $response = $this->get(route('user-inputs.success'));

        $response->assertStatus(200);
        $response->assertViewIs('user-inputs.success');
    }

    /**
     * Test that multiple submissions can be stored.
     *
     * @return void
     */
    public function test_multiple_user_inputs_can_be_stored(): void
    {
        $firstData = [
            'user_input' => 'First message.',
        ];

        $secondData = [
            'user_input' => 'Second message.',
        ];

        $this->post(route('user-inputs.store'), $firstData);
        $this->post(route('user-inputs.store'), $secondData);

        $this->assertDatabaseCount('user_inputs', 2);
        $this->assertDatabaseHas('user_inputs', $firstData);
        $this->assertDatabaseHas('user_inputs', $secondData);
    }
}
