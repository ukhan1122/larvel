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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->constrained();
            $table->foreignId('condition_id')->constrained();
            $table->foreignId('address_id')->constrained();
            $table->string('title');
            $table->integer('quantity')->default(1);
            $table->integer('quantity_left')->default(1);
            $table->string('approval_status')->default('pending');
            $table->string('description');
            $table->string('location');
            $table->string('city');
            $table->string('shipping_type');
            $table->boolean('active')->default(true);
            $table->boolean('sold')->default(false);
            $table->boolean('allow_offers')->default(true);
            $table->unsignedBigInteger('price');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
