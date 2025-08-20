<?php

use App\Http\Controllers\Api\V1\Listing\ProductController;
use Illuminate\Support\Facades\Route;

// Protected product endpoints:
Route::prefix('auth/products')->middleware(['auth:sanctum', 'role:user'])->group(function () {
    // Create a new product
    Route::post('create', [ProductController::class, 'store']);

    // Optionally, search/filter within the vendor's own products
    Route::get('search', [ProductController::class, 'searchUserProducts']);

    // Get all products for the authenticated user
    Route::get('show', [ProductController::class, 'userProducts']);

    // Get details for a single product belonging to the authenticated user
    Route::get('{id}', [ProductController::class, 'showSingleAuth']);

    // Update an existing product
    Route::put('{id}', [ProductController::class, 'update']);

    Route::post('{id}/photos', [ProductController::class, 'updatePhotos']);

    // Delete a product
    Route::delete('{id}', [ProductController::class, 'destroy']);

});

// Public product endpoints:
Route::prefix('public/products')->group(function () {
    //products by category
    Route::get('the/mens', [ProductController::class, 'getMenProducts']);
    Route::get('the/womens', [ProductController::class, 'getWomenProducts']);
    Route::get('the/kids', [ProductController::class, 'getKidProducts']);

    // Get all public products with relationships (existing)
    Route::get('show', [ProductController::class, 'publicProducts']);



    // Search & filter products (by query parameters) with pagination.
    // Example: /v1/listing/public/products/search?category_id=1&brand_id=2&min_price=100&max_price=500&page=1
//    Route::get('search', [ProductController::class, 'search']);

    // Get a single product details (by ID)
    Route::get('/{brand}/{slug}', [ProductController::class, 'showSingle']);


    Route::get('the/search/{group?}/{category?}', [ProductController::class, 'newProductsFetch'])
        ->where([
            'group'    => '[A-Za-z\-]+',
            'category' => '[A-Za-z\-]+',
        ]);

    // Filter sources
    Route::get('the/categories/{group}',  [ProductController::class, 'listCategoriesByGroup']);
    Route::get('the/brands/{group}',      [ProductController::class, 'listBrandsByGroup']);

    Route::get('the/products/conditions',          [ProductController::class, 'listConditions']);
    Route::get('the/products/sizes',               [ProductController::class, 'listSizes']);



});
