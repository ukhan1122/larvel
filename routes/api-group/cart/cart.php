<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Cart\CartController;

Route::prefix('user/cart')->middleware(['auth:sanctum', 'role:user'])->group(function () {
    // Retrieve the authenticated user's cart.
    Route::get('/', [CartController::class, 'index']);

    // Add an item to the cart.
    Route::post('items', [CartController::class, 'store']);

    // Update a cart item's quantity.
    Route::put('items/{itemId}', [CartController::class, 'update']);

    // Remove a cart item.
    Route::delete('items/{itemId}', [CartController::class, 'destroy']);

    // Clear the entire cart.
    Route::delete('clear', [CartController::class, 'clear']);
});
