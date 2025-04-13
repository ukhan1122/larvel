<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Cart\CheckoutController;

Route::middleware(['auth:sanctum', 'role:user'])->prefix('cart/checkout')->group(function () {
    // Process a checkout for items from a single seller.
    Route::post('create', [CheckoutController::class, 'checkout']);

    // Retrieve orders for the authenticated buyer.
    Route::get('orders', [CheckoutController::class, 'getOrders']);

    // Retrieve details for a specific order.
    Route::get('orders/{orderId}', [CheckoutController::class, 'getOrder']);
});
