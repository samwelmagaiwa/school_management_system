<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 
            'geolocation=(), microphone=(), camera=(), magnetometer=(), gyroscope=()');
        
        // Only add HSTS in production with HTTPS
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 
                'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy for API responses
        if ($response->headers->get('Content-Type') === 'application/json' || 
            str_contains($response->headers->get('Content-Type', ''), 'application/json')) {
            $response->headers->set('Content-Security-Policy', 
                "default-src 'none'; script-src 'none'; object-src 'none'; base-uri 'none';");
        }

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
