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

        // Add phone to the input array for User creation
        $input['phone'] = $phone;

        // Optional: Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $relativePath = $file->storeAs('profile_pictures', $filename, 'public');
            $input['profile_picture'] = asset(Storage::url($relativePath));
        }

        // Ensure OTP was verified first
        $otpRow = TempOtp::where('phone', $phone)->first();
        if (!$otpRow) {
            return response()->json(['message' => 'Phone not verified or OTP expired'], 400);
        }

        // Hash the password before saving
        $input['password'] = bcrypt($input['password']);

        // Create the new User
        $user = User::create($input);
        $user->markEmailAsVerified();

        // Remove temp OTP row after registration
        $otpRow->delete();

        // Generate auth token on user
        $token = $user->createToken("User.{$user->id}.AuthToken")->plainTextToken;

        // You may want to add the token to the response
        $resource = new UserResource($user);

        return $this->createdResponse([
            'user'  => $resource,
            'token' => $token,
        ], __('responses.auth.success.register'));
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
        // Log the incoming request data
        \Log::info('Incoming phone verification request:', ['phone' => $request->phone]);

        $request->validate([
            'phone' => 'required|string'
        ]);

        $existingUser = User::where('phone', $request->phone)->first();

        // Log if user already exists
        if ($existingUser) {
            \Log::warning('Attempt to verify phone that already exists.', ['phone' => $request->phone]);
            return response()->json([
                'message' => 'Account already exists, please login.'
            ], 409); // 409 Conflict
        }

        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addSeconds(60);

        // Log OTP and expiration
        \Log::info('Generated OTP and expiry.', ['otp' => $otp, 'expires_at' => $expiresAt]);

        // Save or update OTP
        $otpRow = TempOtp::updateOrCreate(
            ['phone' => $request->phone],
            ['otp' => $otp, 'expires_at' => $expiresAt]
        );
        \Log::info('TempOtp DB updateOrCreate result:', $otpRow->toArray());

        $messageData = [
            'pin'  => $otp
        ];

        $payload = [
            'api_key'     => config('services.sendpk.api_key'),
            'sender'      => 'Closyyyy',
            'mobile'      => $request->phone,
            'template_id' => 10118,
            'message'     => json_encode($messageData),
            'format'      => 'json',
        ];

        // Log the payload that will be sent to SendPK
        \Log::info('SendPK SMS payload:', $payload);

        // Make the request and log both request and response
        $response = Http::asForm()->post('https://sendpk.com/api/sms.php', $payload);

        \Log::debug('SendPK API raw response:', [
            'status' => $response->status(),
            'body'   => $response->body(),
            'json'   => $response->json(),
        ]);

        if ($response->successful()) {
            Log::info('OTP SMS sent successfully via SendPK.', ['phone' => $request->phone, 'otp' => $otp]);
            return response()->json(['message' => 'OTP (test) sent successfully']);
        } else {
            \Log::error('Failed to send OTP SMS via SendPK.', [
                'phone' => $request->phone,
                'status' => $response->status(),
                'body'   => $response->body(),
                'json'   => $response->json(),
            ]);
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
//        $user = User::where('phone', $request->phone)->first();
//        if (!$user) {
//            logger('👤 No existing user, registering...');
//
//            $user = $this->authService->registerUser([
//                'phone' => $request->phone,
//                'email' => null,
//                'name'  => null,
//            ]);
//
//            logger('✅ User created', ['user_id' => $user->id]);
//
//            $user->assignRole('user');
//            $user->markEmailAsVerified();
//            UserPreference::create(['user_id' => $user->id]);
//        } else {
//            logger('👤 Existing user found', ['user_id' => $user->id]);
//        }

        // Delete OTP after use

        logger('🗑️ OTP deleted');

        // ✅ Log the user in
//        Auth::login($user);
        logger('🔓 Auth::login() called', ['logged_in_user_id' => Auth::id()]);

        // ✅ Create token
//        $token = $user->createToken("User.{$user->id}.AuthToken")->plainTextToken;
//        logger('🔑 Token created', ['token' => $token]);

//        $otpRecord->delete();
        return response()->json([
            'success' => true,
            'message' => 'Phone verified & logged in successfully',
//            'token'   => $token,
//            'redirect_url' => 'http://localhost:5173/seller/edit',
//            'user' => new UserResource($user),
        ], 200);
    }

}
