<?php


use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;


Route::get('users/{userId}/profile', [UserController::class, 'profile']);
Route::post('users/profile/picture', [UserController::class, 'updateProfilePicture'])->middleware(['auth:sanctum', 'role:user']);
