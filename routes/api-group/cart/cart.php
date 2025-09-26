<?php

use App\Http\Controllers\Api\V1\Cart\CartController;
use Illuminate\Support\Facades\Route;

Route::prefix('user/cart')->middleware(['auth:sanctum', 'role:user'])->group(function () {
    // Retrieve the authenticated user's cart.
    Route::get('/', [CartController::class, 'index']);

    // Add an item to the cart.
    Route::post('items', [CartController::class, 'store']);
    Route::post('offers/items', [CartController::class, 'offersStore']);

    // Update a cart item's quantity.
    Route::put('items/{itemId}', [CartController::class, 'update']);

    // Remove a cart item.
    Route::delete('items/{itemId}', [CartController::class, 'destroy']);

    // Clear the entire cart.
    Route::delete('clear', [CartController::class, 'clear']);

    Route::put('items/increment/item', [CartController::class, 'incrementCartItem']);

});
Route::prefix('user/cart')->group(function () {

    Route::get('/guest', [CartController::class, 'guestIndex']);

    // Add an item to the cart.
    Route::post('items/guest', [CartController::class, 'storeGuest']);
    Route::delete('items/guest/{itemId}', [CartController::class, 'guestDestroy']);


    Route::put('items/guest/increment', [CartController::class, 'incrementCartItemGuest']);

});
