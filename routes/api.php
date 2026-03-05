<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - LOADING ONLY THE TEST FILE
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Load ONLY the test route file
    require_once base_path('routes/api-group/test-route.php');
});