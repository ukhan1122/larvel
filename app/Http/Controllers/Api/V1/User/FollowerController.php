<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FollowerController extends Controller
{
    use ApiResponse;

    /**
     * Follow a user.
     */
    public function follow(Request $request, $userId)
    {
        $userToFollow = User::findOrFail($userId);
        $currentUser = auth()->user();

        if ($currentUser->id == $userToFollow->id) {
            return $this->errorResponse('You cannot follow yourself', 403);
        }

        $currentUser->following()->syncWithoutDetaching([$userToFollow->id]);

        activity()
            ->performedOn($userToFollow)
            ->causedBy($request->user())
            ->withProperties(['followed_user' => $userToFollow])
            ->log('user_followed');

        return $this->successResponse(null, 'User followed successfully');
    }

    /**
     * Unfollow a user.
     */
    public function unfollow(Request $request, $userId)
    {
        $userToUnfollow = User::findOrFail($userId);
        $currentUser = auth()->user();

        $currentUser->following()->detach($userToUnfollow->id);

        return $this->successResponse(null, 'User unfollowed successfully');
    }

    /**
     * Get followers of the authenticated user.
     */
    public function getFollowers(Request $request)
    {
        $user = auth()->user();
        $followers = $user->followers()->paginate(10);

        return $this->successResponse($followers);
    }

    /**
     * Get following list of the authenticated user.
     */
    public function getFollowing(Request $request)
    {
        $user = auth()->user();
        $following = $user->following()->paginate(10);

        return $this->successResponse($following);
    }

}
