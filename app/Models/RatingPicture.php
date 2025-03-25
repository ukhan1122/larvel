<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingPicture extends Model
{
    use HasFactory;

    protected $table = 'rating_pictures';

    protected $fillable = ['rating_id', 'picture'];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }
}
