<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            if (!$request->user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. SuperAdmin access required.'
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            // Basic dashboard stats without service dependency
            $stats = [
                'total_users' => User::count(),
                'total_schools' => School::count(),
                'active_users' => User::where('status', true)->count(),
                'active_schools' => School::where('status', true)->count()
            ];

            ActivityLogger::log('SuperAdmin Dashboard Viewed', 'Dashboard', [
                'user_id' => $request->user()->id,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all schools with comprehensive data
     */
    public function getSchools(Request $request): JsonResponse
    {
        try {
            $query = School::with(['users' => function($q) {
                $q->select('id', 'school_id', 'first_name', 'last_name', 'email', 'role', 'status');
            }]);

            // Apply filters
            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $search = $request->search;
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('is_active', $request->status);
            }

            $schools = $query->paginate($request->per_page ?? 15);

            // Add computed data
            $schools->getCollection()->transform(function ($school) {
                $school->total_users = $school->users->count();
                $school->total_students = $school->users->where('role', 'Student')->count();
                $school->total_teachers = $school->users->where('role', 'Teacher')->count();
                $school->total_admins = $school->users->where('role', 'Admin')->count();
                return $school;
            });

            return response()->json([
                'success' => true,
                'data' => $schools
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch schools',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new school
     */
    public function createSchool(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:schools,code',
                'email' => 'required|email|unique:schools,email',
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'website' => 'nullable|url',
                'established_year' => 'required|integer|min:1800|max:' . date('Y'),
                'principal_name' => 'required|string|max:255',
                'principal_email' => 'required|email',
                'principal_phone' => 'required|string|max:20',
                'description' => 'nullable|string',
                'board_affiliation' => 'required|string|max:100',
                'school_type' => 'required|in:primary,secondary,higher_secondary,all',
                'registration_number' => 'required|string|max:100',
                'tax_id' => 'nullable|string|max:50',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $school = School::create($validator->validated());

            ActivityLogger::log('School Created', 'School', [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'created_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'School created successfully',
                'data' => $school
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create school',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update a school
     */
    public function updateSchool(Request $request, $id): JsonResponse
    {
        try {
            $school = School::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => ['required', 'string', 'max:50', Rule::unique('schools')->ignore($school->id)],
                'email' => ['required', 'email', Rule::unique('schools')->ignore($school->id)],
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'website' => 'nullable|url',
                'established_year' => 'required|integer|min:1800|max:' . date('Y'),
                'principal_name' => 'required|string|max:255',
                'principal_email' => 'required|email',
                'principal_phone' => 'required|string|max:20',
                'description' => 'nullable|string',
                'board_affiliation' => 'required|string|max:100',
                'school_type' => 'required|in:primary,secondary,higher_secondary,all',
                'registration_number' => 'required|string|max:100',
                'tax_id' => 'nullable|string|max:50',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $school->update($validator->validated());

            ActivityLogger::log('School Updated', 'School', [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'updated_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'School updated successfully',
                'data' => $school
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update school',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a school
     */
    public function deleteSchool(Request $request, $id): JsonResponse
    {
        try {
            $school = School::findOrFail($id);

            // Check if school has users
            if ($school->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete school with existing users. Please transfer or delete users first.'
                ], 409);
            }

            ActivityLogger::log('School Deleted', 'School', [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'deleted_by' => $request->user()->id
            ]);

            $school->delete();

            return response()->json([
                'success' => true,
                'message' => 'School deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all users across all schools
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        try {
            $query = User::with(['school:id,name,code']);

            // Apply filters
            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('role')) {
                $query->byRole($request->role);
            }

            if ($request->has('school_id')) {
                $query->bySchool($request->school_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $users = $query->latest()->paginate($request->per_page ?? 15);

            // Transform user data to include frontend-expected properties
            $users->getCollection()->transform(function ($user) {
                // Handle case where user might be null or undefined
                if (!$user) {
                    return [
                        'id' => null,
                        'first_name' => '',
                        'last_name' => '',
                        'email' => '',
                        'role' => '',
                        'status' => false,
                        'can_reset_password' => false,
                        'can_delete' => false,
                        'can_edit' => false,
                        'can_change_role' => false,
                        'can_change_status' => false,
                        'can_resend_invitation' => false,
                        'can_login_as' => false,
                        'full_name' => '',
                        'role_label' => '',
                        'status_label' => 'Inactive',
                        'status_badge_class' => 'bg-danger',
                        'created_at_formatted' => '',
                        'updated_at_formatted' => '',
                        'last_login_formatted' => 'Never',
                        'school_name' => 'No School Assigned',
                        'school_code' => null,
                        'avatar_url' => null,
                        'avatar_initials' => 'NN'
                    ];
                }
                
                // Add computed properties for frontend
                $userData = $user->toArray();
                
                // Add permission-related properties with error handling
                try {
                    $userData['can_reset_password'] = $this->canResetPassword($user);
                    $userData['can_delete'] = $this->canDeleteUser($user);
                    $userData['can_edit'] = $this->canEditUser($user);
                    $userData['can_change_role'] = $this->canChangeRole($user);
                    $userData['can_change_status'] = $this->canChangeStatus($user);
                    $userData['can_resend_invitation'] = $this->canResendInvitation($user);
                    $userData['can_login_as'] = $this->canLoginAs($user);
                } catch (\Exception $e) {
                    // Fallback permissions if there's an error
                    $userData['can_reset_password'] = false;
                    $userData['can_delete'] = false;
                    $userData['can_edit'] = false;
                    $userData['can_change_role'] = false;
                    $userData['can_change_status'] = false;
                    $userData['can_resend_invitation'] = false;
                    $userData['can_login_as'] = false;
                }
                
                // Add display properties with safe property access
                $userData['full_name'] = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $userData['role_label'] = $this->getRoleLabel($user->role ?? '');
                $userData['status_label'] = ($user->status ?? false) ? 'Active' : 'Inactive';
                $userData['status_badge_class'] = ($user->status ?? false) ? 'bg-success' : 'bg-danger';
                
                // Add formatted dates with safe property access
                $userData['created_at_formatted'] = $user->created_at ? $user->created_at->format('M j, Y g:i A') : 'Not Available';
                $userData['updated_at_formatted'] = $user->updated_at ? $user->updated_at->format('M j, Y g:i A') : 'Not Available';
                $userData['last_login_formatted'] = $user->last_login_at ? $user->last_login_at->format('M j, Y g:i A') : 'Never';
                
                // Add school information with safe property access
                $userData['school_name'] = ($user->school && $user->school->name) ? $user->school->name : 'No School Assigned';
                $userData['school_code'] = ($user->school && $user->school->code) ? $user->school->code : null;
                
                // Add avatar/profile picture URL with safe property access
                $userData['avatar_url'] = ($user->profile_picture ?? false) ? url('storage/' . $user->profile_picture) : null;
                $firstName = $user->first_name ?? 'N';
                $lastName = $user->last_name ?? 'N';
                $userData['avatar_initials'] = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                
                return $userData;
            });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new user
     */
    public function createUser(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'role' => 'required|in:SuperAdmin,Admin,Teacher,Student,Parent',
                'school_id' => 'nullable|exists:schools,id',
                'status' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userData = $validator->validated();
            $userData['password'] = Hash::make($userData['password']);
            $userData['email_verified_at'] = now();

            $user = User::create($userData);

            ActivityLogger::log('User Created by SuperAdmin', 'User', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'created_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load('school')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get system-wide statistics for reports
     */
    public function getSystemReports(Request $request): JsonResponse
    {
        try {
            $reports = [
                'user_distribution' => User::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->get()
                    ->pluck('count', 'role'),
                    
                'school_statistics' => School::selectRaw('
                    COUNT(*) as total_schools,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_schools,
                    AVG(established_year) as avg_established_year
                ')->first(),
                
                'monthly_growth' => [
                    'users' => User::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                        ->whereYear('created_at', date('Y'))
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get(),
                    'schools' => School::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                        ->whereYear('created_at', date('Y'))
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get()
                ],
                
                'activity_summary' => [
                    'total_logins_today' => User::whereDate('last_login_at', today())->count(),
                    'total_active_sessions' => DB::table('sessions')->count(),
                    'new_registrations_this_week' => User::where('created_at', '>=', now()->subWeek())->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate reports',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper method to determine if a user's password can be reset
     */
    private function canResetPassword(User $user): bool
    {
        $currentUser = auth()->user();
        
        if ($currentUser->role === 'SuperAdmin') {
            return true;
        }
        
        return $user->role !== 'SuperAdmin';
    }

    /**
     * Helper method to determine if a user can be deleted
     */
    private function canDeleteUser(User $user): bool
    {
        $currentUser = auth()->user();
        
        // Can't delete yourself
        if ($user->id === $currentUser->id) {
            return false;
        }
        
        // SuperAdmins can delete anyone except other SuperAdmins (unless current user is also SuperAdmin)
        if ($currentUser->role === 'SuperAdmin') {
            return $user->role !== 'SuperAdmin' || $currentUser->role === 'SuperAdmin';
        }
        
        return false;
    }

    /**
     * Helper method to determine if a user can be edited
     */
    private function canEditUser(User $user): bool
    {
        $currentUser = auth()->user();
        
        if ($currentUser->role === 'SuperAdmin') {
            return true;
        }
        
        return false;
    }

    /**
     * Helper method to determine if a user's role can be changed
     */
    private function canChangeRole(User $user): bool
    {
        $currentUser = auth()->user();
        
        if ($currentUser->role === 'SuperAdmin') {
            return $user->id !== $currentUser->id; // Can't change own role
        }
        
        return false;
    }

    /**
     * Helper method to determine if a user's status can be changed
     */
    private function canChangeStatus(User $user): bool
    {
        $currentUser = auth()->user();
        
        // Can't change your own status
        if ($user->id === $currentUser->id) {
            return false;
        }
        
        if ($currentUser->role === 'SuperAdmin') {
            return true;
        }
        
        return false;
    }

    /**
     * Helper method to determine if an invitation can be resent
     */
    private function canResendInvitation(User $user): bool
    {
        $currentUser = auth()->user();
        
        if ($currentUser->role === 'SuperAdmin') {
            return $user->email_verified_at === null;
        }
        
        return false;
    }

    /**
     * Helper method to determine if current user can login as this user
     */
    private function canLoginAs(User $user): bool
    {
        $currentUser = auth()->user();
        
        // Can't login as yourself
        if ($user->id === $currentUser->id) {
            return false;
        }
        
        // Only SuperAdmins can login as other users
        if ($currentUser->role === 'SuperAdmin') {
            return $user->role !== 'SuperAdmin'; // Can't login as other SuperAdmins
        }
        
        return false;
    }

    /**
     * Helper method to get role label
     */
    private function getRoleLabel(string $role): string
    {
        $roleLabels = [
            'SuperAdmin' => 'Super Administrator',
            'Admin' => 'School Administrator',
            'Teacher' => 'Teacher',
            'Student' => 'Student',
            'Parent' => 'Parent',
            'HR' => 'Human Resources',
            'Accountant' => 'Accountant'
        ];
        
        return $roleLabels[$role] ?? $role;
    }
}
