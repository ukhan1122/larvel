<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Listing\OfferController;

Route::prefix('products/offers')->middleware(['auth:sanctum', 'role:user'])->group(function () {
    // Create a new offer.
    Route::post('create', [OfferController::class, 'createOffer']);

    // Get offers received (for products owned by the authenticated user).
    Route::get('received', [OfferController::class, 'getReceivedOffers']);

    // Get offers sent by the authenticated user.
    Route::get('sent', [OfferController::class, 'getSentOffers']);

    // Accept an offer.
    Route::put('{offerId}/accept', [OfferController::class, 'acceptOffer']);

    // Reject an offer.
    Route::put('{offerId}/reject', [OfferController::class, 'rejectOffer']);

    // Counter an offer.
    Route::put('{offerId}/counter', [OfferController::class, 'counterOffer']);
});
