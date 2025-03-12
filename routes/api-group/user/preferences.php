<?php

use App\Http\Controllers\Api\V1\User\PreferencesController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::get('user/preferences', [PreferencesController::class, 'index']);
    Route::post('user/preferences', [PreferencesController::class, 'update']);
});
