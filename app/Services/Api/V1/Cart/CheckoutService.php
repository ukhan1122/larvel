<?php

namespace App\Services\Api\V1\Cart;

use App\Helpers\ActivityLogHelper;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Fees;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function processCheckout(User $buyer, int $sellerId, array $cartItems, int $deliveryAddressId): Order
    {
        return DB::transaction(function () use ($buyer, $sellerId, $cartItems, $deliveryAddressId) {
            // 1) Lock all products in one go
            $productIds = collect($cartItems)->pluck('product_id')->all();
            $products = Product::whereIn('id', $productIds)
                ->where('user_id', $sellerId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw new \Exception("One or more products were not found for this seller.");
            }

            $now       = now();
            $subtotal  = 0;
            $itemsData = [];

            // 2) Build order-items payload and compute subtotal
            foreach ($cartItems as $item) {
                $product = $products[$item['product_id']];

                if ($item['quantity'] > $product->quantity_left) {
                    throw new \Exception("Insufficient stock for product ID {$product->id}.");
                }

                $lineTotal   = $product->price * $item['quantity'];
                $subtotal   += $lineTotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'total'      => $lineTotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // 3) Grab both fees in one query
            $fees = Fees::whereIn('fee_type', ['delivery','platform', 'market_threshold'])
                ->pluck('fee_amount','fee_type');

            $deliveryFee     = $fees['delivery'] ?? 0;
            $platformFeeRate = $fees['platform']  ?? 0;

            // 4) Totals logic
            $threshold          = $fees['market_threshold'] ?? 0;
            $exceedsThreshold   = $subtotal >= $threshold;
            $buyerTotal         = $exceedsThreshold ? $subtotal : $subtotal + $deliveryFee;
            $platformFeeBase    = $subtotal - ($exceedsThreshold ? $deliveryFee : 0);
            $platformFeeAmount  = round($platformFeeBase * $platformFeeRate, 2);

            // 10) Compute seller payout & deposit
            $sellerBase   = $exceedsThreshold ? $subtotal - $deliveryFee : $subtotal;
            $sellerPayout = round($sellerBase - $platformFeeAmount, 2);

            // 5) Create the Order
            $order = Order::create([
                'buyer_id'                 => $buyer->id,
                'seller_id'                => $sellerId,
                'subtotal'                 => $subtotal,
                'delivery_fee'             => $deliveryFee,
                'platform_fee'             => $platformFeeAmount,
                'total_amount'             => $buyerTotal,
                'expected_delivery_date'   => Carbon::now()->addDays(5),
                'tracking_no'              => 'CLSY-' . strtoupper(Str::random(10)),
                'actual_delivery_date'     => null,
                'status'                   => 'pending',
                'delivery_address_id'      => $deliveryAddressId,
                'total_seller_payout' => $sellerPayout,
                'market_threshold_applied' => $threshold
            ]);



            // 6) Log activity
            ActivityLogHelper::logOrderPlaced($order);

            // 7) Insert order items via relationship
            $order->items()->createMany($itemsData);

            // 8) Update stock & sold flag on the same locked models
            foreach ($cartItems as $item) {
                $p       = $products[$item['product_id']];
                $newLeft = $p->quantity_left - $item['quantity'];

                $p->update([
                    'quantity_left' => $newLeft,
                    'sold'          => $newLeft === 0,
                ]);
            }

            // 9) Adjust buyer’s cart in one go
            $cart = Cart::firstOrCreate(['user_id' => $buyer->id]);
            $cartItemsById = $cart
                ->items()
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            foreach ($cartItems as $item) {
                $ci        = $cartItemsById[$item['product_id']];
                $remaining = $ci->quantity - $item['quantity'];

                if ($remaining > 0) {
                    $ci->update(['quantity' => $remaining]);
                } else {
                    $ci->delete();
                }
            }


            // 11) Return order with its items eager-loaded
            return $order->load('items');
        });
    }

}
