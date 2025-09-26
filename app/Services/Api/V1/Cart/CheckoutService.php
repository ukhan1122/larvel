<?php

namespace App\Services\Api\V1\Cart;

use App\Helpers\ActivityLogHelper;
use App\Mail\OrderSummaryToAdmin;
use App\Mail\OrderSummaryToSeller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Fees;
use App\Models\GuestCart;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutService
{
    protected $postexService;

    public function __construct(PostexService $postexService, BlueExService $blueExService)
    {
        $this->postexService = $postexService;
        $this->blueExService = $blueExService;
    }

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

            $now = now();
            $subtotal = 0;
            $itemsData = [];

            // 2) Build order-items payload and compute subtotal
            foreach ($cartItems as $item) {
                $product = $products[$item['product_id']];
                $offerId = isset($item['offer_id']) ? (int)$item['offer_id'] : null;

                \Log::info('Processing cart item', [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'offer_id' => $offerId,
                ]);
                if ($item['quantity'] > $product->quantity_left) {
                    throw new \Exception("Insufficient stock for product ID {$product->id}.");
                }

                $offer = null;
                if ($offerId) {
                    $offer = Offer::where('id', $offerId)
                        ->where('product_id', $item['product_id'])
                        ->where('seller_id', $sellerId)
                        ->first();

                    // Since validation in CheckoutRequest already checks this, the offer should exist
                    if (!$offer) {
                        throw new \Exception("Invalid or inactive offer for product ID {$item['product_id']}.");
                    }
                }

                $price = $offer ? $offer->price : $product->price;
                $lineTotal = $price * $item['quantity'];
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'total' => $lineTotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // 3) Grab both fees in one query
            $fees = Fees::whereIn('fee_type', ['delivery', 'platform', 'market_threshold'])
                ->pluck('fee_amount', 'fee_type');

            $deliveryFee = $fees['delivery'] ?? 0;
            $platformFeeRate = $fees['platform'] ?? 0;

            // 4) Totals logic
            $buyerTotal = $subtotal + $deliveryFee;
            $platformFeeAmount = round($subtotal * $platformFeeRate, 2);
            $sellerPayout = round($subtotal - $platformFeeAmount, 2);

            // 5) Create the Order
            $order = Order::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $sellerId,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'platform_fee' => $platformFeeAmount,
                'total_amount' => $buyerTotal,
                'expected_delivery_date' => Carbon::now()->addDays(5),
                'tracking_no' => 'CLSY-' . strtoupper(Str::random(10)),
                'actual_delivery_date' => null,
                'status' => 'pending',
                'delivery_address_id' => $deliveryAddressId,
                'total_seller_payout' => $sellerPayout,
                'market_threshold_applied' => 0,
                'offer_id' => $offerId ?? null
            ]);

            $order->load('items', 'items.product', 'buyer', 'seller');


            // 6) Insert order items via relationship
            $order->items()->createMany($itemsData);


            // 7) Log activity
            ActivityLogHelper::logOrderPlaced($order);

            try {
                Mail::to($order->seller->email)->send(new OrderSummaryToSeller($order));
                Mail::to(config('app.admin_email'))->send(new OrderSummaryToAdmin($order));
            } catch (\Exception $e) {
                Log::error('Failed to send order summary email: ' . $e->getMessage());
            }

            // 8) Update stock & sold flag on the same locked models
            foreach ($cartItems as $item) {
                $p = $products[$item['product_id']];
                $newLeft = $p->quantity_left - $item['quantity'];

                $p->update([
                    'quantity_left' => $newLeft,
                    'sold' => $newLeft === 0,
                ]);
            }

            $postexResponse = $this->postexService->sendOrderToPostex($order, $itemsData, $products, $buyerTotal);
            $blueEXResponse = $this->blueExService->sendOrderToBlueEx($order, $itemsData, $products, $buyerTotal);

            \Log::info('Postex Response', $postexResponse);
            \Log::info('BlueEX Response', $blueEXResponse);

            //buyer sms
