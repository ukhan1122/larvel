<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {

        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $url]);
    }

    /**
     * 2. Exchange code → Google tokens, fetch profile, upsert user, issue Sanctum token
     */
    public function handleGoogleCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        // 2a. Exchange the code for an access token
        $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri'  => config('services.google.redirect'),
            'code'          => $request->code,
        ]);

        if (! $tokenRes->successful()) {
            return response()->json(['error' => 'Invalid authorization code'], 422);
        }

        $accessToken = $tokenRes->json('access_token');

        // 2b. Fetch Google user profile
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->userFromToken($accessToken);

        // 2c. Upsert into users table
        $user = User::updateOrCreate(
            ['email'     => $googleUser->getEmail()],
            [
                'google_id'=> $googleUser->getId(),
            ]
        );

        // 2d. Issue Sanctum token
        $token = $user->createToken('spa')->plainTextToken;

        $user->assignRole('user');

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }
}
