<?php

use App\Http\Controllers\Api\V1\Admin\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'ensure.admin'])
    ->group(function () {
        Route::get('users/all/wallet', [WalletController::class, 'showAll']);
        Route::get('users/{user}/wallet',  [WalletController::class, 'show']);
        Route::get('users/{user}/transactions', [WalletController::class, 'transactions']);
        Route::post('users/{user}/transactions/{transaction}/confirm', [WalletController::class, 'confirm']);
        Route::post('users/{user}/transactions/{transaction}/cancel', [WalletController::class, 'cancel']);
        Route::post('users/{user}/wallet/deposit', [WalletController::class, 'deposit']);
        Route::post('users/{user}/wallet/withdraw', [WalletController::class, 'withdraw']);
    });
