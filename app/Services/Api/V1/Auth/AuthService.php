<?php

namespace App\Services\Api\V1\Auth;

use App\Http\Resources\Api\V1\User\UserResource;
use App\Repositories\V1\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo) {
        $this->userRepo = $userRepo;
    }
    public function registerUser(array $data)
    {
        // Only hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['role'] = 'user';

        return $this->userRepo->create($data);
    }

    public function loginUser(array $credentials)
    {
        $loginField = $credentials['login']; // Single input: phone, email, or username
        $password = $credentials['password'];

        // Determine the field type
        $field = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' :
            (preg_match('/^\d{10,15}$/', $loginField) ? 'phone' : 'username');

        // Attempt login
        if (Auth::attempt([$field => $loginField, 'password' => $password])) {
            $user = Auth::user();
            $token = $user->createToken("User.{$user->id}.AuthToken")->plainTextToken;
            return ['user' => new UserResource($user), 'token' => $token];
        }

        return null;
    }

    public function logoutUser() {
        $user = Auth::user();
        $user->tokens()->delete();
    }

}
