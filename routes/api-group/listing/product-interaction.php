<?php


use App\Http\Controllers\Api\V1\User\ProductInteractionController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->middleware(['auth:sanctum', 'role:user'])->group(function () {
    // Like and Unlike Endpoints
    Route::post('{productId}/like', [ProductInteractionController::class, 'likeProduct']);
    Route::delete('{productId}/like', [ProductInteractionController::class, 'unlikeProduct']);

    // Save and Unsave Endpoints
    Route::post('{productId}/save', [ProductInteractionController::class, 'saveProduct']);
    Route::delete('{productId}/save', [ProductInteractionController::class, 'unsaveProduct']);
});
