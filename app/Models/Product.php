<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = 'products';

    protected $fillable = [
        'user_id',
        'description',
        'title',
        'category_id',
        'brand_id',
        'condition_id',
        'country',
        'location',
        'city',
        'address_id',
        'shipping_type',
        'price'
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function brand() {
        return $this->belongsTo(Brand::class);
    }

    public function condition() {
        return $this->belongsTo(Condition::class);
    }

    public function address() {
        return $this->belongsTo(Address::class);
    }

    public function photos() {
        return $this->hasMany(Photo::class);
    }

}
