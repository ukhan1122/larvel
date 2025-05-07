<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = auth()->user();

        // Optionally delete the old profile picture if it's in your domain
        if ($user->profile_picture) {
            $oldPath = str_replace(asset('storage') . '/', '', $user->profile_picture);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $file = $request->file('profile_picture');
        $filename = time() . '_' . $file->getClientOriginalName();
        $relativePath = $file->storeAs('profile_pictures', $filename, 'public');

        $fullUrl = asset(Storage::url($relativePath));

        $user->update(['profile_picture' => $fullUrl]);

        $resource = UserResource::make($user);

        return $this->successResponse($resource, 'Profile picture updated successfully');
    }


    public function wallet() {
        $user = auth()->user();
        $wallet = $user->wallet;
        $transactions = $wallet->transactions;

        return $this->successResponse([
            'wallet' => $wallet,
        ]);
    }
}
