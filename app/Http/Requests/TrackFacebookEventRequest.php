<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for tracking Facebook Conversion API events.
 */
class TrackFacebookEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'event_name' => ['required', 'string'],
            'event_time' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'string'],
            'event_source_url' => ['nullable', 'url'],
            'user_data' => ['nullable', 'array'],
            'user_data.email' => ['nullable', 'email'],
            'user_data.phone' => ['nullable', 'string'],
            'custom_data' => ['nullable', 'array'],
            'custom_data.value' => ['nullable', 'numeric'],
            'custom_data.currency' => ['nullable', 'string', 'size:3'],
            'action_source' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_name.required' => 'The event name is required.',
            'event_name.string' => 'The event name must be a string.',
            'event_time.integer' => 'The event time must be a valid Unix timestamp.',
            'event_id.string' => 'The event ID must be a string.',
            'event_source_url.url' => 'The event source URL must be a valid URL.',
            'user_data.array' => 'The user data must be an array.',
            'user_data.email.email' => 'The user email must be a valid email address.',
            'user_data.phone.string' => 'The user phone must be a string.',
            'custom_data.array' => 'The custom data must be an array.',
            'custom_data.value.numeric' => 'The custom data value must be a number.',
            'custom_data.currency.string' => 'The currency must be a string.',
            'custom_data.currency.size' => 'The currency must be exactly 3 characters (ISO 4217 format).',
            'action_source.string' => 'The action source must be a string.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'event_name' => 'event name',
            'event_time' => 'event time',
            'event_id' => 'event ID',
            'event_source_url' => 'event source URL',
            'user_data' => 'user data',
            'user_data.email' => 'user email',
            'user_data.phone' => 'user phone',
            'custom_data' => 'custom data',
            'custom_data.value' => 'value',
            'custom_data.currency' => 'currency',
            'action_source' => 'action source',
        ];
    }
}
