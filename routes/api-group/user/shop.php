<?php

use App\Http\Controllers\Api\V1\User\ShopController;
use Illuminate\Support\Facades\Route;

Route::prefix('shop')->group(function () {
    Route::get('{userId}/shop', [ShopController::class, 'getUserShop']);
    Route::post('auth/update-description', [ShopController::class, 'updateShopDescription'])->middleware(['auth:sanctum', 'role:user']);
});
