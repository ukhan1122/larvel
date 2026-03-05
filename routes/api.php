<?php

// V2 - Fixed with direct file includes
use App\Http\Controllers\Api\V1\Listing\CategoriesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Simple test route - no includes, no complexity
Route::get('/laravel-test', function () {
    return response()->json([
        'laravel_working' => true,
        'database_products' => \DB::table('products')->count(),
        'message' => 'Laravel API is working!'
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// SINGLE v1 prefix group
Route::prefix('v1')->group(function () {
    
    // DEBUG: Check if files exist
    $files = [
        'routes/api-group/auth/auth.php',
        'routes/api-group/listing/products.php',
        'routes/api-group/user/users.php',
    ];
    
    foreach($files as $file) {
        if(file_exists(base_path($file))) {
            error_log("✅ Found: " . $file);
        } else {
            error_log("❌ MISSING: " . $file);
        }
    }
    
    // TEST ROUTE INSIDE V1
    Route::get('/debug-test', function() {
        return response()->json(['message' => 'v1 is working!']);
    });
    
    // Use require_once to prevent multiple inclusions
    require_once base_path('routes/api-group/auth/auth.php');
    require_once base_path('routes/api-group/auth/social-auth.php');
    require_once base_path('routes/api-group/user/preferences.php');
    
    Route::prefix('listing')->group(function () {
        $files = glob(base_path('routes/api-group/listing/*.php'));
        foreach ($files as $file) {
            require_once $file;
        }
    });
    
    require_once base_path('routes/api-group/user/address.php');
    require_once base_path('routes/api-group/user/followers.php');
    require_once base_path('routes/api-group/user/ratings.php');
    require_once base_path('routes/api-group/user/shop.php');
    require_once base_path('routes/api-group/user/users.php');
    require_once base_path('routes/api-group/user/reviews.php');
    require_once base_path('routes/api-group/cart/cart.php');
    require_once base_path('routes/api-group/cart/checkout.php');
    require_once base_path('routes/api-group/conversation/conversations.php');
    require_once base_path('routes/api-group/user/bank.php');
    require_once base_path('routes/api-group/activity/activity.php');
    require_once base_path('routes/api-group/admin/admin-apis.php');
});