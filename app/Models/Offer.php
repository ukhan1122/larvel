<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class   Offer extends Model
{
    use HasFactory;

    protected $table = 'offers';

    protected $fillable = [
        'product_id',
        'seller_id',
        'buyer_id',
        'actor_id',
        'action',
        'price',
        'status',
        'message',
    ];

    /**
     * The product associated with the offer.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The user who made the offer.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * The user who is making the purchase (buyer).
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * The user who performed this action (could be buyer or seller).
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
