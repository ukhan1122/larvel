<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthenticationController::class, 'register'])->name('user.register');
    Route::post('login', [AuthenticationController::class, 'login'])->name('user.login');
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('user.logout')->middleware('auth:sanctum');

    Route::post('forgot-password', [AuthenticationController::class, 'forgotPassword'])->name('password.forgot'); // sends the email

    // Note: This route returns a view and needs to be moved to the frontend in the future.
    Route::get('reset-password/{token}', [AuthenticationController::class, 'setNewPassword'])->name('password.reset'); // triggers when clicking the link in the email.

    Route::post('set-new-password', [AuthenticationController::class, 'reset'])->name('password.update'); // handles actual password reset

    // Note: This route returns a view and needs to be moved to the frontend in the future.
    Route::get('password-reset-success', [AuthenticationController::class, 'resetSuccess'])->name('password.reset.success'); // shows success page after setting a new password. Should be on frontend

});
