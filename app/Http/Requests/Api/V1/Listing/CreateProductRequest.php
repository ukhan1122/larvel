<?php

namespace App\Http\Requests\Api\V1\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'address_id' => [
                'required',
                Rule::exists('addresses', 'id')->where(function ($query) {
                  $query->where('address_type', 'pickup');
                })
            ],
            'location' => 'required|string|max:20',
            'city' => 'required|string|max:20',
            'shipping_type' => 'required|string|max:50',
            // Validate images array. Each file must be an image of the allowed types.
            'images'      => 'required|array|min:2|max:8',
            'images.*'    => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:7168',
            'allow_offers' => 'sometimes|boolean',
            'sold' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'quantity' => 'required|numeric',
            'size_data' => 'sometimes|array',
            'size_data.chest' => 'sometimes|numeric',
            'size_data.waist' => 'sometimes|numeric',
            'size_data.hips' => 'sometimes|numeric',
            'size_data.inseam' => 'sometimes|numeric',
            'size_data.sleeve' => 'sometimes|numeric',
            'size_data.shoulder' => 'sometimes|numeric',
            'size_data.standard_size' => 'sometimes|in:small,medium,large,extra_large',
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
