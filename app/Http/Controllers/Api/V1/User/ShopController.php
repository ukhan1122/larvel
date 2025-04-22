<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use ApiResponse;

    /**
     * Get products for a given user's shop (public view).
     * Accepts filter query parameter: all, selling, sold, liked, saved.
     */
    public function getUserShop(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $filter = $request->query('filter', 'all');

        // Base query: products owned by user.
        $query = Product::with(['photos', 'category', 'brand', 'condition'])
            ->where('user_id', $user->id);

        switch ($filter) {
            case 'selling':
                $query->where('active', true)->where('sold', false);
                break;
            case 'sold':
                $query->where('sold', true);
                break;
            case 'liked':
                // For liked, use the relationship on the user.
                $query = $user->likedProducts()->with(['photos', 'category', 'brand', 'condition']);
                break;
            case 'saved':
                $query = $user->savedProducts()->with(['photos', 'category', 'brand', 'condition']);
                break;
            // default: all products
        }

        $perPage = $request->query('per_page', 10);
        $products = $query->paginate($perPage);

        $shop = Shop::where('user_id', $userId)->first();

        return $this->successResponse([
            'shop' => $shop,
            'products' => $products
        ]);
    }


    public function updateShopDescription(Request $request) {
        $request->validate([
            'description' => ['required', 'string']
        ]);

        $user = auth()->user();
        $shop = Shop::updateOrCreate(
            ['user_id' => $user->id],
            ['description' => $request->input('description')]
        );

        return $this->successResponse($shop, 'Shop description updated');
    }

}
