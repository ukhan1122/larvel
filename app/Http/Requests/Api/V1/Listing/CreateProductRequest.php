<?php

namespace App\Http\Requests\Api\V1\Listing;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'title'        => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required',
            'category_id' => 'required|exists:categories,id',
            'brand_name' => 'required|string',
            'condition_id' => 'required|exists:conditions,id',
            'address_id' => 'required|exists:addresses,id',
            'location' => 'required|string|max:20',
            'city' => 'required|string|max:20',
            'shipping_type' => 'required|string|max:50',
            // Validate images array. Each file must be an image of the allowed types.
            'images'      => 'required|array|min:2|max:8',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'allow_offers' => 'sometimes|boolean',
            'sold' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'quantity' => 'required|numeric'
        ];
    }

    protected function prepareForValidation()
    {
        $booleanFields = ['allow_offers', 'sold', 'active'];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }
}
