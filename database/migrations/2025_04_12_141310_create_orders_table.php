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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Buyer who is checking out
            $table->string('buyer_id');

            $table->string('guest_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('guest_email')->nullable();
            $table->tinyInteger('is_guest_order')->default(0);

            // Seller for this order (each order is per seller)
            $table->unsignedBigInteger('seller_id');
            // Delivery address for the order
            $table->unsignedBigInteger('delivery_address_id');
            // Total product cost
            $table->decimal('subtotal', 10, 2);
            // Delivery fee (flat)
            $table->decimal('delivery_fee', 10, 2);
            // Platform fee
            $table->decimal('platform_fee', 10, 2)->default(0.00);
            // Expected delivery date
            $table->date('expected_delivery_date')->nullable();
            // Actual delivery date
            $table->date('actual_delivery_date')->nullable();
            // Tracking number
            $table->string('tracking_no')->nullable();
            // Overall total = subtotal + delivery_fee
            $table->decimal('total_amount', 10, 2);
            // Order status e.g. 'pending', 'completed'
            $table->decimal('total_seller_payout', 10, 2);
            $table->decimal('market_threshold_applied', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamps();


            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('delivery_address_id')->references('id')->on('addresses')->onDelete('cascade');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
