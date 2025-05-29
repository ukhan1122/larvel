<?php

use App\Http\Controllers\Api\V1\User\BankController;
use Illuminate\Support\Facades\Route;

Route::prefix('user/bank')->middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::post('details/create', [BankController::class, 'store']);
    Route::get('details/show', [BankController::class, 'show']);
    Route::get('transactions', [BankController::class, 'getTransactions']);
});
