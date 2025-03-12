<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $table = 'user_preferences';
    protected $fillable = [
        'user_id',
        'all_promotional_emails',
        'new_features_and_updates',
        'trends_campaigns_more',
        'sales_and_promotions',
        'shopping_updates',
        'selling_tips_and_updates',
        'personalized_recommendations',
        'special_offers_from_sellers',
        'unread_messages',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
