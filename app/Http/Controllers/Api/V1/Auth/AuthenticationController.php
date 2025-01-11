<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterUserRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Services\Api\V1\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuthenticationController extends Controller
{
    use ApiResponse;
    protected $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function register(RegisterUserRequest $request) {
        $input = $request->validated();

        $user = $this->authService->registerUser($input);
        $resource = new UserResource($user);
        return $this->createdResponse($resource, __('responses.auth.success.register'));
    }

    public function login(LoginRequest $request) {
        $input = $request->validated();

        $attempt = $this->authService->loginUser($input);

        if ($attempt) {
            return $this->successResponse($attempt, __('responses.auth.success.login'));
        }

        return $this->errorResponse(message: __('responses.auth.failed.login'));
    }

    public function logout(Request $request) {
        $this->authService->logoutUser();
        return $this->successResponse(message: __('responses.auth.success.logout'));
    }


}
