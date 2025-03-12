<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('all_promotional_emails')->default(true);
            $table->boolean('new_features_and_updates')->default(true);
            $table->boolean('trends_campaigns_more')->default(true);
            $table->boolean('sales_and_promotions')->default(true);
            $table->boolean('shopping_updates')->default(true);
            $table->boolean('selling_tips_and_updates')->default(true);
            $table->boolean('personalised_recommendations')->default(true);
            $table->boolean('special_offers_from_sellers')->default(true);
            $table->boolean('unread_messages')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
