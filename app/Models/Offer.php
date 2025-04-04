<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $table = 'offers';

    protected $fillable = [
        'product_id',
        'offerer_id',
        'offer_price',
        'status',
        'counter_price',
        'message',
        'last_reply_by'
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
    public function offerer()
    {
        return $this->belongsTo(User::class, 'offerer_id');
    }

    /**
     * Get the user who last replied.
     */
    public function lastReplyBy()
    {
        return $this->belongsTo(User::class, 'last_reply_by');
    }
}
