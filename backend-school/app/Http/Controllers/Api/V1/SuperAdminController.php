<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\ActivityLogger;
use App\Http\Requests\StoreSchoolRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
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
            // Check which column exists for school status
            $statusColumn = Schema::hasColumn('schools', 'is_active') ? 'is_active' : 'status';
            
            $stats = [
                'overview' => [
                    'total_schools' => School::count(),
                    'active_schools' => School::where($statusColumn, true)->count(),
                    'total_users' => User::count(),
                    'active_users' => User::where('status', true)->count(),
                    'total_students' => User::where('role', 'Student')->count(),
                    'total_teachers' => User::where('role', 'Teacher')->count(),
                ],
                'user_distribution' => User::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->get()
                    ->pluck('count', 'role'),
                'school_statistics' => School::selectRaw("
                    COUNT(*) as total_schools,
                    SUM(CASE WHEN {$statusColumn} = 1 THEN 1 ELSE 0 END) as active_schools
                ")->first(),
                'recent_activity' => [
                    'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
                    'new_schools_this_month' => School::where('created_at', '>=', now()->subMonth())->count(),
                    'logins_today' => User::whereDate('last_login_at', today())->count(),
                ]
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
            // Check which column exists for school status
            $statusColumn = Schema::hasColumn('schools', 'is_active') ? 'is_active' : 'status';
            
            $query = School::with(['users' => function($q) {
                $q->select('id', 'school_id', 'first_name', 'last_name', 'email', 'role', 'status');
            }]);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('principal_name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status') && $request->status !== '') {
                $query->where($statusColumn, (bool) $request->status);
            }

            // Apply school type filter
            if ($request->has('school_type') && !empty($request->school_type)) {
                $query->where('school_type', $request->school_type);
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['name', 'code', 'established_year', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            } else {
                $query->latest();
            }

            $schools = $query->paginate($request->per_page ?? 25);

            // Transform the data for frontend
            $schools->getCollection()->transform(function ($school) use ($statusColumn) {
                // Count users by role
                $totalUsers = $school->users->count();
                $totalStudents = $school->users->where('role', 'Student')->count();
                $totalTeachers = $school->users->where('role', 'Teacher')->count();
                $totalAdmins = $school->users->where('role', 'Admin')->count();
                $activeUsers = $school->users->where('status', true)->count();

                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'code' => $school->code,
                    'email' => $school->email,
                    'phone' => $school->phone,
                    'address' => $school->address,
                    'website' => $school->website,
                    'logo' => $school->logo,
                    'established_year' => $school->established_year,
                    'principal_name' => $school->principal_name,
                    'principal_email' => $school->principal_email,
                    'principal_phone' => $school->principal_phone,
                    'description' => $school->description,
                    'board_affiliation' => $school->board_affiliation,
                    'school_type' => $school->school_type,
                    'registration_number' => $school->registration_number,
                    'tax_id' => $school->tax_id,
                    'settings' => $school->settings,
                    'is_active' => $school->{$statusColumn},
                    'status' => $school->{$statusColumn}, // For backward compatibility
                    'created_at' => $school->created_at,
                    'updated_at' => $school->updated_at,
                    'created_at_formatted' => $school->created_at->format('M j, Y g:i A'),
                    'updated_at_formatted' => $school->updated_at->format('M j, Y g:i A'),
                    // User statistics
                    'total_users' => $totalUsers,
                    'total_students' => $totalStudents,
                    'total_teachers' => $totalTeachers,
                    'total_admins' => $totalAdmins,
                    'active_users' => $activeUsers,
                    'inactive_users' => $totalUsers - $activeUsers,
                    // Additional computed fields
                    'status_label' => $school->{$statusColumn} ? 'Active' : 'Inactive',
                    'status_badge_class' => $school->{$statusColumn} ? 'bg-success' : 'bg-danger',
                    'school_type_formatted' => $this->formatSchoolType($school->school_type),
                    'has_users' => $totalUsers > 0,
                    'can_delete' => $totalUsers === 0, // Can only delete if no users
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $schools->items(),
                'meta' => [
                    'current_page' => $schools->currentPage(),
                    'last_page' => $schools->lastPage(),
                    'per_page' => $schools->perPage(),
                    'total' => $schools->total(),
                    'from' => $schools->firstItem(),
                    'to' => $schools->lastItem(),
                    'has_more_pages' => $schools->hasMorePages(),
                    'path' => $schools->path(),
                    'links' => [
                        'first' => $schools->url(1),
                        'last' => $schools->url($schools->lastPage()),
                        'prev' => $schools->previousPageUrl(),
                        'next' => $schools->nextPageUrl(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch schools: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
    public function createSchool(StoreSchoolRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            // Remove the temporary established_year_date field before creating school
            unset($validatedData['established_year_date']);
            
            $school = School::create($validatedData);

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
     * Export schools to CSV
     */
    public function exportSchools(Request $request)
    {
        try {
            // Check which column exists for school status
            $statusColumn = Schema::hasColumn('schools', 'is_active') ? 'is_active' : 'status';
            
            $query = School::query();

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('principal_name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status') && $request->status !== '') {
                $query->where($statusColumn, (bool) $request->status);
            }

            // Apply school type filter
            if ($request->has('school_type') && !empty($request->school_type)) {
                $query->where('school_type', $request->school_type);
            }

            $schools = $query->get();

            // Prepare CSV data
            $csvData = [];
            $csvData[] = [
                'ID',
                'Name',
                'Code', 
                'Email',
                'Phone',
                'Address',
                'Website',
                'Established Year',
                'Principal Name',
                'Principal Email',
                'Principal Phone',
                'Description',
                'Board Affiliation',
                'School Type',
                'Registration Number',
                'Tax ID',
                'Status',
                'Created At',
                'Updated At'
            ];

            foreach ($schools as $school) {
                $csvData[] = [
                    $school->id,
                    $school->name,
                    $school->code,
                    $school->email,
                    $school->phone,
                    $school->address,
                    $school->website,
                    $school->established_year,
                    $school->principal_name,
                    $school->principal_email,
                    $school->principal_phone,
                    $school->description,
                    $school->board_affiliation,
                    $this->formatSchoolType($school->school_type),
                    $school->registration_number,
                    $school->tax_id,
                    $school->{$statusColumn} ? 'Active' : 'Inactive',
                    $school->created_at ? $school->created_at->format('Y-m-d H:i:s') : '',
                    $school->updated_at ? $school->updated_at->format('Y-m-d H:i:s') : ''
                ];
            }

            // Generate CSV content
            $csvContent = "";
            foreach ($csvData as $row) {
                $csvContent .= '"' . implode('","', $row) . '"' . "\n";
            }

            $filename = 'schools_export_' . date('Y-m-d_H-i-s') . '.csv';

            ActivityLogger::log('Schools Exported', 'Export', [
                'exported_by' => $request->user()->id,
                'total_schools' => $schools->count(),
                'filters' => $request->only(['search', 'status', 'school_type'])
            ]);

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to export schools: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export schools',
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
                $query->where('is_active', $request->status);
            }

            $users = $query->latest()->paginate($request->per_page ?? 15);

            // Transform user data to include frontend-expected properties
            $users->getCollection()->transform(function ($user) {
                try {
                    // Comprehensive validation and fallback for user objects
                    if (!$user || !is_object($user) || !isset($user->id)) {
                        return $this->getUserFallback($user);
                    }
                    
                    // Start with the fallback to ensure all properties exist
                    $userData = $this->getUserFallback($user);
                    
                    // Override with actual user data
                    $actualData = $user->toArray();
                    $userData = array_merge($userData, $actualData);
                    
                    // Add permission-related properties with error handling
                    try {
                        $permissions = [
                            'can_reset_password' => $this->canResetPassword($user),
                            'can_delete' => $this->canDeleteUser($user),
                            'can_edit' => $this->canEditUser($user),
                            'can_change_role' => $this->canChangeRole($user),
                            'can_change_status' => $this->canChangeStatus($user),
                            'can_resend_invitation' => $this->canResendInvitation($user),
                            'can_impersonate' => $this->canLoginAs($user)
                        ];
                        
                        // Set both individual permissions and permissions object for frontend compatibility
                        $userData['permissions'] = $permissions;
                        $userData = array_merge($userData, $permissions);
                    } catch (\Exception $e) {
                        // Set safe fallback permissions
                        $fallbackPermissions = [
                            'can_reset_password' => false,
                            'can_delete' => false,
                            'can_edit' => false,
                            'can_change_role' => false,
                            'can_change_status' => false,
                            'can_resend_invitation' => false,
                            'can_impersonate' => false
                        ];
                        $userData['permissions'] = $fallbackPermissions;
                        $userData = array_merge($userData, $fallbackPermissions);
                        error_log('Permission check failed for user ' . ($user->id ?? 'unknown') . ': ' . $e->getMessage());
                    }
                    
                    // Override display properties with computed values
                    $userData['full_name'] = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Unknown User';
                    $userData['role_label'] = $this->getRoleLabel($user->role ?? 'Student');
                    $userData['status_label'] = ($user->status ?? false) ? 'Active' : 'Inactive';
                    $userData['status_badge_class'] = ($user->status ?? false) ? 'bg-success' : 'bg-danger';
                    
                    // Add email verification status
                    $userData['email_verified'] = !empty($user->email_verified_at);
                    
                    // Override formatted dates
                    $userData['created_at_formatted'] = $user->created_at ? $user->created_at->format('M j, Y g:i A') : 'Not Available';
                    $userData['updated_at_formatted'] = $user->updated_at ? $user->updated_at->format('M j, Y g:i A') : 'Not Available';
                    $userData['last_login_formatted'] = $user->last_login_at ? $user->last_login_at->format('M j, Y g:i A') : 'Never';
                    $userData['last_login_human'] = $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never logged in';
                    
                    // Override school information
                    $userData['school_name'] = ($user->school && $user->school->name) ? $user->school->name : 'No School Assigned';
                    $userData['school_code'] = ($user->school && $user->school->code) ? $user->school->code : null;
                    
                    // Override avatar properties
                    $userData['avatar_url'] = ($user->profile_picture ?? false) ? url('storage/' . $user->profile_picture) : null;
                    $firstName = $user->first_name ?? 'N';
                    $lastName = $user->last_name ?? 'N';
                    $userData['avatar_initials'] = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?: 'NN';
                    
                    return $userData;
                    
                } catch (\Exception $e) {
                    // If anything fails, return safe fallback
                    error_log('User transformation failed for user ' . ($user->id ?? 'unknown') . ': ' . $e->getMessage());
                    return $this->getUserFallback($user);
                }
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
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'role' => 'required|in:SuperAdmin,Admin,Teacher,Student,Parent,HR,Accountant',
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
     * Update user status (activate/deactivate)
     */
    public function updateUserStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'status' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->status = $request->status;
            $user->save();

            ActivityLogger::log('User Status Updated', 'User', [
                'user_id' => $user->id,
                'new_status' => $user->status ? 'Active' : 'Inactive',
                'updated_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user statistics for SuperAdmin
     */
    public function getUserStatistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'overview' => [
                    'total_users' => User::count(),
                    'active_users' => User::where('status', true)->count(),
                    'inactive_users' => User::where('status', false)->count(),
                    'users_this_month' => User::whereMonth('created_at', now()->month)->count(),
                    'users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
                    'logins_today' => User::whereDate('last_login_at', today())->count(),
                ],
                'role_distribution' => User::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->map(function($item) {
                        return [
                            'role' => $item->role,
                            'count' => $item->count,
                            'percentage' => round(($item->count / User::count()) * 100, 2)
                        ];
                    }),
                'school_distribution' => School::withCount('users')
                    ->orderBy('users_count', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($school) {
                        return [
                            'school_id' => $school->id,
                            'school_name' => $school->name,
                            'school_code' => $school->code,
                            'user_count' => $school->users_count
                        ];
                    }),
                'status_distribution' => [
                    'active' => User::where('status', true)->count(),
                    'inactive' => User::where('status', false)->count()
                ],
                'monthly_registrations' => User::selectRaw('MONTH(created_at) as month, MONTHNAME(created_at) as month_name, COUNT(*) as count')
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_name')
                    ->orderBy('month')
                    ->get(),
                'recent_activity' => [
                    'last_registered' => User::latest('created_at')
                        ->with('school:id,name')
                        ->limit(5)
                        ->get()
                        ->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->first_name . ' ' . $user->last_name,
                                'email' => $user->email,
                                'role' => $user->role,
                                'school' => $user->school ? $user->school->name : 'No School',
                                'registered_at' => $user->created_at->format('Y-m-d H:i:s')
                            ];
                        }),
                    'last_login' => User::whereNotNull('last_login_at')
                        ->latest('last_login_at')
                        ->with('school:id,name')
                        ->limit(5)
                        ->get()
                        ->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->first_name . ' ' . $user->last_name,
                                'email' => $user->email,
                                'role' => $user->role,
                                'school' => $user->school ? $user->school->name : 'No School',
                                'last_login' => $user->last_login_at->format('Y-m-d H:i:s')
                            ];
                        })
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get users by schools for SuperAdmin
     */
    public function getUsersBySchools(Request $request): JsonResponse
    {
        try {
            $query = School::with(['users' => function($q) {
                $q->select('id', 'school_id', 'first_name', 'last_name', 'email', 'role', 'status', 'created_at', 'last_login_at');
            }]);

            // Apply search filter
            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $search = $request->search;
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $schools = $query->get();

            $result = $schools->map(function ($school) {
                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'code' => $school->code,
                    'email' => $school->email,
                    'status' => $school->status,
                    'total_users' => $school->users->count(),
                    'user_distribution' => [
                        'total' => $school->users->count(),
                        'active' => $school->users->where('status', true)->count(),
                        'inactive' => $school->users->where('status', false)->count(),
                        'by_role' => $school->users->groupBy('role')->map(function($roleUsers) {
                            return $roleUsers->count();
                        })
                    ],
                    'users' => $school->users->map(function($user) {
                        return [
                            'id' => $user->id,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'full_name' => trim($user->first_name . ' ' . $user->last_name),
                            'email' => $user->email,
                            'role' => $user->role,
                            'role_label' => $this->getRoleLabel($user->role),
                            'status' => $user->status,
                            'status_label' => $user->status ? 'Active' : 'Inactive',
                            'status_badge_class' => $user->status ? 'bg-success' : 'bg-danger',
                            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                            'last_login_at' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : null,
                            'created_at_formatted' => $user->created_at->format('M j, Y g:i A'),
                            'last_login_formatted' => $user->last_login_at ? $user->last_login_at->format('M j, Y g:i A') : 'Never',
                            'avatar_initials' => strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)),
                            'can_reset_password' => $this->canResetPassword($user),
                            'can_delete' => $this->canDeleteUser($user),
                            'can_edit' => $this->canEditUser($user),
                            'can_change_role' => $this->canChangeRole($user),
                            'can_change_status' => $this->canChangeStatus($user),
                            'can_resend_invitation' => $this->canResendInvitation($user),
                            'can_login_as' => $this->canLoginAs($user)
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users by schools',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user roles management for SuperAdmin
     */
    public function getUserRoles(Request $request): JsonResponse
    {
        try {
            $availableRoles = [
                'SuperAdmin' => 'Super Administrator',
                'Admin' => 'School Administrator', 
                'Teacher' => 'Teacher',
                'Student' => 'Student',
                'Parent' => 'Parent',
                'HR' => 'Human Resources',
                'Accountant' => 'Accountant'
            ];

            $roleStatistics = User::selectRaw('role, COUNT(*) as user_count')
                ->groupBy('role')
                ->get()
                ->keyBy('role');

            $rolePermissions = [
                'SuperAdmin' => [
                    'all_system_access',
                    'manage_schools',
                    'manage_users',
                    'view_reports',
                    'system_configuration'
                ],
                'Admin' => [
                    'school_management',
                    'user_management_school',
                    'academic_management',
                    'reports_school'
                ],
                'Teacher' => [
                    'class_management',
                    'student_grades',
                    'attendance_management',
                    'assignments'
                ],
                'Student' => [
                    'view_grades',
                    'view_attendance',
                    'submit_assignments',
                    'view_schedule'
                ],
                'Parent' => [
                    'view_child_progress',
                    'communication',
                    'fee_payments',
                    'events_notifications'
                ],
                'HR' => [
                    'employee_management',
                    'payroll_management',
                    'recruitment',
                    'staff_reports'
                ],
                'Accountant' => [
                    'financial_management',
                    'fee_collection',
                    'expense_tracking',
                    'financial_reports'
                ]
            ];

            $result = collect($availableRoles)->map(function($label, $role) use ($roleStatistics, $rolePermissions) {
                $stats = $roleStatistics->get($role);
                
                return [
                    'role' => $role,
                    'label' => $label,
                    'user_count' => $stats ? $stats->user_count : 0,
                    'permissions' => $rolePermissions[$role] ?? [],
                    'is_system_role' => in_array($role, ['SuperAdmin']),
                    'can_manage_school' => in_array($role, ['SuperAdmin', 'Admin']),
                    'can_teach' => in_array($role, ['Teacher']),
                    'is_student_role' => in_array($role, ['Student', 'Parent'])
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'available_roles' => $result,
                    'total_users' => User::count(),
                    'role_distribution' => $roleStatistics->values(),
                    'recent_role_changes' => User::whereNotNull('updated_at')
                        ->where('updated_at', '>', now()->subDays(7))
                        ->select('id', 'first_name', 'last_name', 'email', 'role', 'updated_at')
                        ->orderBy('updated_at', 'desc')
                        ->limit(10)
                        ->get()
                        ->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->first_name . ' ' . $user->last_name,
                                'email' => $user->email,
                                'role' => $user->role,
                                'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                            ];
                        })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user roles',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper method to determine if a user's password can be reset
     */
    private function canResetPassword(User $user): bool
    {
        // SuperAdmins can reset any password except other SuperAdmins (unless current user is also SuperAdmin)
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

    /**
     * Helper method to format school type
     */
    private function formatSchoolType($type): string
    {
        if ($type === null || $type === '') {
            return 'Not Specified';
        }
        
        $typeLabels = [
            'primary' => 'Primary School',
            'secondary' => 'Secondary School',
            'higher_secondary' => 'Higher Secondary School',
            'all' => 'Primary & Secondary School'
        ];
        
        return $typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Generate a safe fallback user object with all required properties
     */
    private function getUserFallback($user = null): array
    {
        $id = $user->id ?? null;
        $firstName = $user->first_name ?? '';
        $lastName = $user->last_name ?? '';
        $email = $user->email ?? '';
        $role = $user->role ?? 'Student';
        
        return [
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'email_verified_at' => $user->email_verified_at ?? null,
            'role' => $role,
            'phone' => $user->phone ?? null,
            'address' => $user->address ?? null,
            'status' => $user->status ?? false,
            'date_of_birth' => $user->date_of_birth ?? null,
            'gender' => $user->gender ?? null,
            'school_id' => $user->school_id ?? null,
            'profile_picture' => $user->profile_picture ?? null,
            'last_login_at' => $user->last_login_at ?? null,
            'created_at' => $user->created_at ?? now(),
            'updated_at' => $user->updated_at ?? now(),
            'deleted_at' => $user->deleted_at ?? null,
            'school' => $user && $user->school ? [
                'id' => $user->school->id,
                'name' => $user->school->name,
                'code' => $user->school->code
            ] : null,
            // Permission properties - always safe defaults for invalid users
            'can_reset_password' => false,
            'can_delete' => false,
            'can_edit' => false,
            'can_change_role' => false,
            'can_change_status' => false,
            'can_resend_invitation' => false,
            'can_impersonate' => false,
            'permissions' => [
                'can_reset_password' => false,
                'can_delete' => false,
                'can_edit' => false,
                'can_change_role' => false,
                'can_change_status' => false,
                'can_resend_invitation' => false,
                'can_impersonate' => false
            ],
            // Display properties
            'full_name' => trim($firstName . ' ' . $lastName) ?: 'Unknown User',
            'role_label' => $this->getRoleLabel($role),
            'status_label' => ($user->status ?? false) ? 'Active' : 'Inactive',
            'status_badge_class' => ($user->status ?? false) ? 'bg-success' : 'bg-danger',
            'email_verified' => $user && !empty($user->email_verified_at),
            // Formatted dates
            'created_at_formatted' => $user && $user->created_at ? $user->created_at->format('M j, Y g:i A') : 'Not Available',
            'updated_at_formatted' => $user && $user->updated_at ? $user->updated_at->format('M j, Y g:i A') : 'Not Available',
            'last_login_formatted' => $user && $user->last_login_at ? $user->last_login_at->format('M j, Y g:i A') : 'Never',
            'last_login_human' => $user && $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never logged in',
            // School information
            'school_name' => $user && $user->school && $user->school->name ? $user->school->name : 'No School Assigned',
            'school_code' => $user && $user->school && $user->school->code ? $user->school->code : null,
            // Avatar properties
            'avatar_url' => $user && $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
            'avatar_initials' => strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?: 'NN'
        ];
    }


    /**
     * Get available schools for user assignment
     */
    public function getUserSchools(Request $request): JsonResponse
    {
        try {
            // Check which column exists for school status
            $statusColumn = Schema::hasColumn('schools', 'is_active') ? 'is_active' : 'status';
            
            $schools = School::where($statusColumn, true)
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get();

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
                    
                'school_statistics' => (function() {
                    $statusColumn = Schema::hasColumn('schools', 'is_active') ? 'is_active' : 'status';
                    return School::selectRaw("
                        COUNT(*) as total_schools,
                        SUM(CASE WHEN {$statusColumn} = 1 THEN 1 ELSE 0 END) as active_schools
                    ")->first();
                })(),
                
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
     * Get school statistics for SuperAdmin
     */
    public function getSchoolStatistics(Request $request): JsonResponse
    {
        try {
            // Add some basic error checking
            if (!$request->user() || !$request->user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
            // Check which column exists for school status
            $statusColumn = Schema::hasColumn('schools', 'is_active') ? 'is_active' : 'status';
            
            $stats = [
                'overview' => [
                    'total_schools' => School::count(),
                    'active_schools' => School::where($statusColumn, true)->count(),
                    'inactive_schools' => School::where($statusColumn, false)->count(),
                    'pending_approval' => School::where($statusColumn, false)->count(),
                ],
                'school_types' => [
                    'Public' => School::count(), // All schools considered public for now
                ],
                'geographical_distribution' => School::selectRaw('SUBSTRING_INDEX(address, ",", -1) as region, COUNT(*) as count')
                    ->whereNotNull('address')
                    ->groupBy('region')
                    ->limit(10)
                    ->get()
                    ->pluck('count', 'region'),
                'subscription_status' => [
                    'Free' => School::count() // All schools are free for now
                ],
                'monthly_registrations' => School::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'year')
                    ->orderBy('month')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'month' => date('F', mktime(0, 0, 0, $item->month, 1)),
                            'year' => $item->year,
                            'count' => $item->count
                        ];
                    }),
                'user_statistics_by_school' => School::withCount(['users as total_users'])
                    ->withCount(['users as active_users' => function($query) {
                        $userStatusColumn = \Schema::hasColumn('users', 'is_active') ? 'is_active' : 'status';
                        $query->where($userStatusColumn, true);
                    }])
                    ->withCount(['users as teachers' => function($query) {
                        $query->where('role', 'Teacher');
                    }])
                    ->withCount(['users as students' => function($query) {
                        $query->where('role', 'Student');
                    }])
                    ->withCount(['users as admins' => function($query) {
                        $query->where('role', 'Admin');
                    }])
                    ->get()
                    ->map(function ($school) {
                        return [
                            'school_name' => $school->name,
                            'school_code' => $school->code,
                            'total_users' => $school->total_users,
                            'active_users' => $school->active_users,
                            'teachers' => $school->teachers,
                            'students' => $school->students,
                            'admins' => $school->admins,
                            'established_date' => $school->established_date,
                            'principal_name' => $school->principal_name
                        ];
                    }),
                'top_schools_by_users' => School::withCount('users')
                    ->orderBy('users_count', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($school) {
                        return [
                            'name' => $school->name,
                            'code' => $school->code,
                            'users_count' => $school->users_count,
                            'established_date' => $school->established_date
                        ];
                    }),
                'recent_activity' => [
                    'new_schools_this_week' => School::where('created_at', '>=', now()->subWeek())->count(),
                    'new_schools_this_month' => School::where('created_at', '>=', now()->subMonth())->count(),
                    'schools_activated_today' => School::whereDate('updated_at', today())
                        ->where($statusColumn, true)
                        ->count(),
                    'recent_schools' => School::latest()
                        ->limit(5)
                        ->select('id', 'name', 'code', 'email', 'created_at', $statusColumn)
                        ->get()
                        ->map(function ($school) use ($statusColumn) {
                            return [
                                'id' => $school->id,
                                'name' => $school->name,
                                'code' => $school->code,
                                'email' => $school->email,
                                'status' => $school->{$statusColumn},
                                'created_at' => $school->created_at->format('Y-m-d H:i:s'),
                                'created_at_formatted' => $school->created_at->format('M j, Y g:i A')
                            ];
                        })
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('School Statistics Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch school statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
