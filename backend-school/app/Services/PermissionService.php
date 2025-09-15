<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Define all system permissions organized by modules
     */
    private const PERMISSIONS = [
        'superadmin' => [
            'manage_system',
            'manage_all_schools',
            'manage_all_users',
            'view_system_reports',
            'manage_system_settings',
            'delete_any_data',
            'export_all_data',
            'manage_roles',
        ],
        'schools' => [
            'view_schools',
            'create_schools',
            'update_schools',
            'delete_schools',
            'manage_school_settings',
            'view_school_statistics',
            'export_school_data',
        ],
        'users' => [
            'view_users',
            'create_users',
            'update_users',
            'delete_users',
            'manage_user_roles',
            'reset_user_passwords',
            'activate_deactivate_users',
            'view_user_statistics',
            'export_user_data',
        ],
        'students' => [
            'view_students',
            'create_students',
            'update_students',
            'delete_students',
            'promote_students',
            'transfer_students',
            'view_student_grades',
            'update_student_grades',
            'view_student_attendance',
            'manage_student_attendance',
            'view_student_statistics',
            'export_student_data',
            'generate_student_reports',
        ],
        'teachers' => [
            'view_teachers',
            'create_teachers',
            'update_teachers',
            'delete_teachers',
            'assign_teachers',
            'view_teacher_performance',
            'manage_teacher_schedules',
            'view_teacher_statistics',
            'export_teacher_data',
        ],
        'classes' => [
            'view_classes',
            'create_classes',
            'update_classes',
            'delete_classes',
            'assign_students_to_classes',
            'assign_teachers_to_classes',
            'view_class_statistics',
            'manage_class_schedules',
        ],
        'subjects' => [
            'view_subjects',
            'create_subjects',
            'update_subjects',
            'delete_subjects',
            'assign_subjects',
            'manage_subject_curriculum',
        ],
        'attendance' => [
            'view_attendance',
            'mark_attendance',
            'update_attendance',
            'generate_attendance_reports',
            'view_attendance_statistics',
            'export_attendance_data',
        ],
        'exams' => [
            'view_exams',
            'create_exams',
            'update_exams',
            'delete_exams',
            'schedule_exams',
            'grade_exams',
            'publish_exam_results',
            'view_exam_statistics',
            'generate_exam_reports',
        ],
        'fees' => [
            'view_fees',
            'create_fees',
            'update_fees',
            'delete_fees',
            'collect_fees',
            'generate_fee_invoices',
            'generate_fee_receipts',
            'view_fee_statistics',
            'generate_fee_reports',
            'manage_fee_categories',
        ],
        'library' => [
            'view_library',
            'manage_books',
            'issue_books',
            'return_books',
            'generate_library_reports',
            'view_library_statistics',
        ],
        'transport' => [
            'view_transport',
            'manage_vehicles',
            'manage_routes',
            'assign_transport',
            'view_transport_statistics',
        ],
        'reports' => [
            'view_financial_reports',
            'view_academic_reports',
            'view_administrative_reports',
            'export_reports',
            'schedule_reports',
        ],
    ];

    /**
     * Role-based permission mapping
     */
    private const ROLE_PERMISSIONS = [
        'SuperAdmin' => ['*'], // All permissions
        'Admin' => [
            'schools.view_schools',
            'schools.manage_school_settings',
            'schools.view_school_statistics',
            'users.view_users',
            'users.create_users',
            'users.update_users',
            'users.manage_user_roles',
            'users.reset_user_passwords',
            'users.activate_deactivate_users',
            'students.*',
            'teachers.*',
            'classes.*',
            'subjects.*',
            'attendance.*',
            'exams.*',
            'fees.*',
            'library.*',
            'transport.*',
            'reports.view_academic_reports',
            'reports.view_administrative_reports',
            'reports.export_reports',
        ],
        'Teacher' => [
            'students.view_students',
            'students.view_student_grades',
            'students.update_student_grades',
            'students.view_student_attendance',
            'students.manage_student_attendance',
            'classes.view_classes',
            'subjects.view_subjects',
            'attendance.view_attendance',
            'attendance.mark_attendance',
            'attendance.update_attendance',
            'exams.view_exams',
            'exams.create_exams',
            'exams.grade_exams',
            'library.view_library',
            'reports.view_academic_reports',
        ],
        'Student' => [
            'students.view_students', // Own record only
            'students.view_student_grades', // Own grades only
            'students.view_student_attendance', // Own attendance only
            'classes.view_classes', // Own classes only
            'subjects.view_subjects', // Enrolled subjects only
            'attendance.view_attendance', // Own attendance only
            'exams.view_exams', // Own exams only
            'fees.view_fees', // Own fees only
            'library.view_library', // Limited access
            'transport.view_transport', // Own transport only
        ],
        'Parent' => [
            'students.view_students', // Children only
            'students.view_student_grades', // Children's grades only
            'students.view_student_attendance', // Children's attendance only
            'classes.view_classes', // Children's classes only
            'subjects.view_subjects', // Children's subjects only
            'attendance.view_attendance', // Children's attendance only
            'exams.view_exams', // Children's exams only
            'fees.view_fees', // Children's fees only
            'fees.collect_fees', // Pay children's fees
            'transport.view_transport', // Children's transport only
        ],
        'HR' => [
            'users.view_users',
            'users.create_users',
            'users.update_users',
            'users.manage_user_roles',
            'teachers.view_teachers',
            'teachers.create_teachers',
            'teachers.update_teachers',
            'teachers.view_teacher_performance',
            'reports.view_administrative_reports',
        ],
        'Accountant' => [
            'fees.*',
            'reports.view_financial_reports',
            'reports.export_reports',
            'students.view_students', // For fee purposes
            'users.view_users', // For fee purposes
        ],
    ];

    /**
     * Check if user has specific permission
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // SuperAdmin has all permissions
        if ($user->isSuperAdmin()) {
            return true;
        }

        $userPermissions = $this->getUserPermissions($user);

        // Check for exact permission match
        if (in_array($permission, $userPermissions)) {
            return true;
        }

        // Check for wildcard permissions (e.g., 'students.*')
        foreach ($userPermissions as $userPermission) {
            if (str_ends_with($userPermission, '.*')) {
                $module = str_replace('.*', '', $userPermission);
                if (str_starts_with($permission, $module . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return $this->getAllPermissions();
        }

        return self::ROLE_PERMISSIONS[$user->role] ?? [];
    }

    /**
     * Get all available permissions in the system
     */
    public function getAllPermissions(): array
    {
        $allPermissions = [];
        foreach (self::PERMISSIONS as $module => $permissions) {
            foreach ($permissions as $permission) {
                $allPermissions[] = $module . '.' . $permission;
            }
        }
        return $allPermissions;
    }

    /**
     * Get permissions by module
     */
    public function getModulePermissions(string $module): array
    {
        if (!isset(self::PERMISSIONS[$module])) {
            return [];
        }

        $permissions = [];
        foreach (self::PERMISSIONS[$module] as $permission) {
            $permissions[] = $module . '.' . $permission;
        }
        return $permissions;
    }

    /**
     * Check if user can access a specific resource based on school context
     */
    public function canAccessResource(User $user, $resource, string $action = 'view'): bool
    {
        // Build permission string
        $resourceType = class_basename($resource);
        $moduleMap = [
            'School' => 'schools',
            'User' => 'users', 
            'Student' => 'students',
            'Teacher' => 'teachers',
            'ClassModel' => 'classes',
            'Subject' => 'subjects',
            'Attendance' => 'attendance',
            'Exam' => 'exams',
            'Fee' => 'fees',
        ];

        $module = $moduleMap[$resourceType] ?? strtolower($resourceType);
        $permission = $module . '.' . $action . '_' . $module;

        // Check basic permission
        if (!$this->hasPermission($user, $permission)) {
            return false;
        }

        // SuperAdmin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check school-based access for non-SuperAdmin users
        if (property_exists($resource, 'school_id') && $resource->school_id) {
            // User must be in the same school or be SuperAdmin
            return $user->school_id === $resource->school_id;
        }

        // For resources without school context, allow if user has permission
        return true;
    }

    /**
     * Get user's accessible school IDs
     */
    public function getAccessibleSchoolIds(User $user): array
    {
        if ($user->isSuperAdmin()) {
            // SuperAdmin can access all schools
            return \App\Models\School::pluck('id')->toArray();
        }

        // Other users can only access their own school
        return $user->school_id ? [$user->school_id] : [];
    }

    /**
     * Authorize user action or throw exception
     */
    public function authorize(User $user, string $permission): void
    {
        if (!$this->hasPermission($user, $permission)) {
            abort(403, "You don't have permission to perform this action. Required permission: {$permission}");
        }
    }

    /**
     * Get user's role capabilities summary
     */
    public function getRoleCapabilities(User $user): array
    {
        $permissions = $this->getUserPermissions($user);
        $capabilities = [];

        foreach (self::PERMISSIONS as $module => $modulePermissions) {
            $capabilities[$module] = [];
            foreach ($modulePermissions as $permission) {
                $fullPermission = $module . '.' . $permission;
                $capabilities[$module][$permission] = in_array($fullPermission, $permissions) || 
                                                     in_array($module . '.*', $permissions) ||
                                                     $user->isSuperAdmin();
            }
        }

        return $capabilities;
    }

    /**
     * Check if action is allowed on specific data context
     */
    public function authorizeDataAccess(User $user, string $action, $data): bool
    {
        // Handle different data access patterns based on user role and data ownership
        switch ($user->role) {
            case 'Student':
                return $this->authorizeStudentDataAccess($user, $action, $data);
            case 'Parent':
                return $this->authorizeParentDataAccess($user, $action, $data);
            case 'Teacher':
                return $this->authorizeTeacherDataAccess($user, $action, $data);
            case 'Admin':
                return $this->authorizeAdminDataAccess($user, $action, $data);
            case 'SuperAdmin':
                return true; // SuperAdmin can access all data
            default:
                return false;
        }
    }

    private function authorizeStudentDataAccess(User $user, string $action, $data): bool
    {
        // Students can only access their own data
        if (property_exists($data, 'student_id')) {
            return $user->student && $user->student->id === $data->student_id;
        }
        if (property_exists($data, 'user_id')) {
            return $user->id === $data->user_id;
        }
        return false;
    }

    private function authorizeParentDataAccess(User $user, string $action, $data): bool
    {
        // Parents can access their children's data
        if (property_exists($data, 'student_id')) {
            return $user->children && $user->children->contains('id', $data->student_id);
        }
        return false;
    }

    private function authorizeTeacherDataAccess(User $user, string $action, $data): bool
    {
        // Teachers can access data from their school
        if (property_exists($data, 'school_id')) {
            return $user->school_id === $data->school_id;
        }
        return false;
    }

    private function authorizeAdminDataAccess(User $user, string $action, $data): bool
    {
        // Admins can access data from their school
        if (property_exists($data, 'school_id')) {
            return $user->school_id === $data->school_id;
        }
        return true; // Allow access to data without school context
    }
}
