<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Low-risk default security headers on every Laravel web response.
     *
     * Inline scripts have been removed from the shared layouts. The template
     * system still uses inline style attributes so style-src allows inline
     * styles. Scripts are limited to first-party compiled assets.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Origin-Agent-Cluster', '?1');
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "script-src 'self'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob:",
                "media-src 'self' blob:",
                "font-src 'self' data:",
                "frame-src https://www.youtube-nocookie.com",
                "connect-src 'self'",
                "form-action 'self'",
            ]));
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=15552000');
        }

        if ($request->is('dashboard*', 'vendor*', 'account*', 'login', 'register', 'forgot-password', 'reset-password*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
