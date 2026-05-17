<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\OauthAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => ['code' => 'TOO_MANY_ATTEMPTS', 'message' => 'Too many login attempts.', 'statusCode' => 429]], 429);
        }

        $user = User::where('email', $request->email)->where('is_active', true)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            RateLimiter::hit($key, 900);
            return response()->json(['error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Invalid credentials.', 'statusCode' => 401]], 401);
        }

        RateLimiter::clear($key);

        $preToken = encrypt(['user_id' => $user->id, 'exp' => now()->addMinutes(5)->timestamp]);

        return response()->json(['requires_pin' => true, 'session_token' => $preToken]);
    }

    public function oauth(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|in:google,apple',
            'id_token' => 'required|string',
        ]);

        try {
            $socialUser = Socialite::driver($request->provider)->userFromToken($request->id_token);
        } catch (\Throwable) {
            return response()->json(['error' => ['code' => 'INVALID_TOKEN', 'message' => 'OAuth token invalid.', 'statusCode' => 401]], 401);
        }

        $oauthAccount = OauthAccount::firstOrCreate(
            ['provider' => $request->provider, 'provider_uid' => $socialUser->getId()],
        );

        if (!$oauthAccount->user_id) {
            // Cannot create users without an org — return error for now
            return response()->json(['error' => ['code' => 'NO_ORG', 'message' => 'Account not linked to an organization.', 'statusCode' => 422]], 422);
        }

        $user      = $oauthAccount->user;
        $preToken  = encrypt(['user_id' => $user->id, 'exp' => now()->addMinutes(5)->timestamp]);

        return response()->json(['requires_pin' => (bool) $user->pin_hash, 'session_token' => $preToken]);
    }

    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string',
            'pin'           => 'required|string|digits:4',
        ]);

        $pinKey = 'pin_attempt:' . sha1($request->session_token);
        if (RateLimiter::tooManyAttempts($pinKey, 5)) {
            return response()->json(['error' => ['code' => 'TOO_MANY_ATTEMPTS', 'message' => 'Too many PIN attempts.', 'statusCode' => 429]], 429);
        }

        try {
            $payload = decrypt($request->session_token);
        } catch (\Throwable) {
            return response()->json(['error' => ['code' => 'INVALID_TOKEN', 'message' => 'Invalid session token.', 'statusCode' => 422]], 422);
        }

        if ($payload['exp'] < now()->timestamp) {
            return response()->json(['error' => ['code' => 'SESSION_EXPIRED', 'message' => 'Session expired.', 'statusCode' => 422]], 422);
        }

        // Prevent replay
        $usedKey = 'used_pre_token:' . sha1($request->session_token);
        if (Cache::has($usedKey)) {
            return response()->json(['error' => ['code' => 'TOKEN_USED', 'message' => 'Session token already used.', 'statusCode' => 422]], 422);
        }

        $user = User::findOrFail($payload['user_id']);

        if (!Hash::check($request->pin, $user->pin_hash)) {
            RateLimiter::hit($pinKey, 300);
            return response()->json(['error' => ['code' => 'INVALID_PIN', 'message' => 'Invalid PIN.', 'statusCode' => 422]], 422);
        }

        Cache::put($usedKey, true, 300);
        RateLimiter::clear($pinKey);

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('mobile', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user'         => UserResource::make($user->load('organization')),
        ]);
    }

    public function setPin(Request $request): JsonResponse
    {
        $request->validate(['pin' => 'required|digits:4']);

        $request->user()->update(['pin_hash' => Hash::make($request->pin)]);

        return response()->json(['message' => 'PIN set successfully.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user  = $request->user();
        $rules = ['new_password' => 'required|string|min:8|confirmed'];

        if ($user->password_hash) {
            $rules['current_password'] = 'required|string';
        }

        $request->validate($rules);

        if ($user->password_hash && !Hash::check($request->current_password, $user->password_hash)) {
            return response()->json([
                'error' => ['code' => 'INVALID_PASSWORD', 'message' => 'Current password is incorrect.', 'statusCode' => 422],
            ], 422);
        }

        $user->update(['password_hash' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function logout(Request $request): \Illuminate\Http\Response
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }
}
