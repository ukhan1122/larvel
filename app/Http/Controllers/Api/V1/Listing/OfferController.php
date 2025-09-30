<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Services\Api\V1\Listing\OfferService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'product_id' => 'required|exists:products,id',
            'offer_price' => 'required|numeric|min:0',
            'message' => 'nullable|string'
        ]);

        try {
            $offer = $this->offerService->createOffer(request()->user(), $validated);

            return $this->createdResponse($offer, 'Offer created successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse("{$e->getMessage()}");
        }
    }


    public function updateOfferPrice(Request $request, $offerId)
    {
        // Log the request data and id
        Log::info('Update Offer Price Request', [
            'offer_id' => $offerId,
            'request_data' => $request->all(),
            'user_id' => $request->user() ? $request->user()->id : null,
        ]);

        $validated = $request->validate([
            'offer_price' => 'required|numeric|min:0', // Changed from 'price' to 'offer_price'
            'product_id' => 'required|exists:products,id', // Validate product_id
            'message' => 'nullable|string', // Optional message
        ]);

        try {
            // Pass the offerId and validated data to the service
            $offer = $this->offerService->updateOfferPrice(request()->user(), $offerId, $validated);

            // Log successful update
            Log::info('Offer updated successfully', ['offer_id' => $offer->id]);

            return $this->createdResponse($offer, 'Offer updated successfully.');
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error updating offer', ['offer_id' => $offerId, 'error' => $e->getMessage()]);

            return $this->errorResponse("{$e->getMessage()}");
        }
    }

    /**
     * Get offers received (for products owned by the authenticated user).
     */
    public function getReceivedOffers(Request $request)
    {
//        Log::info('Incoming request', [
//            'user' => $request->user()->toArray()
//        ]);
        $offers = $this->offerService->getReceivedOffers($request->user());
        return $this->successResponse($offers, 'Received offers retrieved successfully');
    }

    public function getOfferConversation(Request $request)
    {
        Log::info('Incoming request', [
            'user' => $request
        ]);
        $offers = $this->offerService->getOfferConversation($request);
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
//                Log::info('Incoming request', [
//            'user' => $request->user(),
//            'OD' => $offerId,
//        ]);
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
        // Log the incoming request details
        Log::info('CounterOffer endpoint called', [
            'offer_id' => $offerId,
            'user_id' => $request->user() ? $request->user()->id : null,
            'ip' => $request->ip(),
            'input' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            // Validate the request
            $validated = $request->validate([
                'price' => 'required|numeric|min:0',
                // Do NOT require offer_id in body since it's in the route
            ]);

            $user = $request->user(); // Current authenticated seller
            if (!$user) {
                Log::warning('No authenticated user found for counterOffer', [
                    'offer_id' => $offerId,
                    'ip' => $request->ip(),
                ]);
                return $this->errorResponse('Unauthenticated', 401);
            }

            $baseOffer = Offer::findOrFail($offerId);

            // Log the base offer details
            Log::info('Base offer retrieved', [
                'offer_id' => $baseOffer->id,
                'product_id' => $baseOffer->product_id,
                'buyer_id' => $baseOffer->buyer_id,
                'seller_id' => $baseOffer->seller_id,
                'price' => $baseOffer->price,
            ]);

            $newOffer = $this->offerService->counterOffer(
                baseOffer: $baseOffer,
                user: $user,
                price: (float) $validated['price'],
                message: $request->input('message') // optional
            );

            // Log successful counter offer creation
            Log::info('Counter offer created successfully', [
                'new_offer_id' => $newOffer->id,
                'price' => $newOffer->price,
                'user_id' => $user->id,
            ]);

            return $this->createdResponse($newOffer, 'Counter offer created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed in counterOffer', [
                'offer_id' => $offerId,
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);
            return $this->errorResponse($e->errors(), 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log when offer is not found
            Log::error('Offer not found in counterOffer', [
                'offer_id' => $offerId,
                'user_id' => $user ? $user->id : null,
            ]);
            return $this->errorResponse('Offer not found', 404);
        } catch (\Throwable $e) {
            // Log any other unexpected errors
            Log::error('Error in counterOffer', [
                'offer_id' => $offerId,
                'user_id' => $user ? $user->id : null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
