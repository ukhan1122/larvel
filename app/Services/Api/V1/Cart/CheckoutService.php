<?php

namespace App\Services\Api\V1\Cart;

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
     * @param  User  $buyer
     * @param  int  $sellerId
     * @param  array  $cartItems  Array of cart items (each with 'id', 'product_id', 'quantity')
     * @return Order
     * @throws \Exception
     */
    public function processCheckout($buyer, $sellerId, array $cartItems)
    {
        // Perform the checkout in a transaction.
        return DB::transaction(function () use ($buyer, $sellerId, $cartItems) {
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($cartItems as $item) {
                // Retrieve product with a lock for update to prevent race conditions.
                $product = Product::where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->approval_status === 'pending') {
                    throw new \Exception("Product {$product->id} is not yet approved by the admin");
                }

                // Ensure that the product actually belongs to the specified seller.
                if ($product->user_id != $sellerId) {
                    throw new \Exception("Product {$product->id} does not belong to seller {$sellerId}.");
                }

                // Check that the requested quantity does not exceed quantity_left.
                if ($item['quantity'] > $product->quantity_left) {
                    throw new \Exception("Insufficient stock for product {$product->id}.");
                }

                $lineTotal = $item['quantity'] * $product->price;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'total'      => $lineTotal,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // Retrieve the dynamic delivery fee from the fees table.
            $deliveryFeeRecord = Fees::where('fee_type', 'delivery')->firstOrFail();
            $deliveryFee = $deliveryFeeRecord->fee_amount;

            $totalAmount = $subtotal + $deliveryFee;

            // Create the order record.
            $order = Order::create([
                'buyer_id'     => $buyer->id,
                'seller_id'    => $sellerId,
                'subtotal'     => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'status'       => 'pending'
            ]);

            // Create the order items.
            foreach ($orderItemsData as &$orderItem) {
                $orderItem['order_id'] = $order->id;
            }
            OrderItem::insert($orderItemsData);

            // Update each product: decrement quantity_left and mark sold if necessary.
            foreach ($cartItems as $item) {
                $product = Product::findOrFail($item['product_id']);
                $newQuantityLeft = $product->quantity_left - $item['quantity'];
                $product->update([
                    'quantity_left' => $newQuantityLeft,
                    'sold'          => ($newQuantityLeft == 0)
                ]);
            }

            // Optionally, remove these items from the buyer's cart.
            // That logic depends on how you manage your cart; here we assume it will be handled by the cart service.

            return $order->load('items');
        });
    }
}
