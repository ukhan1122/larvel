<?php


use App\Http\Controllers\Api\V1\User\FollowerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::post('users/{userId}/follow', [FollowerController::class, 'follow']);
    Route::delete('users/{userId}/unfollow', [FollowerController::class, 'unfollow']);
    Route::get('users/followers', [FollowerController::class, 'getFollowers']);
    Route::get('users/following', [FollowerController::class, 'getFollowing']);
});
