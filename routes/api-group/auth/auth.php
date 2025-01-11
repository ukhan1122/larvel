<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthenticationController::class, 'register'])->name('user.register');
    Route::post('login', [AuthenticationController::class, 'login'])->name('user.login');
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('user.logout')->middleware('auth:sanctum');
});
