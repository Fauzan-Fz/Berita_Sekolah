<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(protected JwtService $jwtService) {}

    /**
     * Authenticate credentials with Anti-Brute-Force Rate Limiting (OWASP A04).
     */
    public function login(Request $request): JsonResponse
    {
        $throttleKey = 'api_login|'.$request->ip().'|'.Str::lower($request->input('email', ''));

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'status' => 'error',
                'message' => "Too many authentication attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password credentials',
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        $accessTtl = (int) (config('jwt.access_ttl') ?: 15);
        $refreshTtl = (int) (config('jwt.refresh_ttl') ?: 10080);

        $accessToken = $this->jwtService->generateToken($user, [], $accessTtl);
        $refreshToken = $this->jwtService->createRefreshToken($user, $request->ip(), $request->userAgent());

        return response()->json([
            'status' => 'success',
            'message' => 'Authentication successful',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $accessToken,
                'expires_in' => $accessTtl * 60,
                'refresh_token' => $refreshToken,
                'refresh_expires_in' => $refreshTtl * 60,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'bio' => $user->bio,
                    'profile_photo' => $user->profile_photo,
                ],
            ],
        ]);
    }

    /**
     * Rotate Refresh Token and receive fresh short-lived Access Token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $throttleKey = 'api_refresh|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'status' => 'error',
                'message' => "Too many refresh attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $rotationResult = $this->jwtService->rotateRefreshToken(
            $data['refresh_token'],
            $request->ip(),
            $request->userAgent()
        );

        if (! $rotationResult) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid, expired, or reused refresh token',
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        $user = $rotationResult['user'];

        return response()->json([
            'status' => 'success',
            'message' => 'Token rotated successfully',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $rotationResult['access_token'],
                'expires_in' => $rotationResult['expires_in'],
                'refresh_token' => $rotationResult['refresh_token'],
                'refresh_expires_in' => $rotationResult['refresh_expires_in'],
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Retrieve authenticated profile via JWT bearer.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'bio' => $user->bio,
                'profile_photo' => $user->profile_photo,
                'posts_count' => $user->posts()->count(),
                'created_at' => $user->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Terminate API session and revoke refresh token.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->filled('refresh_token')) {
            $this->jwtService->revokeRefreshToken($request->string('refresh_token'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Session terminated and token revoked successfully',
        ]);
    }
}
