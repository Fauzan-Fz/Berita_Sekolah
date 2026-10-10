<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * OWASP A05 / A02 Security Headers Enforcement Middleware.
 * Adds strict defense-in-depth HTTP security headers to all web and API responses.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if (! $response->headers->has('Content-Security-Policy')) {
            $csp = "default-src 'self'; ".
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ".
                   "style-src 'self' 'unsafe-inline' fonts.googleapis.com; ".
                   "font-src 'self' fonts.gstatic.com data:; ".
                   "img-src 'self' data: https: blob:; ".
                   "connect-src 'self'; ".
                   "frame-ancestors 'self'; ".
                   "base-uri 'self'; ".
                   "form-action 'self'; ".
                   "object-src 'none';";
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
