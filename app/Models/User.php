<?php

namespace App\Models;

use App\Notifications\Api\V1\Auth\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'location',
        'username',
        'profile_picture'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Add this method to override the default notification
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    public function preferences() {
        return $this->hasOne(UserPreference::class);
    }

    public function addresses() {
        return $this->hasMany(Address::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }

    // Users that follow this user
    public function followers() {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id')->withTimestamps();
    }

    // Users that this user is following
    public function following() {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id')->withTimestamps();
    }

    // Products liked by this user
    public function likedProducts() {
        return $this->belongsToMany(Product::class, 'product_likes', 'user_id', 'product_id')->withTimestamps();
    }

    // Products saved by this user
    public function savedProducts() {
        return $this->belongsToMany(Product::class, 'product_saves', 'user_id', 'product_id')->withTimestamps();
    }

    // Ratings given to this user
    public function ratings() {
        return $this->hasMany(Rating::class, 'user_id');
    }

    // Ratings given by this user
    public function givenRatings() {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    // Compute average rating
    public function averageRating() {
        return $this->ratings()->avg('rating');
    }

    /**
     * The conversations this user participates in.
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')->withTimestamps();
    }

    /**
     * All messages sent by this user.
     */
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function sentOffers()
    {
        return $this->hasMany(Offer::class, 'offerer_id');
    }

    public function bankDetail()
    {
        return $this->hasOne(BankDetail::class);
    }

    public function shop() {
        return $this->hasOne(Shop::class);
    }


}

