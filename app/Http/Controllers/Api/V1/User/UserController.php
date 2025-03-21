<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Get public profile for a user.
     */
    public function profile($userId)
    {
        $user = User::withCount(['followers', 'following', 'products'])->findOrFail($userId);
        $user->average_rating = $user->averageRating();

        return $this->successResponse($user);
    }
}
