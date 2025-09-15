<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdvancedRateLimitMiddleware
{
    /**
     * The rate limiter instance.
     */
    public function __construct(protected RateLimiter $limiter)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Check if rate limit exceeded
        if ($this->limiter->tooManyAttempts($key, (int) $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
                'max_attempts' => (int) $maxAttempts,
                'decay_minutes' => (int) $decayMinutes
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $this->availableAt($retryAfter),
                'Retry-After' => $retryAfter,
            ]);
        }

        // Hit the rate limiter
        $this->limiter->hit($key, (int) $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers to response
        $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->limiter->retriesLeft($key, (int) $maxAttempts),
            'X-RateLimit-Reset' => $this->availableAt((int) $decayMinutes * 60),
        ]);

        return $response;
    }

    /**
     * Resolve the rate limiting signature for the request.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        
        // Different rate limiting keys based on authentication status
        if ($user) {
            // Authenticated users: limit by user ID + route
            return 'api_rate_limit:user_' . $user->id . ':' . $request->route()->getName();
        }
        
        // Unauthenticated users: limit by IP + route (more restrictive)
        return 'api_rate_limit:ip_' . $request->ip() . ':' . $request->route()->getName();
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     */
    protected function availableAt(int $retryAfter): int
    {
        return time() + $retryAfter;
    }
}
