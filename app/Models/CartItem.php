<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = ['cart_id', 'product_id', 'quantity','price','price_source','offer_id'];

    protected $hidden = ['created_at', 'updated_at'];

    // Append the computed total_price attribute to every response.
    protected $appends = ['total_price'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
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
