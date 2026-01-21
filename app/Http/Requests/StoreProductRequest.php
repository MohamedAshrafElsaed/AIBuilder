<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for storing a new product.
 */
class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'name' => 'product name',
            'price' => 'product price',
            'category_id' => 'category',
            'description' => 'product description',
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
            'name.required' => 'The product name is required.',
            'name.max' => 'The product name must not exceed 255 characters.',
            'price.required' => 'The product price is required.',
            'price.numeric' => 'The product price must be a valid number.',
            'price.min' => 'The product price must be at least 0.',
            'category_id.required' => 'Please select a category for this product.',
            'category_id.exists' => 'The selected category does not exist.',
            'description.max' => 'The product description must not exceed 1000 characters.',
        ];
    }
}
