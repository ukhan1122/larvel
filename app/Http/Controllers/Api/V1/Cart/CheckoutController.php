<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\CheckoutRequest;
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

    /**
     * Retrieve orders for the authenticated buyer.
     */
    public function getOrders(Request $request)
    {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with(['items.product', 'items.product.photos', 'seller'])
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
