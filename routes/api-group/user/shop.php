<?php

use App\Http\Controllers\Api\V1\User\ShopController;
use Illuminate\Support\Facades\Route;

Route::prefix('shop')->group(function () {
    Route::get('{userId}/products', [ShopController::class, 'getUserShop']);
});
