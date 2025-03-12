<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\UpdatePreferencesRequest;
use App\Http\Resources\Api\V1\User\PreferencesResource;
use App\Models\UserPreference;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    use ApiResponse;
    public function index() {
        $user = auth()->user();
        $preferences = $user->preferences;
        $resource = new PreferencesResource($preferences);
        return $this->successResponse($resource);
    }


    /**
     * Update the authenticated user’s preferences.
     * If 'all_promotional_emails' is true -> set all to true,
     * If 'all_promotional_emails' is false -> set all to false,
     * Otherwise, user can toggle each preference individually.
     */
    public function update(UpdatePreferencesRequest $request)
    {
        $validated = $request->validated();

        $user = auth()->user();

        // Retrieve or create a new preferences record
        $preferences = $user->preferences ?? new UserPreference(['user_id' => $user->id]);

        // If 'all_promotional_emails' is included in the request,
        // set all preferences accordingly
        if (array_key_exists('all_promotional_emails', $validated)) {
            $allPromotional = $validated['all_promotional_emails'];

            $preferences->all_promotional_emails       = $allPromotional;
            $preferences->new_features_and_updates     = $allPromotional;
            $preferences->trends_campaigns_more        = $allPromotional;
            $preferences->sales_and_promotions         = $allPromotional;
            $preferences->shopping_updates             = $allPromotional;
            $preferences->selling_tips_and_updates     = $allPromotional;
            $preferences->personalised_recommendations = $allPromotional;
            $preferences->special_offers_from_sellers  = $allPromotional;
            $preferences->unread_messages              = $allPromotional;
        }

        // Override specific toggles if present in the request
        $fields = [
            'new_features_and_updates',
            'trends_campaigns_more',
            'sales_and_promotions',
            'shopping_updates',
            'selling_tips_and_updates',
            'personalised_recommendations',
            'special_offers_from_sellers',
            'unread_messages',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $validated)) {
                $preferences->{$field} = $validated[$field];
            }
        }

        // Finally, save
        $preferences->save();

        $resource = new PreferencesResource($preferences);

        return $this->successResponse($resource, 'Preferences updated successfully.');

    }

}
