<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Listing\OfferController;

Route::prefix('products/offers')->middleware(['auth:sanctum', 'role:user'])->group(function () {
    // Create a new offer.
    Route::post('create', [OfferController::class, 'createOffer']);
    Route::post('update/{offerId}', [OfferController::class, 'updateOfferPrice']);

    // Get offers received (for products owned by the authenticated user).
    Route::get('received', [OfferController::class, 'getReceivedOffers']);
    Route::get('conversations', [OfferController::class, 'getOfferConversation']);

    // Get offers sent by the authenticated user.
    Route::get('sent', [OfferController::class, 'getSentOffers']);

    // Accept an offer.
    Route::post('{offerId}/accept', [OfferController::class, 'acceptOffer']);

    // Reject an offer.
    Route::post('{offerId}/reject', [OfferController::class, 'rejectOffer']);

    // Counter an offer.
    Route::post('{offerId}/counter', [OfferController::class, 'counterOffer']);
});
