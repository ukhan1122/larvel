<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RatingController extends Controller
{
    use ApiResponse;

    /**
     * Rate a user (authenticated users only).
     */
    public function rateUser(Request $request, $userId)
    {
        // Validate input: rating (1–5), a required comment, and a required pictures array.
        $validated = $request->validate([
            'rating'    => 'required|integer|min:1|max:5',
            'comment'   => 'required|string',
            'pictures'  => 'required|array|min:1|max:3',
            'pictures.*'=> 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $raterId = auth()->id();
        if ($raterId == $userId) {
            return $this->errorResponse('You cannot rate yourself', 403);
        }

        // Create or update the rating record.
        $rating = Rating::updateOrCreate(
            ['user_id' => $userId, 'rater_id' => $raterId],
            [
                'rating'  => $validated['rating'],
                'comment' => $validated['comment'],
            ]
        );

        // Delete any previous pictures associated with this rating.
        $rating->pictures()->delete();


        // Process and store each uploaded picture.
        $pictures = $request->file('pictures');
        foreach ($pictures as $picture) {
            $filename = time() . '_' . $picture->getClientOriginalName();
            $relativePath = $picture->storeAs('ratings', $filename, 'public');
            $fullUrl = asset(Storage::url($relativePath));

            $rating->pictures()->create([
                'picture' => $fullUrl,
            ]);
        }

        // Reload the rating with its pictures.
        $rating->load('pictures');


        $ratedUser = User::findOrFail($userId);

        ActivityLogHelper::logUserRating(request()->user(), $ratedUser, $rating);



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

        $ratings->load('pictures');

        return $this->successResponse([
            'ratings' => $ratings,
            'average' => $averageRating
        ]);
    }
}
