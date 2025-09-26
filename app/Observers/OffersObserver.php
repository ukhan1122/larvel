<?php

namespace App\Observers;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OffersObserver
{
    public bool $afterCommit = true;
    /**
     * Handle the Offer "created" event.
     */
    public function created(Offer $offer): void
    {
        // pull related models without re-querying if already loaded
        $offer->loadMissing(['product', 'seller', 'buyer']);

        $isBuyerAction = (int)$offer->buyer_id === (int)$offer->actor_id;

        // recipient + label
        $recipient   = $isBuyerAction ? $offer->seller : $offer->buyer;
        $statusLabel = $isBuyerAction ? 'Received' : 'Countered';

        if (!$recipient || empty($recipient->phone)) {
            Log::warning('Offer SMS skipped: recipient phone missing', [
                'offer_id' => $offer->id,
                'recipient_type' => $isBuyerAction ? 'seller' : 'buyer',
            ]);
            return;
        }

        $messageData = [
            'status'  => $statusLabel,
            'product' => $offer->product->title ?? ("Product #{$offer->product_id}"),
            'price'   => $offer->price,
        ];

        $payload = [
            'api_key'     => config('services.sendpk.api_key'),
            'sender'      => 'Closyyyy',
            'mobile'      => $recipient->phone,
            'template_id' => 10143,
            'message'     => json_encode($messageData),
            'format'      => 'json',
        ];

        try {
            Log::info('SendPK SMS payload:', $payload);

            $resp = Http::asForm()->post('https://sendpk.com/api/sms.php', $payload);

            Log::debug('SendPK API response', [
                'offer_id' => $offer->id,
                'http'     => $resp->status(),
                'body'     => $resp->body(),
                'json'     => $resp->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Offer SMS failed', [
                'offer_id' => $offer->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Offer "updated" event.
     */
    public function updated(Offer $offer): void
    {
        Log::info('Offer created');
        if ($offer->isDirty('status') && in_array($offer->status, ['accepted', 'rejected'])) {
            $prd = Product::find($offer->product_id);
            $buyer = User::find($offer->buyer_id);

            $messageData = [
                'status' => ucfirst($offer->status),
                'product' => $prd->title,
                'price' => $offer->price,
            ];

            $payload = [
                'api_key' => config('services.sendpk.api_key'),
                'sender' => 'Closyyyy',
                'mobile' => $buyer->phone,
                'template_id' => 10143,
                'message' => json_encode($messageData),
                'format' => 'json',
            ];

            try {
                \Log::info('SendPK SMS payload:', $payload);

                // Make the request and log both request and response
                $response = Http::asForm()->post('https://sendpk.com/api/sms.php', $payload);

                \Log::debug('SendPK API raw response:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed order sms: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Offer "deleted" event.
     */
    public function deleted(Offer $offer): void
    {
        //
    }

    /**
     * Handle the Offer "restored" event.
     */
    public function restored(Offer $offer): void
    {
        //
    }

    /**
     * Handle the Offer "force deleted" event.
     */
    public function forceDeleted(Offer $offer): void
    {
        //
    }
}
