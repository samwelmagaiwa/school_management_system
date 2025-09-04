<?php

namespace App\Http\Controllers;

use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\ActivityLogger;
use App\Modules\Dashboard\Services\DashboardService;

class AuthController extends Controller
{
    /**
     * Handle user login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            ActivityLogger::logAuth('Login Failed', [
                'email' => $request->email,
                'reason' => 'Invalid credentials'
            ], 'warning');
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->status) {
            ActivityLogger::logAuth('Login Blocked', [
                'email' => $request->email,
                'reason' => 'Account deactivated'
            ], 'warning');
            
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact administrator.'
            ], 403);
        }

        // Delete existing tokens
        $user->tokens()->delete();

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        ActivityLogger::logAuth('Login Successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'school_id' => $user->school_id
        ]);

        // Get dashboard service for permissions and menu items
        $dashboardService = app(DashboardService::class);
        $permissions = $dashboardService->getUserPermissions($user->role);
        $menuItems = $dashboardService->getMenuItemsByRole($user->role);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'school_id' => $user->school_id,
                    'school' => $user->school ? [
                        'id' => $user->school->id,
                        'name' => $user->school->name,
                        'code' => $user->school->code,
                    ] : null,
                ],
                'token' => $token,
                'permissions' => $permissions,
                'menu_items' => $menuItems,
            ]
        ]);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        ActivityLogger::logAuth('Logout', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated user information
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('school');
        
        // Get dashboard service for permissions and menu items
        $dashboardService = app(DashboardService::class);
        $permissions = $dashboardService->getUserPermissions($user->role);
        $menuItems = $dashboardService->getMenuItemsByRole($user->role);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'school_id' => $user->school_id,
                    'school' => $user->school ? [
                        'id' => $user->school->id,
                        'name' => $user->school->name,
                        'code' => $user->school->code,
                    ] : null,
                    'profile_picture' => $user->profile_picture,
                    'status' => $user->status,
                ],
                'permissions' => $permissions,
                'menu_items' => $menuItems,
            ]
        ]);
    }

    /**
     * Refresh user token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Delete current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        ActivityLogger::logAuth('Token Refreshed', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
            ]
        ]);
    }
}