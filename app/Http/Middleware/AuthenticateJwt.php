<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(protected JwtService $jwtService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Missing or malformed Bearer authorization token',
            ], 401);
        }

        $token = trim(substr($header, 7));
        if (empty($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Bearer token is empty',
            ], 401);
        }

        $claims = $this->jwtService->verifyToken($token);
        if (! $claims) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid, expired, or tampered JWT token',
            ], 401);
        }

        $user = User::find($claims['sub']);
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: User associated with this token does not exist',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: Account has been deactivated by administrator',
            ], 403);
        }

        // Bind authenticated user to request and Auth facade
        $request->setUserResolver(fn () => $user);
        Auth::setUser($user);

        return $next($request);
    }
}
