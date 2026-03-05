<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes - Diagnostic Version
|--------------------------------------------------------------------------
*/

// This route works (keep it)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Test v1 prefix
Route::prefix('v1')->group(function () {
    
    // Simple test inside v1
    Route::get('/ping', function() {
        return response()->json(['message' => 'v1 is working!']);
    });
    
    // Try loading auth file
    $authFile = base_path('routes/api-group/auth/auth.php');
    if (file_exists($authFile)) {
        require_once $authFile;
        \Log::info("✅ Auth file loaded successfully");
    } else {
        \Log::error("❌ Auth file NOT FOUND: " . $authFile);
    }
    
    // Try loading products file
    $productsFile = base_path('routes/api-group/listing/products.php');
    if (file_exists($productsFile)) {
        require_once $productsFile;
        \Log::info("✅ Products file loaded successfully");
    } else {
        \Log::error("❌ Products file NOT FOUND: " . $productsFile);
    }
    
});