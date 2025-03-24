<?php

namespace App\Services\Api\V1\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Retrieve the authenticated user's cart with items and products loaded.
     */
    public function getCart($user)
    {
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load('items.product');
        return $cart;
    }

    /**
     * Add a product to the user's cart.
     * Increases quantity if the product already exists in the cart.
     */
    public function addItem($user, $productId, $quantity = 1)
    {
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            $cartItem = CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);
            $cartItem->load('product');
        }

        return $cartItem;
    }

    /**
     * Update the quantity of an item in the user's cart.
     */
    public function updateItem($user, $itemId, $quantity)
    {
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (!$cartItem) {
            throw new \Exception("Cart item not found");
        }

        $cartItem->quantity = $quantity;
        $cartItem->save();

        // Ensure the product relation is loaded so the total_price accessor works.
        $cartItem->load('product');
        return $cartItem;
    }

    /**
     * Remove an item from the user's cart.
     */
    public function removeItem($user, $itemId)
    {
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (!$cartItem) {
            throw new \Exception("Cart item not found");
        }

        $cartItem->delete();
        return true;
    }

    /**
     * Clear all items from the user's cart.
     */
    public function clearCart($user)
    {
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->items()->delete();
        return true;
    }
}
