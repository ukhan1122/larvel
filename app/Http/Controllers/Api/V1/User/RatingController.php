<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    use ApiResponse;

    /**
     * Rate a user (authenticated users only).
     */
    public function rateUser(Request $request, $userId)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $raterId = auth()->id();
        if ($raterId == $userId) {
            return $this->errorResponse('You cannot rate yourself', 403);
        }

        $rating = Rating::updateOrCreate(
            ['user_id' => $userId, 'rater_id' => $raterId],
            ['rating' => $request->input('rating'), 'comment' => $request->input('comment')]
        );

        return $this->successResponse($rating, 'Rating submitted successfully');
    }

    /**
     * Get public ratings for a user.
     */
    public function getUserRatings($userId)
    {
        $user = User::findOrFail($userId);
        $ratings = $user->ratings()->with('rater')->get();
        $averageRating = $user->averageRating();

        return $this->successResponse([
            'ratings' => $ratings,
            'average' => $averageRating
        ]);
    }
}
