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
            $table->unsignedBigInteger('buyer_id');
            // Seller for this order (each order is per seller)
            $table->unsignedBigInteger('seller_id');
            // Total product cost
            $table->decimal('subtotal', 10, 2);
            // Delivery fee (flat)
            $table->decimal('delivery_fee', 10, 2);
            // Overall total = subtotal + delivery_fee
            $table->decimal('total_amount', 10, 2);
            // Order status e.g. 'pending', 'completed'
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
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
