<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Modules\User\Models\User;

class DebugController extends Controller
{
    /**
     * Check authentication status
     */
    public function authStatus(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'debug_info' => [
                'has_bearer_token' => !empty($token),
                'token_preview' => $token ? substr($token, 0, 10) . '...' : null,
                'is_authenticated' => Auth::check(),
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'user_role' => $user?->role,
                'guards' => config('auth.guards'),
                'default_guard' => config('auth.defaults.guard'),
                'sanctum_guard' => config('sanctum.guard'),
                'request_headers' => [
                    'authorization' => $request->header('Authorization'),
                    'accept' => $request->header('Accept'),
                    'content_type' => $request->header('Content-Type'),
                ],
                'middleware' => $request->route()?->middleware() ?? [],
            ]
        ]);
    }

    /**
     * Check CORS configuration
     */
    public function corsInfo(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'cors_info' => [
                'origin' => $request->header('Origin'),
                'referer' => $request->header('Referer'),
                'user_agent' => $request->header('User-Agent'),
                'allowed_origins' => config('cors.allowed_origins'),
                'allowed_methods' => config('cors.allowed_methods'),
                'allowed_headers' => config('cors.allowed_headers'),
                'supports_credentials' => config('cors.supports_credentials'),
            ]
        ]);
    }

    /**
     * Test database connection and user count
     */
    public function databaseStatus(): JsonResponse
    {
        try {
            $userCount = User::count();
            $firstUser = User::first();
            
            return response()->json([
                'success' => true,
                'database_info' => [
                    'connection' => 'working',
                    'user_count' => $userCount,
                    'first_user' => $firstUser ? [
                        'id' => $firstUser->id,
                        'email' => $firstUser->email,
                        'role' => $firstUser->role,
                        'status' => $firstUser->status,
                    ] : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'database_info' => [
                    'connection' => 'failed',
                    'error' => $e->getMessage(),
                ]
            ], 500);
        }
    }

    /**
     * Create a test user for authentication testing
     */
    public function createTestUser(): JsonResponse
    {
        try {
            // Check if test user already exists
            $existingUser = User::where('email', 'test@example.com')->first();
            
            if ($existingUser) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test user already exists',
                    'user' => [
                        'id' => $existingUser->id,
                        'email' => $existingUser->email,
                        'role' => $existingUser->role,
                    ]
                ]);
            }

            // Create test user
            $user = User::create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'role' => 'Admin',
                'status' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test user created successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'credentials' => [
                    'email' => 'test@example.com',
                    'password' => 'password'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}