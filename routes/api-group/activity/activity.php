<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Activity\ActivityController;


Route::middleware(['auth:sanctum'])->prefix('app/logs')->group(function () {
    Route::get('activities', [ActivityController::class, 'index']);
});
