<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\Listing\OfferService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    use ApiResponse;

    protected $offerService;

    public function __construct(OfferService $offerService)
    {
        $this->offerService = $offerService;
    }

    /**
     * Create a new offer on a product.
     */
    public function createOffer(Request $request)
    {
        $validated = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'offer_price' => 'required|numeric|min:0',
            'message'     => 'nullable|string'
        ]);

        try {
            $offer = $this->offerService->createOffer(request()->user(), $validated);

            return $this->createdResponse($offer, 'Offer created successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse("{$e->getMessage()}");
        }
    }

    /**
     * Get offers received (for products owned by the authenticated user).
     */
    public function getReceivedOffers(Request $request)
    {
        $offers = $this->offerService->getReceivedOffers($request->user());
        return $this->successResponse($offers, 'Received offers retrieved successfully');
    }

    /**
     * Get offers sent by the authenticated user.
     */
    public function getSentOffers(Request $request)
    {
        $offers = $this->offerService->getSentOffers($request->user());
        return $this->successResponse($offers, 'Sent offers retrieved successfully');
    }

    /**
     * Accept an offer.
     */
    public function acceptOffer(Request $request, $offerId)
    {
        try {
            $offer = $this->offerService->acceptOffer($request->user(), $offerId);
            return $this->successResponse($offer, 'Offer accepted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse("{$e->getMessage()}");
        }
    }

    /**
     * Reject an offer.
     */
    public function rejectOffer(Request $request, $offerId)
    {
        try {
            $offer = $this->offerService->rejectOffer($request->user(), $offerId);
            return $this->successResponse($offer, 'Offer rejected successfully');
        } catch (\Exception $e) {
            return $this->errorResponse("{$e->getMessage()}");
        }
    }

    /**
     * Counter an offer.
     */
    public function counterOffer(Request $request, $offerId)
    {
        $validated = $request->validate([
            'counter_price' => 'required|numeric|min:0',
            'message'       => 'nullable|string'
        ]);

        try {
            $offer = $this->offerService->counterOffer($request->user(), $offerId, $validated);
            return $this->successResponse($offer, 'Counter offer submitted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse("{$e->getMessage()}");
        }
    }
}
