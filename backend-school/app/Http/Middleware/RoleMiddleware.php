<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     * @param  string|array  $roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }
        
        // Check if user has required role
        if (!empty($roles) && !in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions. Required roles: ' . implode(', ', $roles)
            ], 403);
        }
        
        return $next($request);
    }
}
