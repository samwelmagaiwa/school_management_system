<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'code' => 'UNAUTHENTICATED'
            ], 401);
        }
        
        // Check if user has SuperAdmin role
        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'SuperAdmin access required. This action requires SuperAdmin privileges.',
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'required_role' => 'SuperAdmin',
                'current_role' => $user->role
            ], 403);
        }
        
        // Check if user account is active
        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive. Please contact system administrator.',
                'code' => 'ACCOUNT_INACTIVE'
            ], 403);
        }
        
        return $next($request);
    }
}
