<?php

namespace App\Http\Requests;

use App\Enums\FacebookEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendFacebookEventRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_name' => [
                'required',
                'string',
                Rule::enum(FacebookEventType::class),
            ],
            'event_time' => [
                'required',
                'integer',
                'min:1',
            ],
            'event_source_url' => [
                'required',
                'url',
                'max:2048',
            ],
            'user_data' => [
                'required',
                'array',
            ],
            'user_data.email' => [
                'nullable',
                'email',
            ],
            'user_data.phone' => [
                'nullable',
                'string',
            ],
            'user_data.first_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'user_data.last_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'user_data.city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'user_data.state' => [
                'nullable',
                'string',
                'max:255',
            ],
            'user_data.zip' => [
                'nullable',
                'string',
                'max:20',
            ],
            'user_data.country' => [
                'nullable',
                'string',
                'size:2',
            ],
            'user_data.external_id' => [
                'nullable',
                'string',
            ],
            'user_data.client_ip_address' => [
                'nullable',
                'ip',
            ],
            'user_data.client_user_agent' => [
                'nullable',
                'string',
            ],
            'user_data.fbc' => [
                'nullable',
                'string',
            ],
            'user_data.fbp' => [
                'nullable',
                'string',
            ],
            'custom_data' => [
                'nullable',
                'array',
            ],
            'custom_data.value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'custom_data.currency' => [
                'nullable',
                'string',
                'size:3',
            ],
            'custom_data.content_name' => [
                'nullable',
                'string',
            ],
            'custom_data.content_category' => [
                'nullable',
                'string',
            ],
            'custom_data.content_ids' => [
                'nullable',
                'array',
            ],
            'custom_data.content_ids.*' => [
                'string',
            ],
            'custom_data.content_type' => [
                'nullable',
                'string',
            ],
            'custom_data.num_items' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'custom_data.predicted_ltv' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'custom_data.status' => [
                'nullable',
                'string',
            ],
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
            'event_name.enum' => 'The event name must be a valid Facebook event type.',
            'event_time.required' => 'The event time is required.',
            'event_time.integer' => 'The event time must be a Unix timestamp.',
            'event_source_url.required' => 'The event source URL is required.',
            'event_source_url.url' => 'The event source URL must be a valid URL.',
            'user_data.required' => 'User data is required.',
            'user_data.array' => 'User data must be an array.',
            'user_data.email.email' => 'The email must be a valid email address.',
            'user_data.country.size' => 'The country must be a 2-letter ISO code.',
            'user_data.client_ip_address.ip' => 'The client IP address must be a valid IP address.',
            'custom_data.currency.size' => 'The currency must be a 3-letter ISO code.',
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
            'event_source_url' => 'event source URL',
            'user_data.email' => 'email',
            'user_data.phone' => 'phone',
            'user_data.first_name' => 'first name',
            'user_data.last_name' => 'last name',
            'user_data.city' => 'city',
            'user_data.state' => 'state',
            'user_data.zip' => 'zip code',
            'user_data.country' => 'country',
            'user_data.external_id' => 'external ID',
            'user_data.client_ip_address' => 'client IP address',
            'user_data.client_user_agent' => 'client user agent',
            'custom_data.value' => 'value',
            'custom_data.currency' => 'currency',
            'custom_data.content_name' => 'content name',
            'custom_data.content_category' => 'content category',
            'custom_data.content_ids' => 'content IDs',
            'custom_data.content_type' => 'content type',
            'custom_data.num_items' => 'number of items',
            'custom_data.predicted_ltv' => 'predicted lifetime value',
            'custom_data.status' => 'status',
        ];
    }
}
