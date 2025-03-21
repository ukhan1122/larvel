<?php


use App\Http\Controllers\Api\V1\User\RatingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::post('users/{userId}/rate', [RatingController::class, 'rateUser']);
});

Route::get('users/{userId}/ratings', [RatingController::class, 'getUserRatings']);
