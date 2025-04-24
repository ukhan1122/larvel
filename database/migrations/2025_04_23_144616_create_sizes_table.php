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
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->float('chest')->nullable();
            $table->float('waist')->nullable();
            $table->float('hips')->nullable();
            $table->float('inseam')->nullable();
            $table->float('sleeve')->nullable();
            $table->float('shoulder')->nullable();

            $table->enum('standard_size', ['small', 'medium', 'large', 'extra_large'])->nullable();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
