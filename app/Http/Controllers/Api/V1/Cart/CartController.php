<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Api\V1\Cart\CartService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }


    /**
     * Retrieve the cart for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $cart = $this->cartService->getCart($user);
        return $this->successResponse($cart, 'Cart retrieved successfully');
    }

    /**
     * Add a product to the cart.
     * Expects a 'product_id' and an optional 'quantity' (default is 1).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $user = auth()->user();
        $productId = $validated['product_id'];
        $quantity  = $validated['quantity'] ?? 1;

        $cartItem = $this->cartService->addItem($user, $productId, $quantity);

        return $this->createdResponse($cartItem, 'Product added to cart successfully');

    }

    /**
     * Update the quantity of a cart item.
     * Expects a new 'quantity' for the cart item.
     */
    public function update(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();
        try {
            $cartItem = $this->cartService->updateItem($user, $itemId, $validated['quantity']);
        } catch (\Exception $e) {
            return $this->errorResponse("Error updating cart: {$e->getMessage()}");
        }

        return $this->successResponse($cartItem, 'Cart item updated successfully');

    }

    /**
     * Remove a cart item.
     */
    public function destroy(Request $request, $itemId)
    {
        $user = auth()->user();
        try {
            $this->cartService->removeItem($user, $itemId);
        } catch (\Exception $e) {
            return $this->errorResponse("Error updating cart: {$e->getMessage()}");
        }

        return $this->successResponse(null, 'Cart item removed successfully');

    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request)
    {
        $user = auth()->user();
        $this->cartService->clearCart($user);

        return $this->successResponse(null, 'Cart cleared successfully');

    }
}
