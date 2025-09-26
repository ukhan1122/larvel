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

            // Thread identifiers
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('seller_id');   // owner of product
            $table->unsignedBigInteger('buyer_id');    // the other party
            $table->unsignedBigInteger('actor_id');    // who performed this row

            // Action / state
            $table->enum('action', ['offer','counter','accept','decline','message','cancel'])
                ->default('offer');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->text('message')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for common queries
            $table->index(['product_id','buyer_id','seller_id','created_at']);
            $table->index(['seller_id','created_at']);
            $table->index(['buyer_id','created_at']);
            $table->index('status');
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
