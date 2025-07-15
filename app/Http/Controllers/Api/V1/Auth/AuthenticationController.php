<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterUserRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\TempOtp;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\Api\V1\Auth\VerifyEmail;
use App\Repositories\V1\Contracts\UserRepositoryInterface;
use App\Services\Api\V1\Auth\AuthService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $phone = $request->get('phone');

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $relativePath = $file->storeAs('profile_pictures', $filename, 'public');

            // Store the full URL instead of just the path
            $input['profile_picture'] = asset(Storage::url($relativePath));
        }
        $user = User::where('phone', $phone)->firstOrFail();
        $user->update($input);



        $resource = new UserResource($user);


        return $this->createdResponse($resource, __('responses.auth.success.register'));
    }

    public function login(LoginRequest $request) {
        $input = $request->all();

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

        if ($status === Password::PASSWORD_RESET) {
            return redirect('https://closyyyy.com/seller/login')
                ->with('status', __('responses.auth.success.password_reset'));
        }

        return $this->errorResponse(__($status));
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

        return redirect('https://closyyyy.com/seller/login')
        ->with('status', __('responses.auth.success.email_verified'));

    }



    public function verifyPhone(Request $request)
    {
        logger($request->all());
        $request->validate([
            'phone' => 'required|string'
        ]);

        $otp = rand(100000, 999999); // You can still generate it for later use
        $expiresAt = Carbon::now()->addSeconds(60);

        // Save or update OTP
        TempOtp::updateOrCreate(
            ['phone' => $request->phone],
            ['otp' => $otp, 'expires_at' => $expiresAt]
        );

        $accessToken = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        // Test with built-in "hello_world" template

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $request->phone, // make sure this is in E.164 format like '923224055946'
            'type' => 'template',
            'template' => [
                'name' => 'closyyyy_otp_en',
                'language' => [
                    'code' => 'en_US'
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otp
                            ]
                        ]
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otp
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://graph.facebook.com/v22.0/{$phoneNumberId}/messages", $payload);

        Log::debug('WhatsApp API Response: ' . $response->body());

        if ($response->successful()) {
            return response()->json(['message' => 'OTP (test) sent successfully']);
        } else {
            return response()->json([
                'message'     => 'Failed to send test WhatsApp message',
                'meta_error'  => $response->json(),
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        logger($request->all());

        logger('🔐 verifyOtp: Incoming request', $request->all());

        $request->validate([
            'phone' => 'required|string',
            'otp'   => 'required|string'
        ]);

        $otpRecord = TempOtp::where('phone', $request->phone)->first();
        logger('📦 OTP record found:', ['otpRecord' => $otpRecord]);

        if (!$otpRecord) {
            logger('❌ OTP not found');
            return response()->json(['message' => 'OTP not found'], 404);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            logger('⏰ OTP expired');
            return response()->json(['message' => 'OTP expired'], 400);
        }

        if ($otpRecord->otp !== $request->otp) {
            logger('❌ OTP mismatch', [
                'expected' => $otpRecord->otp,
                'provided' => $request->otp,
            ]);
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        // ✅ OTP is valid — create user if not already created
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            logger('👤 No existing user, registering...');

            $user = $this->authService->registerUser([
                'phone' => $request->phone,
                'email' => null,
                'name'  => null,
            ]);

            logger('✅ User created', ['user_id' => $user->id]);

            $user->assignRole('user');
            $user->markEmailAsVerified();
            UserPreference::create(['user_id' => $user->id]);
        } else {
            logger('👤 Existing user found', ['user_id' => $user->id]);
        }

        // Delete OTP after use

        logger('🗑️ OTP deleted');

        // ✅ Log the user in
//        Auth::login($user);
        logger('🔓 Auth::login() called', ['logged_in_user_id' => Auth::id()]);

        // ✅ Create token
        $token = $user->createToken("User.{$user->id}.AuthToken")->plainTextToken;
        logger('🔑 Token created', ['token' => $token]);

        $otpRecord->delete();
        return response()->json([
            'success' => true,
            'message' => 'Phone verified & logged in successfully',
//            'token'   => $token,
//            'redirect_url' => 'http://localhost:5173/seller/edit',
            'user' => new UserResource($user),
        ], 200);
    }

}
