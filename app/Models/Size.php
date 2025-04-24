<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'chest', 'waist', 'hips', 'inseam', 'sleeve', 'shoulder', 'standard_size'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

}