//            $trackingNumber = $postexResponse['dist']['trackingNumber'] ?? null;
            $trackingNumber = $blueEXResponse['cnno'] ?? null;


            if (!$trackingNumber) {
                \Log::warning('Tracking number not found in PostEx response:', $blueEXResponse);
                $trackingNumber = 'N/A';
            }

            $messageData = [
                'name' => $order['guest_name'] ?? $order->buyer->first_name ?? '',
                'trackingID' => $trackingNumber,
            ];

            $payload = [
                'api_key' => config('services.sendpk.api_key'),
                'sender' => 'Closyyyy',
                'mobile' => $order['guest_phone'] ?? $order->buyer->phone ?? '',
                'template_id' => 10119,
                'message' => json_encode($messageData),
                'format' => 'json',
            ];

            try {
                \Log::info('SendPK SMS payload:', $payload);

                // Make the request and log both request and response
                $response = Http::asForm()->post('https://sendpk.com/api/sms.php', $payload);

                \Log::debug('SendPK API raw response:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed order sms: ' . $e->getMessage());
            }
//            buyer sms

            //seller sms
            $messageData = [
                'name' => $order->seller->first_name ?? '',
            ];

            $payload = [
                'api_key' => config('services.sendpk.api_key'),
                'sender' => 'Closyyyy',
                'mobile' => $order->seller->phone ?? '',
                'template_id' => 10120,
                'message' => json_encode($messageData),
                'format' => 'json',
            ];

            try {
                \Log::info('SendPK SMS payload:', $payload);

                // Make the request and log both request and response
                $response = Http::asForm()->post('https://sendpk.com/api/sms.php', $payload);

                \Log::debug('SendPK API raw response:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed order sms: ' . $e->getMessage());
            }
            //seller sms


            // 9) Adjust buyer’s cart in one go
            $cart = Cart::firstOrCreate(['user_id' => $buyer->id]);
            $cartItemsById = $cart
                ->items()
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            foreach ($cartItems as $item) {
                $ci = $cartItemsById[$item['product_id']];
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

    public function processCheckoutGuest(
        string $guestId,
        int    $sellerId,
        array  $cartItems,
        array  $guestInfo
    ): Order
    {
        return DB::transaction(function () use (
            $guestId,
            $sellerId,
            $cartItems,
            $guestInfo
        ) {
            //–– 0) Create the one-off Address for this guest
            $address = Address::create([
                'user_id' => $guestId,
                'address_line_1' => $guestInfo['address'],
                'address_line_2' => $guestInfo['address_line_2'] ?? null,
                'city' => $guestInfo['city'],
                'state_province_or_region' => $guestInfo['state_province_or_region'] ?? null,
                'zip_or_postal_code' => 00000,
                'address_type' => 'shipping',
                'is_guest_address' => 1,
            ]);


            $deliveryAddressId = $address->id;

            //–– 1) Lock & fetch the seller’s products
            $productIds = collect($cartItems)->pluck('product_id')->all();
            $products = Product::whereIn('id', $productIds)
                ->where('user_id', $sellerId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw new \Exception("One or more products were not found for this seller.");
            }

            //–– 2) Build items payload & subtotal
            $now = now();
            $subtotal = 0;
            $itemsData = [];

            foreach ($cartItems as $item) {
                $p = $products[$item['product_id']];
                if ($item['quantity'] > $p->quantity_left) {
                    throw new \Exception("Insufficient stock for product ID {$p->id}.");
                }

                $lineTotal = $p->price * $item['quantity'];
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $p->id,
                    'quantity' => $item['quantity'],
                    'price' => $p->price,
                    'total' => $lineTotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            //–– 3) Fees lookup
            $fees = Fees::whereIn('fee_type', ['delivery', 'platform', 'market_threshold'])
                ->pluck('fee_amount', 'fee_type');
            $deliveryFee = $fees['delivery'] ?? 0;
            $platformFeeRate = $fees['platform'] ?? 0;

            //–– 4) Totals
            $buyerTotal = $subtotal + $deliveryFee;
            $platformFeeAmount = round($subtotal * $platformFeeRate, 2);
            $sellerPayout = round($subtotal - $platformFeeAmount, 2);

            //–– 5) Create the Order (note: buyer_id = null, guest_id = UUID)
            $order = Order::create([
                'buyer_id' => $guestId,

                'guest_name' => $guestInfo['first_name'] . ' ' . $guestInfo['last_name'],
                'guest_phone' => $guestInfo['phone'],
                'guest_email' => $guestInfo['email'],
                'is_guest_order' => 1,

                'seller_id' => $sellerId,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'platform_fee' => $platformFeeAmount,
                'total_amount' => $buyerTotal,
                'expected_delivery_date' => Carbon::now()->addDays(5),
                'tracking_no' => 'CLSY-' . strtoupper(Str::random(10)),
                'status' => 'pending',
                'delivery_address_id' => $deliveryAddressId,
                'total_seller_payout' => $sellerPayout,
                'market_threshold_applied' => 0,
            ]);

            $order->load('items', 'items.product', 'seller');

            //–– 6) Insert order items
            $order->items()->createMany($itemsData);

            //–– 7) Activity log + emails
//            ActivityLogHelper::logOrderPlaced($order);
            try {
                Mail::to($order->seller->email)->send(new OrderSummaryToSeller($order));
                Mail::to(config('app.admin_email'))->send(new OrderSummaryToAdmin($order));
            } catch (\Exception $e) {
                Log::error('Failed order emails: ' . $e->getMessage());
            }


            //–– 8) Update stock
            foreach ($cartItems as $item) {
                $p = $products[$item['product_id']];
                $newLeft = $p->quantity_left - $item['quantity'];
                $p->update([
                    'quantity_left' => $newLeft,
                    'sold' => $newLeft === 0,
                ]);
            }

            $postexResponse = $this->postexService->sendOrderToPostex($order, $itemsData, $products, $buyerTotal);
            $blueEXResponse = $this->blueExService->sendOrderToBlueEx($order, $itemsData, $products, $buyerTotal);

            \Log::info('Postex Response', $postexResponse);
            \Log::info('BlueEX Response', $blueEXResponse);



            //buyer sms
//            $trackingNumber = $postexResponse['dist']['trackingNumber'] ?? null;
            $trackingNumber = $blueEXResponse['cnno'] ?? null;


            if (!$trackingNumber) {
                \Log::warning('Tracking number not found in PostEx response:', $postexResponse);
                $trackingNumber = 'N/A';
            }

            $messageData = [
                'name' => $order['guest_name'] ?? $order->buyer->first_name ?? '',
                'trackingID' => $trackingNumber,
            ];

            $payload = [
                'api_key' => config('services.sendpk.api_key'),
                'sender' => 'Closyyyy',
                'mobile' => $order['guest_phone'] ?? $order->buyer->phone ?? '',
                'template_id' => 10119,
                'message' => json_encode($messageData),
                'format' => 'json',
            ];

            try {
                \Log::info('SendPK SMS payload:', $payload);

                // Make the request and log both request and response
                $response = Http::asForm()->post('https://sendpk.com/api/sms.php', $payload);

                \Log::debug('SendPK API raw response:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed order sms: ' . $e->getMessage());
            }
            //buyer sms

            //seller sms
            $messageData = [
                'name' => $order->seller->first_name ?? '',
            ];

            $payload = [
                'api_key' => config('services.sendpk.api_key'),
                'sender' => 'Closyyyy',
                'mobile' => $order->seller->phone ?? '',
                'template_id' => 10120,
                'message' => json_encode($messageData),
                'format' => 'json',
            ];

            try {
                \Log::info('SendPK SMS payload:', $payload);

                // Make the request and log both request and response
                $response = Http::asForm()->post('https://sendpk.com/api/sms.php', $payload);

                \Log::debug('SendPK API raw response:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed order sms: ' . $e->getMessage());
            }
            //seller sms


            //–– 9) Adjust guest cart
            $cart = GuestCart::firstOrCreate(['guest_id' => $guestId]);
            $byProduct = $cart->items()
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            foreach ($cartItems as $item) {
                $ci = $byProduct[$item['product_id']];
                $remain = $ci->quantity - $item['quantity'];
                $remain > 0
                    ? $ci->update(['quantity' => $remain])
                    : $ci->delete();
            }


            return $order;
        });
    }

}
