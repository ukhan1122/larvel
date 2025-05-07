<?php

namespace App\Http\Requests\Api\V1\Listing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPhotosRequest extends FormRequest
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
            'delete_photo_ids' => 'sometimes|array',
            'delete_photo_ids.*' => 'integer|exists:photos,id',
            'new_photos' => 'sometimes|array',
            'new_photos.*' => 'file|image|max:5120' // 5MB max
        ];
    }

}
