<?php

namespace App\Services\Api\V1\Listing;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfferService
{
    /**
     * Create a new offer on a product.
     *
     * @param User $user The authenticated user making the offer.
     * @param array $data Validated data: product_id, offer_price, message.
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
        $minOffer = $product->price * 0.5;
        if ($data['offer_price'] < $minOffer) {
            throw new \Exception('Offer must be at least 50% of the product price');
        }
        // Check for existing offer with same product_id, seller_id, and buyer_id
        $existingOffer = Offer::where('product_id', $product->id)
            ->where('seller_id', $product->user_id)
            ->where('buyer_id', $user->id)
            ->whereIn('status', ['accepted','pending'])
            ->exists();
        if ($existingOffer) {
            throw new \Exception('Offer already exists for this product and buyer');
        }
        // Create the offer row (append-only).
        $offer = Offer::create([
            'product_id' => $product->id,
            'seller_id' => $product->user_id,
            'buyer_id' => $user->id,
            'actor_id' => $user->id,
            'action' => 'offer',
            'price' => $data['offer_price'],
            'message' => $data['message'] ?? null,
            'status' => 'pending',
        ]);



        return $offer;
    }

    public function updateOfferPrice($user, $offerId, array $data)
    {
        // Use product_id from the request data to find the product
        $product = Product::findOrFail($data['product_id']);

        // Find the offer using offerId and ensure it matches product_id, seller_id, buyer_id, and actor_id
        $offer = Offer::where('id', $offerId)
            ->where('product_id', $product->id)
            ->where('seller_id', $product->user_id)
            ->where('buyer_id', $user->id)
            ->where('actor_id', $user->id)
            ->firstOrFail();

        // Validate that the updated price is less than the listed price
        if ($data['offer_price'] >= $product->price) {
            throw new \Exception('Offer must be less than the product price');
        }

        // Ensure the updated price is at least 50% of the product price
        $minOffer = $product->price * 0.5;
        if ($data['offer_price'] < $minOffer) {
            throw new \Exception('Offer must be at least 50% of the product price');
        }

        // Update only the price
        $offer->update(['price' => $data['offer_price']]);

        return $offer;
    }


    /**
     * Get offers received by the authenticated user (i.e. on products owned by the user).
     */
    public function getReceivedOffers($user)
    {
        $latestPerThread = DB::table('offers')
            ->select(
                'product_id',
                'buyer_id',
                'seller_id',
                DB::raw('MAX(id) as last_id')
            )
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
            })
            ->groupBy('product_id', 'buyer_id', 'seller_id');

        // 2) Subquery: pick ONE photo per product (cover) -> first by id
        $coverPhoto = DB::table('photos')
            ->select('product_id', DB::raw('MIN(id) as photo_id'))
            ->groupBy('product_id');

        // 3) Join everything together
        $offers = DB::table('offers as o')
            ->joinSub($latestPerThread, 'latest', function ($join) {
                $join->on('o.id', '=', 'latest.last_id');
            })
            ->join('products as p', 'p.id', '=', 'o.product_id')
            // cover photo join
            ->leftJoinSub($coverPhoto, 'cp', function ($join) {
                $join->on('p.id', '=', 'cp.product_id');
            })
            ->leftJoin('photos as ph', 'ph.id', '=', 'cp.photo_id')
            // users
            ->join('users as b', 'b.id', '=', 'o.buyer_id')
            ->join('users as s', 's.id', '=', 'o.seller_id')
            ->select(
                'o.id',
                'o.product_id',
                'o.buyer_id',
                'o.seller_id',
                'o.actor_id',
                'o.action',
                'o.price',
                'o.status',
                'o.message',
                'o.created_at',

                'p.title as product_title',
                'p.price as product_price',

                // first/cover image for product
                'ph.image_path as product_image',

                // display names
                DB::raw('COALESCE(b.first_name, b.username) as buyer_name'),
                DB::raw('COALESCE(s.first_name, s.username) as seller_name')
            )
            ->orderByDesc('o.created_at')
            ->get();

        return response()->json($offers);

    }

    public function getOfferConversation($request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'buyer_id'   => 'required|integer|exists:users,id',
            'seller_id'  => 'required|integer|exists:users,id',
        ]);

        // one cover photo per product (first by id)
        $coverPhoto = DB::table('photos')
            ->select('product_id', DB::raw('MIN(id) as photo_id'))
            ->groupBy('product_id');

        $rows = DB::table('offers as o')
            ->join('products as p', 'p.id', '=', 'o.product_id')
            ->leftJoinSub($coverPhoto, 'cp', function ($join) {
                $join->on('p.id', '=', 'cp.product_id');
            })
            ->leftJoin('photos as ph', 'ph.id', '=', 'cp.photo_id')
            ->join('users as b', 'b.id', '=', 'o.buyer_id')
            ->join('users as s', 's.id', '=', 'o.seller_id')
            ->select(
                'o.id',
                'o.product_id',
                'o.buyer_id',
                'o.seller_id',
                'o.actor_id',
                'o.price',
                'o.status',
                'o.action',
                'o.created_at',

                'p.title as product_title',
                'p.price as product_price',

                // product image
                'ph.image_path as product_image',

                // names
                DB::raw('COALESCE(b.first_name, b.username) as buyer_name'),
                DB::raw('COALESCE(s.first_name, s.username) as seller_name'),

                // profile pictures
                'b.profile_picture as buyer_profile_picture',
                's.profile_picture as seller_profile_picture',

                // per-message actor avatar + name
                DB::raw("CASE WHEN o.actor_id = b.id THEN b.profile_picture ELSE s.profile_picture END as actor_profile_picture"),
                DB::raw("CASE WHEN o.actor_id = b.id THEN COALESCE(b.first_name, b.username) ELSE COALESCE(s.first_name, s.username) END as actor_name")
            )
            ->where('o.product_id', $request->product_id)
            ->where('o.buyer_id', $request->buyer_id)
            ->where('o.seller_id', $request->seller_id)
            ->orderBy('o.created_at', 'asc') // chat usually chronological
            ->get();

        // make paths absolute if stored as relative
        $rows = $rows->map(function ($r) {
            foreach (['product_image','buyer_profile_picture','seller_profile_picture','actor_profile_picture'] as $f) {
                if (!empty($r->$f) && !Str::startsWith($r->$f, ['http://','https://','/'])) {
                    $r->$f = url('storage/'.$r->$f); // adjust base path if different
                }
            }
            return $r;
        });

        return response()->json($rows);
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
     * @param User $user The authenticated user.
     * @param int $offerId
     * @return Offer
     * @throws \Exception
     */
    public function acceptOffer($user, $offerId)
    {
        // Fetch the offer with related product
        $offer = Offer::with('product')->findOrFail($offerId);

        // Check if the offer is pending
        if ($offer->status !== 'pending') {
            throw new \Exception('Offer is not pending and cannot be accepted.');
        }

        // Ensure the user is the recipient (not the actor who sent the offer)
        if ($user->id === $offer->actor_id) {
            throw new \Exception('You cannot accept your own offer.');
        }

        // Verify the user is a participant (either buyer or seller)
        if ($user->id !== $offer->seller_id && $user->id !== $offer->buyer_id) {
            throw new \Exception('Unauthorized: You are not a participant in this negotiation.');
        }

        // Update the offer status to accepted
        $offer->status = 'accepted';
        $offer->save();

        return $offer;
    }

    /**
     * Reject an offer.
     * Once rejected, the negotiation is closed.
     *
     * @param User $user
     * @param int $offerId
     * @return Offer
     * @throws \Exception
     */
    public function rejectOffer($user, $offerId)
    {
        // Fetch the offer with related product
        $offer = Offer::with('product')->findOrFail($offerId);

        // Check if the offer is pending
        if ($offer->status !== 'pending') {
            throw new \Exception('Offer is not pending and cannot be accepted.');
        }

        // Ensure the user is the recipient (not the actor who sent the offer)
        if ($user->id === $offer->actor_id) {
            throw new \Exception('You cannot accept your own offer.');
        }

        // Verify the user is a participant (either buyer or seller)
        if ($user->id !== $offer->seller_id && $user->id !== $offer->buyer_id) {
            throw new \Exception('Unauthorized: You are not a participant in this negotiation.');
        }

        // Update the offer status to accepted
        $offer->status = 'rejected';
        $offer->save();

        return $offer;
    }

    /**
     * Counter an offer.
     * Either participant may propose a counter offer.
     * Once the offer is accepted or rejected, no further interactions are allowed.
     *
     * @param User $user The authenticated user.
     * @param int $offerId
     * @param array $data Validated data: counter_price, message.
     * @return Offer
     * @throws \Exception
     */
    public function counterOffer(Offer $baseOffer, User $user, float $price, ?string $message = null): Offer
    {
        return DB::transaction(function () use ($baseOffer, $user, $price, $message) {
            $product = Product::findOrFail($baseOffer->product_id);

            // Only seller can counter
            if ($product->user_id !== $user->id) {
                throw new \Exception('Only the seller can make a counter offer.');
            }

            // Price rules
            if ($price >= (float) $product->price) {
                throw new \Exception('Counter must be less than the product price.');
            }
            $min = (float) $product->price * 0.5;
            if ($price < $min) {
                throw new \Exception("Counter must be at least 50% of the product price.");
            }

            // Close any buyer-pending offer in the same thread
            Offer::where('product_id', $baseOffer->product_id)
                ->where('seller_id',  $baseOffer->seller_id)
                ->where('buyer_id',   $baseOffer->buyer_id)
                ->where('status',     'pending')
                ->where('actor_id',   $baseOffer->buyer_id) // pending FROM buyer
                ->update([
                    'status'     => 'countered',  // or 'rejected' if you prefer
                    'updated_at' => now(),
                ]);

            // Append the seller counter
            return Offer::create([
                'product_id' => $baseOffer->product_id,
                'seller_id'  => $baseOffer->seller_id,
                'buyer_id'   => $baseOffer->buyer_id,
                'actor_id'   => $user->id,
                'action'     => 'counter',
                'price'      => $price,
                'message'    => $message,
                'status'     => 'pending',
            ]);
        });
    }
}
