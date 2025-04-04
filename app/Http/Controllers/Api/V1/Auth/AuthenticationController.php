<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterUserRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\Api\V1\Auth\VerifyEmail;
use App\Repositories\V1\Contracts\UserRepositoryInterface;
use App\Services\Api\V1\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Registered;
class AuthenticationController extends Controller
{
    use ApiResponse;
    protected $authService;
    protected $userRepo;

    public function __construct(AuthService $authService, UserRepositoryInterface $userRepo) {
        $this->authService = $authService;
        $this->userRepo = $userRepo;
    }

    public function register(RegisterUserRequest $request) {
        $input = $request->validated();

        $user = $this->authService->registerUser($input);

        // Attempt to send the email verification notification
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            Log::error('Email verification failed: ' . $e->getMessage());
        }

        $resource = new UserResource($user);

        $preferences = UserPreference::create(['user_id' => $user->id]);

        $user->assignRole('user');

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

    public function forgotPassword(Request $request) {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? $this->successResponse(__($status))
            : $this->errorResponse(__($status));

    }
    public function setNewPassword(string $token) {
        return view('auth.password_reset', ['token' => $token, 'email' => request()->input('email')]);

    }

    public function reset(ResetPasswordRequest $request)
    {
       $input = $request->validated();

        $status = Password::reset(
            $input,
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->successResponse(message: __('responses.auth.success.password_reset'))
            : $this->errorResponse(__($status));
    }

    public function resetSuccess() {
        return view('auth.password_reset_success');
    }

    public function sendVerificationEmail(Request $request)
    {
        $user = $this->userRepo->findByEmail($request->input('email'));
        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(message: __('responses.auth.success.email_verification_already'));
        }

        $user->sendEmailVerificationNotification();

        return $this->successResponse(message: __('responses.auth.success.account_verification_email_sent'));
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = $this->userRepo->find($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->forbiddenResponse(__('responses.auth.failed.email_verification'));
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(message: __('responses.auth.success.email_verification_already'));
        }

        $user->markEmailAsVerified();

        return $this->successResponse(message: __('responses.auth.success.email_verified'));
    }


}
