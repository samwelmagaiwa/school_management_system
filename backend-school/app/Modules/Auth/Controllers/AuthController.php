<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\User\Models\User;
use App\Services\ActivityLogger;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            if (!Auth::attempt($credentials)) {
                ActivityLogger::log('Login Failed - Invalid Credentials', 'Authentication', [
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ], 'warning');
                
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;

            // Load user relationships for complete data
            $user->load(['school', 'permissions', 'roles']);

            ActivityLogger::log('User Login Successful', 'Authentication', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'school_id' => $user->school_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'permissions' => app(DashboardService::class)->getUserPermissions($user->role),
                    'menu_items' => app(DashboardService::class)->getMenuItemsByRole($user->role)
                ]
            ]);
            
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            ActivityLogger::log('Login Error', 'Authentication', [
                'email' => $credentials['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'ip_address' => $request->ip()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed due to server error'
            ], 500);
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            ActivityLogger::log('User Logout', 'Authentication', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip()
            ]);
            
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);
            
        } catch (\Exception $e) {
            ActivityLogger::log('Logout Error', 'Authentication', [
                'error' => $e->getMessage(),
                'ip_address' => $request->ip()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['school', 'permissions', 'roles']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'permissions' => app(DashboardService::class)->getUserPermissions($user->role),
                    'menu_items' => app(DashboardService::class)->getMenuItemsByRole($user->role)
                ]
            ]);
            
        } catch (\Exception $e) {
            ActivityLogger::log('User Profile Fetch Error', 'Authentication', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user profile'
            ], 500);
        }
    }

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:SuperAdmin,Admin,Teacher,Student,Parent',
                'school_id' => 'required|exists:schools,id',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'school_id' => $request->school_id,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;
            $user->load(['school']);

            ActivityLogger::log('User Registration', 'Authentication', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'school_id' => $user->school_id,
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'permissions' => app(DashboardService::class)->getUserPermissions($user->role),
                    'menu_items' => app(DashboardService::class)->getMenuItemsByRole($user->role)
                ]
            ], 201);
            
        } catch (\Exception $e) {
            ActivityLogger::log('User Registration Failed', 'Authentication', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage(),
                'ip_address' => $request->ip()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }


    /**
     * Refresh user token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Delete current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('auth-token')->plainTextToken;
            
            ActivityLogger::log('Token Refreshed', 'Authentication', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token
                ]
            ]);
            
        } catch (\Exception $e) {
            ActivityLogger::log('Token Refresh Failed', 'Authentication', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 500);
        }
    }
}