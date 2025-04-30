<?php

namespace App\Services\Api\V1\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Fees;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Process a checkout for items in the cart belonging to a specific seller.
     *
     * @param  User   $buyer      The authenticated buyer.
     * @param  int    $sellerId   The seller whose products are being checked out.
     * @param  array  $cartItems  Each item: ['product_id' => int, 'quantity' => int]
     * @return Order
     * @throws \Exception
     */
    public function processCheckout(User $buyer, int $sellerId, array $cartItems, int $deliveryAddressId): Order
    {
        return DB::transaction(function () use ($buyer, $sellerId, $cartItems, $deliveryAddressId) {
            // 1) Compute subtotal and prepare order items
            $subtotal        = 0;
            $orderItemsData  = [];

            foreach ($cartItems as $item) {
                /** @var Product $product */
                $product = Product::where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                // Double-check seller ownership and stock (should already be validated)
                if ($product->user_id !== $sellerId) {
                    throw new \Exception("Product ID {$product->id} does not belong to this seller.");
                }
                if ($item['quantity'] > $product->quantity_left) {
                    throw new \Exception("Insufficient stock for product ID {$product->id}.");
                }

                $lineTotal   = $product->price * $item['quantity'];
                $subtotal   += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'total'      => $lineTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 2) Load dynamic delivery fee
            $deliveryFee = Fees::where('fee_type', 'delivery')
                ->value('fee_amount');

            // 3) Compute buyer‐facing total
            $threshold  = 3000;
            $buyerTotal = ($subtotal >= $threshold)
                ? $subtotal
                : $subtotal + $deliveryFee;

            // 4) Create the Order
            $order = Order::create([
                'buyer_id'     => $buyer->id,
                'seller_id'    => $sellerId,
                'subtotal'     => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $buyerTotal,
                'status'       => 'pending',
                'delivery_address_id' => $deliveryAddressId
            ]);

            // 5) Insert order items
            foreach ($orderItemsData as &$row) {
                $row['order_id'] = $order->id;
            }
            OrderItem::insert($orderItemsData);

            // 6) Update product stock & sold flag
            foreach ($cartItems as $item) {
                $product   = Product::findOrFail($item['product_id']);
                $newLeft   = $product->quantity_left - $item['quantity'];
                $product->update([
                    'quantity_left' => $newLeft,
                    'sold'          => ($newLeft === 0),
                ]);
            }

            // 7) Adjust buyer’s cart: decrement or remove
            $cart          = Cart::firstOrCreate(['user_id' => $buyer->id]);
            $cartItemsById = $cart->items()->whereIn(
                'product_id', collect($cartItems)->pluck('product_id')
            )->get()->keyBy('product_id');

            foreach ($cartItems as $item) {
                $cartItem = $cartItemsById[$item['product_id']];
                $remaining = $cartItem->quantity - $item['quantity'];

                if ($remaining > 0) {
                    $cartItem->update(['quantity' => $remaining]);
                } else {
                    $cartItem->delete();
                }
            }

            // 8) Compute seller payout & deposit wallet points
            if ($subtotal >= $threshold) {
                $sellerBase = $subtotal - $deliveryFee;
            } else {
                $sellerBase = $subtotal;
            }
            $platformFee  = round($sellerBase * 0.10, 2);
            $sellerPayout = round($sellerBase - $platformFee, 2);

            /** @var User $seller */
            $seller = User::findOrFail($sellerId);
            $seller->deposit($sellerPayout, null, false);

            // 9) Return the created order with its items
            return $order->load('items');
        });
    }
}
