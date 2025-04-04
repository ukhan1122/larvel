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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            // The product for which the offer is made.
            $table->unsignedBigInteger('product_id');
            // User that replied last
            $table->unsignedBigInteger('last_reply_by')->nullable();
            // The user making the offer.
            $table->unsignedBigInteger('offerer_id');
            // The offered amount.
            $table->decimal('offer_price', 10, 2);
            // Status: pending, accepted, rejected, countered.
            $table->string('status')->default('pending');
            // If countered, the counter offer price.
            $table->decimal('counter_price', 10, 2)->nullable();
            // Optional message attached with the offer.
            $table->text('message')->nullable();
            $table->timestamps();

            // Foreign keys and indexing.
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('offerer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('last_reply_by')->references('id')->on('users')->onDelete('set null');
            $table->index('status');
            $table->index('product_id');
            $table->index('offerer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
