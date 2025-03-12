<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
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
            'all_promotional_emails'        => 'sometimes|boolean',
            'new_features_and_updates'      => 'sometimes|boolean',
            'trends_campaigns_more'         => 'sometimes|boolean',
            'sales_and_promotions'          => 'sometimes|boolean',
            'shopping_updates'              => 'sometimes|boolean',
            'selling_tips_and_updates'      => 'sometimes|boolean',
            'personalized_recommendations'  => 'sometimes|boolean',
            'special_offers_from_sellers'   => 'sometimes|boolean',
            'unread_messages'               => 'sometimes|boolean',
        ];
    }
}
