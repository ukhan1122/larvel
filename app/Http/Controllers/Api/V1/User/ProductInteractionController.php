<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductInteractionController extends Controller
{
    use ApiResponse;

    /**
     * Like a product.
     *
     * @param Request $request
     * @param  int  $productId
     * @return JsonResponse
     */
    public function likeProduct(Request $request, $productId)
    {
        $user = $request->user();
        $product = Product::findOrFail($productId);

        // Use syncWithoutDetaching to add the product to likedProducts without duplicates.
        $user->likedProducts()->syncWithoutDetaching([$product->id]);

        return $this->successResponse(null, 'Product liked successfully');
    }

    /**
     * Unlike a product.
     *
     * @param Request $request
     * @param  int  $productId
     * @return JsonResponse
     */
    public function unlikeProduct(Request $request, $productId)
    {
        $user = $request->user();
        $product = Product::findOrFail($productId);

        // Remove the product from the likedProducts pivot.
        $user->likedProducts()->detach($product->id);

        return $this->successResponse(null, 'Product unliked successfully');
    }

    /**
     * Save a product.
     *
     * @param Request $request
     * @param  int  $productId
     * @return JsonResponse
     */
    public function saveProduct(Request $request, $productId)
    {
        $user = $request->user();
        $product = Product::findOrFail($productId);

        // Add the product to the savedProducts pivot without duplicates.
        $user->savedProducts()->syncWithoutDetaching([$product->id]);

        return $this->successResponse(null, 'Product saved successfully');
    }

    /**
     * Unsave a product.
     *
     * @param Request $request
     * @param  int  $productId
     * @return JsonResponse
     */
    public function unsaveProduct(Request $request, $productId)
    {
        $user = $request->user();
        $product = Product::findOrFail($productId);

        // Remove the product from the savedProducts pivot.
        $user->savedProducts()->detach($product->id);

        return $this->successResponse(null, 'Product unsaved successfully');
    }

}
