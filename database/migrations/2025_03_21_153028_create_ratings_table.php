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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();

            // user_id is the one being rated; rater_id is the user giving the rating
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rater_id');
            $table->unsignedTinyInteger('rating'); // e.g., rating out of 5
            $table->text('comment')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rater_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'rater_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
