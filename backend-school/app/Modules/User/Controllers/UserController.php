<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Resources\UserCollection;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Services\ActivityLogger;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware('auth:sanctum');
        $this->userService = $userService;
    }

    /**
     * Display a listing of users with filters and search
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        ActivityLogger::logUser('View Users List', [
            'filters' => $request->only(['role', 'school_id', 'search', 'status', 'sort_by', 'sort_order'])
        ]);
        
        $query = User::with(['school']);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        // Role filter
        if ($request->filled('role')) {
            $query->byRole($request->role);
        }

        // School filter (only for SuperAdmin)
        if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'first_name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new UserCollection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'filters' => [
                'role' => $request->role,
                'school_id' => $request->school_id,
                'search' => $request->search,
                'status' => $request->status,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $userData = $request->validated();
        
        // Set default password to last_name if not provided
        if (!isset($userData['password'])) {
            $userData['password'] = $userData['last_name'];
        }

        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $userData['school_id'] = auth()->user()->school_id;
        }

        $user = $this->userService->createUser($userData);

        ActivityLogger::logUser('User Created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'school_id' => $user->school_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $user->school_id) &&
            $currentUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        ActivityLogger::logUser('View User Details', [
            'viewed_user_id' => $user->id,
            'viewed_user_email' => $user->email
        ]);
        
        $user->load(['school', 'student']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $user->school_id && !$user->isSuperAdmin()) &&
            $currentUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $userData = $request->validated();
        
        $originalData = $user->toArray();
        $user = $this->userService->updateUser($user, $userData);

        ActivityLogger::logUser('User Updated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'changes' => array_diff_assoc($userData, $originalData)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user->fresh(['school', 'student']))
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete yourself'
            ], 403);
        }
        
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $user->school_id && !$user->isSuperAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        // Prevent deletion of SuperAdmin users by non-SuperAdmin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            ActivityLogger::logUser('User Deletion Failed', [
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'reason' => 'Cannot delete SuperAdmin user'
            ], 'warning');
            
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete SuperAdmin user'
            ], 403);
        }

        ActivityLogger::logUser('User Deleted', [
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
     * Get available roles for user creation
     */
    public function getAvailableRoles(): JsonResponse
    {
        $roles = User::ROLES;

        // Non-SuperAdmin users cannot create SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $roles = array_filter($roles, fn($role) => $role !== 'SuperAdmin');
        }

        return response()->json([
            'success' => true,
            'data' => array_values($roles)
        ]);
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): JsonResponse
    {
        $query = User::byRole($role);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        $users = $query->active()->get();

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users)
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->userService->getUserStatistics(
            auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
        );

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $user->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $this->userService->resetPasswordToDefault($user);

        ActivityLogger::logUser('Password Reset', [
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
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $user->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $newStatus = !$user->status;
        $user->update(['status' => $newStatus]);

        ActivityLogger::logUser('User Status Toggled', [
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'new_status' => $newStatus ? 'active' : 'inactive'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'status' => $newStatus,
                'user' => new UserResource($user->fresh())
            ]
        ]);
    }
    
    /**
     * Bulk import users from CSV/Excel
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
            'school_id' => 'required_if:role,SuperAdmin|exists:schools,id'
        ]);
        
        $schoolId = auth()->user()->isSuperAdmin() ? $request->school_id : auth()->user()->school_id;
        
        try {
            $result = $this->userService->bulkImportUsers($request->file('file'), $schoolId);
            
            ActivityLogger::logUser('Users Bulk Import', [
                'file_name' => $request->file('file')->getClientOriginalName(),
                'imported_count' => $result['imported'],
                'failed_count' => $result['failed'],
                'school_id' => $schoolId
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
     * Export users to CSV/Excel
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->only(['role', 'school_id', 'status', 'search']);
        
        if (!auth()->user()->isSuperAdmin()) {
            $filters['school_id'] = auth()->user()->school_id;
        }
        
        try {
            return $this->userService->exportUsers($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting users: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Upload profile picture
     */
    public function uploadProfilePicture(Request $request, User $user): JsonResponse
    {
        // Check authorization - users can only upload their own profile pictures or admins can upload for users in their school
        $currentUser = auth()->user();
        if ($currentUser->id !== $user->id && !$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $user->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        
        try {
            $profilePictureUrl = $this->userService->uploadProfilePicture($user, $request->file('profile_picture'));
            
            ActivityLogger::logUser('Profile Picture Uploaded', [
                'user_id' => $user->id,
                'email' => $user->email,
                'profile_picture_url' => $profilePictureUrl
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile picture uploaded successfully',
                'data' => [
                    'profile_picture_url' => $profilePictureUrl,
                    'user' => new UserResource($user->fresh())
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading profile picture: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword(Request $request, User $user): JsonResponse
    {
        // Check authorization - users can only change their own password
        if (auth()->user()->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only change your own password'
            ], 403);
        }
        
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed'
        ]);
        
        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }
        
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        ActivityLogger::logUser('Password Changed', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
