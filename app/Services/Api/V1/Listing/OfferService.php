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
     * @param User $user  Authenticated user making the offer.
     * @param  array  $data  Validated data (product_id, offer_price, message).
     * @return Offer
     * @throws Exception
     */
    public function createOffer(User $user, array $data)
    {
        $product = Product::findOrFail($data['product_id']);

        // Prevent self-offer.
        if ($product->user_id == $user->id) {
            throw new Exception('You cannot make an offer on your own product');
        }

        // Validate that the offer is less than the listed price.
        if ($data['offer_price'] >= $product->price) {
            throw new Exception('Offer must be less than the product price');
        }

        // Ensure the offer is at least 10% of the product price.
        $minOffer = $product->price * 0.1;
        if ($data['offer_price'] < $minOffer) {
            throw new Exception('Offer must be at least 10% of the product price');
        }

        // Create the offer record.
        $offer = Offer::create([
            'product_id'  => $product->id,
            'offerer_id'  => $user->id,
            'offer_price' => $data['offer_price'],
            'message'     => $data['message'] ?? null,
            'status'      => 'pending',
        ]);

        return $offer;
    }

    /**
     * Get offers received by the authenticated user (i.e. on products owned by the user).
     *
     * @param  User  $user
     * @return Collection
     */
    public function getReceivedOffers($user)
    {
        return Offer::whereHas('product', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('product', 'offerer')->get();
    }

    /**
     * Get offers sent by the authenticated user.
     *
     * @param  User  $user
     * @return Collection
     */
    public function getSentOffers($user)
    {
        return Offer::where('offerer_id', $user->id)
            ->with('product')
            ->get();
    }

    /**
     * Accept an offer.
     * Only the product owner can accept the offer.
     *
     * @param  User  $user  Authenticated product owner.
     * @param  int  $offerId
     * @return Offer
     * @throws Exception
     */
    public function acceptOffer($user, $offerId)
    {
        $offer = Offer::with('product')->findOrFail($offerId);

        if ($offer->product->user_id != $user->id) {
            throw new Exception('Unauthorized: You are not the owner of this product');
        }
        if (!in_array($offer->status, ['pending', 'countered'])) {
            throw new Exception('Offer cannot be accepted');
        }

        $offer->status = 'accepted';
        $offer->save();

        return $offer;
    }

    /**
     * Reject an offer.
     * Only the product owner can reject the offer.
     *
     * @param  User  $user
     * @param  int  $offerId
     * @return Offer
     * @throws Exception
     */
    public function rejectOffer($user, $offerId)
    {
        $offer = Offer::with('product')->findOrFail($offerId);

        if ($offer->product->user_id != $user->id) {
            throw new Exception('Unauthorized: You are not the owner of this product');
        }
        if (!in_array($offer->status, ['pending', 'countered'])) {
            throw new Exception('Offer cannot be rejected');
        }

        $offer->status = 'rejected';
        $offer->save();

        return $offer;
    }

    /**
     * Counter an offer.
     * The product owner may propose a counter offer.
     *
     * @param  User  $user  Authenticated product owner.
     * @param  int  $offerId
     * @param  array  $data  Validated data (counter_price, message).
     * @return Offer
     * @throws Exception
     */
    public function counterOffer($user, $offerId, array $data)
    {
        $offer = Offer::with('product')->findOrFail($offerId);

        if ($offer->product->user_id != $user->id) {
            throw new Exception('Unauthorized: You are not the owner of this product');
        }
        if (!in_array($offer->status, ['pending', 'countered'])) {
            throw new Exception('Offer cannot be countered');
        }

        if ($data['counter_price'] >= $offer->product->price) {
            throw new Exception('Counter offer must be less than the product price');
        }

        $minOffer = $offer->product->price * 0.1;
        if ($data['counter_price'] < $minOffer) {
            throw new Exception('Counter offer must be at least 10% of the product price');
        }

        $offer->status = 'countered';
        $offer->counter_price = $data['counter_price'];
        if (isset($data['message'])) {
            $offer->message = $data['message'];
        }
        $offer->save();

        return $offer;
    }
}
