<?php

namespace App\Observers;

use App\Mail\ProductPosted;
use App\Mail\ProductStatusUpdate;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        Log::info('Product created');
        Mail::to(config('app.admin_email'))->send(new ProductPosted($product));
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        Log::info('Product updated');

        if ($product->isDirty('approval_status') && in_array($product->approval_status, ['approved', 'rejected'])) {
            Mail::to($product->user->email)->send(new ProductStatusUpdate($product));
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
