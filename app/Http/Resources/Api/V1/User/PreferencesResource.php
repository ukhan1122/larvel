<?php

namespace App\Http\Resources\Api\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreferencesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'all_promotional_emails' => (bool) $this->all_promotional_emails,
            'new_features_and_updates' => (bool) $this->new_features_and_updates,
            'trends_campaigns_more' => (bool) $this->trends_campaigns_more,
            'sales_and_promotions' => (bool) $this->sales_and_promotions,
            'shopping_updates' => (bool) $this->shopping_updates,
            'selling_tips_and_updates' => (bool) $this->selling_tips_and_updates,
            'personalised_recommendations' => (bool) $this->personalised_recommendations,
            'special_offers_from_sellers' => (bool) $this->special_offers_from_sellers,
            'unread_messages' => (bool) $this->unread_messages,
        ];
    }
}
