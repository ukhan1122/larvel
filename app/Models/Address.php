<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province_or_region',
        'zip_or_postal_code',
        'address_type',
        'is_guest_address'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'delivery_address_id');
    }
}

