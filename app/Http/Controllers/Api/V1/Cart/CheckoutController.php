<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
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
     *
     * Request Example:
     * {
     *    "seller_id": 5,
     *    "cart_items": [
     *         {"product_id": 10, "quantity": 2},
     *         {"product_id": 11, "quantity": 1}
     *    ]
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'seller_id'   => 'required|exists:users,id',
            'cart_items'  => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity'   => 'required|integer|min:1'
        ]);

        try {
            $order = $this->checkoutService->processCheckout(
                $request->user(),
                $validated['seller_id'],
                $validated['cart_items']
            );
            return response()->json([
                'status'  => 'success',
                'message' => 'Checkout completed successfully',
                'data'    => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Retrieve orders for the authenticated buyer.
     */
    public function getOrders(Request $request)
    {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with('items.product', 'seller')
            ->orderBy('created_at', 'desc')
            ->get();

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
}
