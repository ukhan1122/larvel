<?php

namespace App\Helpers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Rating;
use App\Models\User;
use App\Services\Api\V1\Activity\ActivityLoggerService;

class ActivityLogHelper
{
    public static function logProductPosted(Product $product): void
    {
        self::logActivity(
            log: 'product',
            event: 'created_product',
            causer: $product->user,
            subject: $product,
            properties: [
                'data' => $product->load('photos'),
                'title' => 'Product posted',
                'message' => 'New product posted by a vendor. Tap to see',
                'type' => 'broadcast'
            ],
            message: 'New product posted by a vendor'
        );
    }

    public static function logUserFollow(User $follower, User $followed): void
    {
        $follower->setRelation(
            'following',
            $follower->following()->where('users.id', $followed->id)->get()
        );

        self::logActivity(
            log: 'follow',
            event: 'user_followed',
            causer: $follower,
            subject: $followed,
            properties: [
                'data' => $follower,
                'title' => 'User followed',
                'message' => "Started following {$followed->first_name} {$followed->last_name}",
                'type' => 'individual'
            ],
            message: 'Followed a user'
        );
    }

    public static function logUserRating(User $rater, User $rated, Rating $rating): void
    {
        self::logActivity(
            log: 'rating',
            event: 'user_rated',
            causer: $rater,
            subject: $rated,
            properties: [
                'data' => $rating->load('pictures'),
                'title' => 'Rating given',
                'message' => 'Rating given. Tap to see',
                'type' => 'individual'
            ],
            message: 'Rated a user'
        );
    }

    public static function logOrderPlaced(Order $order): void
    {
        self::logActivity(
            log: 'order',
            event: 'order_placed',
            causer: $order->buyer,
            subject: $order,
            properties: [
                'data' => $order->load('buyer'),
                'title' => 'Order placed',
                'message' => 'An order was placed by a user. Tap to see',
                'type' => 'broadcast'
            ],
            message: 'An order is placed'
        );
    }

    /**
     * Generalized logger method to avoid repetition
     */
    private static function logActivity(
        string $log,
        string $event,
        User $causer,
        mixed $subject,
        array $properties,
        string $message
    ): void {
        ActivityLoggerService::log()
            ->useLog($log)
            ->event($event)
            ->causedBy($causer)
            ->performedOn($subject)
            ->withProperties($properties)
            ->logMessage($message);
    }
}
