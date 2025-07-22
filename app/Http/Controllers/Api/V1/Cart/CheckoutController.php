<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\CheckoutRequest;
use App\Http\Requests\Api\V1\Checkout\CheckoutRequestGuest;
use App\Models\Order;
use App\Services\Api\V1\Cart\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    protected $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    /**
     * Process a checkout for items from a given seller.
     */
    public function checkout(CheckoutRequest $request)
    {

        // At this point, all structural & business-rule validations have passed
        $validated = $request->validated();

        try {
            $order = $this->checkoutService->processCheckout(
                $request->user(),
                $validated['seller_id'],
                $validated['cart_items'],
                $validated['delivery_address_id']
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Checkout completed successfully',
                'data'    => $order
            ], 201);

        } catch (\Exception $e) {
            // Service‐level errors (should be rare)
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
    public function checkoutGuest(CheckoutRequestGuest $request)
    {
        $v       = $request->validated();
        $guestId = $v['guest_id'];
        $seller  = $v['seller_id'];
        $items   = $v['cart_items'];
        $info    = $v['guest_info'];

        logger('checkoutGuest payload', [
            'validated'  => $v,
            'guest_id'   => $guestId,
            'seller_id'  => $seller,
            'cart_items' => $items,
            'guest_info' => $info,
        ]);
        try {
            $order = $this->checkoutService
                ->processCheckoutGuest($guestId, $seller, $items, $info);

            return response()->json([
                'status'  => 'success',
                'message' => 'Checkout completed successfully',
                'data'    => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Retrieve orders for the authenticated user.
     * either sold or purchased
     */
    public function getOrders(Request $request)
    {
        $type = $request->query('type', 'sold'); // default is 'sold'
        $userId = $request->user()->id;

        if ($type === 'purchased') {
            $orders = Order::where('buyer_id', $userId)
                ->with(['items.product', 'items.product.photos', 'seller', 'deliveryAddress'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else { // default or 'sold'
            $orders = Order::where('seller_id', $userId)
                ->with(['items.product', 'items.product.photos', 'buyer', 'deliveryAddress'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Orders retrieved successfully',
            'data'    => $orders
        ], 200);
    }

    /**
     * Retrieve a specific order's details.
     */
    public function getOrder($orderId, Request $request)
    {

        $order = Order::where('buyer_id', $request->user()->id)
            ->with('items.product', 'seller')
            ->findOrFail($orderId);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order details retrieved successfully',
            'data'    => $order
        ], 200);
    }
    public function getOrderForGuest(Request $request)
    {
        $orderId = $request->orderId;
        $guestId = $request->guest_id;

        // Get the order for this guest
        $order = Order::where('id', $orderId)
            ->where('buyer_id', $guestId)
            ->with('items.product', 'seller', 'items.product.photos')
            ->firstOrFail();

        logger('getOrderForGuest payload', [$order, $guestId]);

        // Fetch the address directly by delivery_address_id and user_id = guest_id (UUID)
        $address = \DB::table('addresses')
            ->where('id', $order->delivery_address_id)
            ->where('user_id', $guestId) // guestId is UUID for guest addresses
            ->first();


        // Convert to array and attach
        $orderData = $order->toArray();
        $orderData['delivery_address'] = $address;

        return response()->json([
            'status'  => 'success',
            'message' => 'Order details retrieved successfully',
            'data'    => $orderData
        ], 200);
    }

}
