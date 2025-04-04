<?php

namespace App\Services\Api\V1\Listing;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OfferService
{
    /**
     * Create a new offer on a product.
     *
     * @param User $user  The authenticated user making the offer.
     * @param  array  $data  Validated data: product_id, offer_price, message.
     * @return Offer
     * @throws \Exception
     */
    public function createOffer($user, array $data)
    {
        $product = Product::findOrFail($data['product_id']);

        // Prevent self-offer.
        if ($product->user_id == $user->id) {
            throw new \Exception('You cannot make an offer on your own product');
        }

        // Validate that the offered price is less than the listed price.
        if ($data['offer_price'] >= $product->price) {
            throw new \Exception('Offer must be less than the product price');
        }

        // Ensure the offer is at least 10% of the product price.
        $minOffer = $product->price * 0.1;
        if ($data['offer_price'] < $minOffer) {
            throw new \Exception('Offer must be at least 10% of the product price');
        }

        // Create the offer record.
        $offer = Offer::create([
            'product_id'  => $product->id,
            'offerer_id'  => $user->id,
            'offer_price' => $data['offer_price'],
            'message'     => $data['message'] ?? null,
            'status'      => 'pending',
            'last_reply_by' => $user->id,
        ]);

        return $offer;
    }

    /**
     * Get offers received by the authenticated user (i.e. on products owned by the user).
     */
    public function getReceivedOffers($user)
    {
        return Offer::whereHas('product', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('product', 'offerer', 'lastReplyBy')->get();
    }

    /**
     * Get offers sent by the authenticated user.
     */
    public function getSentOffers($user)
    {
        return Offer::where('offerer_id', $user->id)
            ->with('product', 'lastReplyBy')
            ->get();
    }

    /**
     * Accept an offer.
     * Once accepted, the negotiation is closed.
     *
     * @param User $user  The authenticated user.
     * @param  int  $offerId
     * @return Offer
     * @throws \Exception
     */
    public function acceptOffer($user, $offerId)
    {
        $offer = Offer::with('product')->findOrFail($offerId);

        // Ensure the user is a participant (either the offerer or product owner).
        if (!in_array($user->id, [$offer->offerer_id, $offer->product->user_id])) {
            throw new \Exception('Unauthorized: You are not a participant in this negotiation');
        }
        // Once accepted (or rejected), the negotiation is closed.
        if (in_array($offer->status, ['accepted', 'rejected'])) {
            throw new \Exception('Offer is closed and cannot be interacted with.');
        }

        $offer->status = 'accepted';
        $offer->last_reply_by = $user->id;  // Update last reply to this user.
        $offer->save();

        return $offer;
    }

    /**
     * Reject an offer.
     * Once rejected, the negotiation is closed.
     *
     * @param User $user
     * @param  int  $offerId
     * @return Offer
     * @throws \Exception
     */
    public function rejectOffer($user, $offerId)
    {
        $offer = Offer::with('product')->findOrFail($offerId);

        // Ensure the user is a participant.
        if (!in_array($user->id, [$offer->offerer_id, $offer->product->user_id])) {
            throw new \Exception('Unauthorized: You are not a participant in this negotiation');
        }
        // Prevent any action if the offer is already closed.
        if (in_array($offer->status, ['accepted', 'rejected'])) {
            throw new \Exception('Offer is closed and cannot be interacted with.');
        }

        $offer->status = 'rejected';
        $offer->last_reply_by = $user->id;
        $offer->save();

        return $offer;
    }

    /**
     * Counter an offer.
     * Either participant may propose a counter offer.
     * Once the offer is accepted or rejected, no further interactions are allowed.
     *
     * @param User $user  The authenticated user.
     * @param  int  $offerId
     * @param  array  $data  Validated data: counter_price, message.
     * @return Offer
     * @throws \Exception
     */
    public function counterOffer($user, $offerId, array $data)
    {
        $offer = Offer::with('product')->findOrFail($offerId);

        // Ensure the user is a participant.
        if (!in_array($user->id, [$offer->offerer_id, $offer->product->user_id])) {
            throw new \Exception('Unauthorized: You are not a participant in this negotiation');
        }
        // Prevent counter if negotiation is closed.
        if (in_array($offer->status, ['accepted', 'rejected'])) {
            throw new \Exception('Offer is closed and cannot be countered.');
        }

        // Validate the new counter offer price.
        if ($data['counter_price'] >= $offer->product->price) {
            throw new \Exception('Counter offer must be less than the product price');
        }
        $minOffer = $offer->product->price * 0.1;
        if ($data['counter_price'] < $minOffer) {
            throw new \Exception('Counter offer must be at least 10% of the product price');
        }

        // Update the offer with the new counter price and message.
        $offer->offer_price = $data['counter_price'];
        $offer->status = 'countered';
        if (isset($data['message'])) {
            $offer->message = $data['message'];
        }
        // Update the last reply field with the current user.
        $offer->last_reply_by = $user->id;
        $offer->save();

        return $offer;
    }
}
