<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ActivityLogger;

class LogActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        // Log the request after processing
        $this->logRequest($request, $response, $startTime);
        
        return $response;
    }

    /**
     * Log the request details
     */
    private function logRequest(Request $request, Response $response, float $startTime): void
    {
        // Skip logging for certain routes
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $logData = [
            'execution_time_ms' => $executionTime,
            'memory_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'response_size' => strlen($response->getContent()),
        ];

        // Log based on response status
        if ($response->getStatusCode() >= 500) {
            ActivityLogger::logSecurity(
                'Server Error Response',
                array_merge($logData, ['status_code' => $response->getStatusCode()]),
                'error'
            );
        } elseif ($response->getStatusCode() >= 400) {
            // Only log 4xx errors for authenticated users or specific endpoints
            if ($request->user() || $this->shouldLogUnauthenticatedError($request)) {
                ActivityLogger::logSecurity(
                    'Client Error Response',
                    array_merge($logData, ['status_code' => $response->getStatusCode()]),
                    'warning'
                );
            }
        } else {
            ActivityLogger::logRequest($request, $response);
        }

        // Log slow requests
        if ($executionTime > 1000) { // More than 1 second
            ActivityLogger::logSecurity(
                'Slow Request Detected',
                array_merge($logData, [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]),
                'warning'
            );
        }
    }

    /**
     * Determine if logging should be skipped for this request
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $skipRoutes = [
            'health',
            'up',
            '_debugbar',
            'telescope',
        ];

        $path = $request->path();
        
        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($path, $skipRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if unauthenticated 4xx errors should be logged
     */
    private function shouldLogUnauthenticatedError(Request $request): bool
    {
        // Log 404s for protected routes that shouldn't be accessed by guests
        $protectedPaths = [
            'api/admin',
            'api/dashboard',
            'api/schools',
            'api/users',
            'api/students',
        ];

        $path = $request->path();
        
        foreach ($protectedPaths as $protectedPath) {
            if (str_starts_with($path, $protectedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}