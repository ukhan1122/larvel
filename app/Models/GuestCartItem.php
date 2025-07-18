<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestCartItem extends Model
{
    use HasFactory;

    protected $fillable = ['guest_cart_id', 'product_id', 'quantity'];
    protected $appends = ['total_price'];
    public function cart()
    {
        return $this->belongsTo(GuestCart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the total price for this cart item.
     */
    public function getTotalPriceAttribute()
    {
        // Make sure the product relation is loaded.
        if ($this->relationLoaded('product') && $this->product) {
            return $this->quantity * $this->product->price;
        }
        return 0;
    }
}
