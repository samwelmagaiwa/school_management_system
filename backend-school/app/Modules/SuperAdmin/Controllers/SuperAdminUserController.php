<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use App\Modules\SuperAdmin\Requests\SuperAdminUserStoreRequest;
use App\Modules\SuperAdmin\Requests\SuperAdminUserUpdateRequest;
use App\Modules\SuperAdmin\Requests\SuperAdminUserBulkRequest;
use App\Modules\SuperAdmin\Resources\SuperAdminUserResource;
use App\Modules\SuperAdmin\Resources\SuperAdminUserCollection;
use App\Modules\SuperAdmin\Services\SuperAdminUserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class SuperAdminUserController extends Controller
{
    protected SuperAdminUserService $userService;

    public function __construct(SuperAdminUserService $userService)
    {
        $this->middleware('auth:sanctum');
        $this->userService = $userService;
        
        // Ensure only SuperAdmin can access these endpoints
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. SuperAdmin privileges required.'
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of all users across all schools
     */
    public function index(Request $request): JsonResponse
    {
        ActivityLogger::logUser('SuperAdmin: View All Users', [
            'filters' => $request->only(['role', 'school_id', 'search', 'status', 'sort_by', 'sort_order', 'date_range'])
        ]);

        $query = User::with(['school']);

        // Apply filters
        if ($request->filled('role')) {
            $query->byRole($request->role);
        }

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // Date range filter
        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            if (isset($dateRange['start']) && isset($dateRange['end'])) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }
        }

        // Advanced filters
        if ($request->filled('last_login_range')) {
            $loginRange = $request->last_login_range;
            if (isset($loginRange['start']) && isset($loginRange['end'])) {
                $query->whereBetween('last_login_at', [$loginRange['start'], $loginRange['end']]);
            }
        }

        if ($request->filled('email_verified')) {
            if ($request->boolean('email_verified')) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Handle special sorting cases
        if ($sortBy === 'school_name') {
            $query->leftJoin('schools', 'users.school_id', '=', 'schools.id')
                  ->orderBy('schools.name', $sortOrder)
                  ->select('users.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new SuperAdminUserCollection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'filters' => $request->only(['role', 'school_id', 'search', 'status', 'sort_by', 'sort_order'])
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(SuperAdminUserStoreRequest $request): JsonResponse
    {
        $userData = $request->validated();
        
        $user = $this->userService->createUser($userData);

        ActivityLogger::logUser('SuperAdmin: User Created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'school_id' => $user->school_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => new SuperAdminUserResource($user)
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        ActivityLogger::logUser('SuperAdmin: View User Details', [
            'viewed_user_id' => $user->id,
            'viewed_user_email' => $user->email
        ]);
        
        $user->load(['school', 'student', 'teacher', 'employee']);

        return response()->json([
            'success' => true,
            'data' => new SuperAdminUserResource($user)
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(SuperAdminUserUpdateRequest $request, User $user): JsonResponse
    {
        $userData = $request->validated();
        
        $originalData = $user->toArray();
        $user = $this->userService->updateUser($user, $userData);

        ActivityLogger::logUser('SuperAdmin: User Updated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'changes' => array_diff_assoc($userData, $originalData)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new SuperAdminUserResource($user->fresh(['school', 'student', 'teacher', 'employee']))
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deletion of other SuperAdmin users
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete SuperAdmin users'
            ], 403);
        }

        ActivityLogger::logUser('SuperAdmin: User Deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);
        
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get comprehensive user statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->userService->getComprehensiveStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get user analytics data
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $period = $request->get('period', '30_days');
        $analytics = $this->userService->getUserAnalytics($period);

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user): JsonResponse
    {
        $this->userService->resetPasswordToDefault($user);

        ActivityLogger::logUser('SuperAdmin: Password Reset', [
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'default_password' => $user->last_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
            'data' => [
                'default_password' => $user->last_name
            ]
        ]);
    }

    /**
     * Toggle user status (active/inactive)
     */
    public function toggleStatus(User $user): JsonResponse
    {
        // Prevent deactivating SuperAdmin users
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate SuperAdmin users'
            ], 403);
        }

        $newStatus = !$user->status;
        $user->update(['status' => $newStatus]);

        ActivityLogger::logUser('SuperAdmin: User Status Toggled', [
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'new_status' => $newStatus ? 'active' : 'inactive'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'status' => $newStatus,
                'user' => new SuperAdminUserResource($user->fresh())
            ]
        ]);
    }

    /**
     * Bulk operations on users
     */
    public function bulkAction(SuperAdminUserBulkRequest $request): JsonResponse
    {
        $action = $request->action;
        $userIds = $request->user_ids;
        $data = $request->data ?? [];

        try {
            DB::beginTransaction();

            $result = $this->userService->performBulkAction($action, $userIds, $data);

            DB::commit();

            ActivityLogger::logUser('SuperAdmin: Bulk Action Performed', [
                'action' => $action,
                'user_count' => count($userIds),
                'user_ids' => $userIds,
                'data' => $data
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bulk {$action} completed successfully",
                'data' => $result
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => "Bulk action failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->only(['role', 'school_id', 'status', 'search', 'date_range']);
        
        try {
            ActivityLogger::logUser('SuperAdmin: Users Export', [
                'format' => $format,
                'filters' => $filters
            ]);

            return $this->userService->exportUsers($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import users from file
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
            'school_id' => 'required|exists:schools,id'
        ]);
        
        try {
            $result = $this->userService->bulkImportUsers(
                $request->file('file'), 
                $request->school_id
            );
            
            ActivityLogger::logUser('SuperAdmin: Users Bulk Import', [
                'file_name' => $request->file('file')->getClientOriginalName(),
                'imported_count' => $result['imported'],
                'failed_count' => $result['failed'],
                'school_id' => $request->school_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Users imported successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available schools for user assignment
     */
    public function getSchools(): JsonResponse
    {
        $schools = School::active()->orderBy('name')->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => $schools
        ]);
    }

    /**
     * Get available roles for user creation
     */
    public function getRoles(): JsonResponse
    {
        $roles = User::ROLES;

        return response()->json([
            'success' => true,
            'data' => array_values($roles)
        ]);
    }

    /**
     * Get user activity logs
     */
    public function getActivityLogs(User $user, Request $request): JsonResponse
    {
        $logs = $this->userService->getUserActivityLogs($user, $request->all());

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Send password reset email to user
     */
    public function sendPasswordResetEmail(User $user): JsonResponse
    {
        try {
            $this->userService->sendPasswordResetEmail($user);

            ActivityLogger::logUser('SuperAdmin: Password Reset Email Sent', [
                'target_user_id' => $user->id,
                'target_user_email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Impersonate a user (for support purposes)
     */
    public function impersonate(User $user): JsonResponse
    {
        // Prevent impersonating other SuperAdmin users
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot impersonate SuperAdmin users'
            ], 403);
        }

        try {
            $token = $this->userService->createImpersonationToken($user);

            ActivityLogger::logUser('SuperAdmin: User Impersonation Started', [
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'impersonation_token' => substr($token, 0, 10) . '...'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Impersonation token created successfully',
                'data' => [
                    'token' => $token,
                    'user' => new SuperAdminUserResource($user)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create impersonation token: ' . $e->getMessage()
            ], 500);
        }
    }
}