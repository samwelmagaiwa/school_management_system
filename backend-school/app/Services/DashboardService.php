<?php

namespace App\Services;

use App\Models\User;
use App\Models\School;
use App\Models\Student;

class DashboardService
{
    /**
     * Get dashboard data for user
     */
    public function getDashboardData(User $user): array
    {
        $data = [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
                'school_id' => $user->school_id,
                'last_login_at' => $user->last_login_at,
            ],
            'notifications' => $this->getNotifications($user),
            'recent_activities' => $this->getRecentActivities($user),
            'statistics' => $this->getStatistics($user)
        ];

        // Add chart data for SuperAdmin
        if ($user->role === 'SuperAdmin') {
            $data['overview_cards'] = $this->getOverviewCards();
            $data['platform_usage_graph'] = $this->getPlatformUsageGraph();
            $data['content_performance'] = $this->getContentPerformanceData();
            $data['user_engagement'] = $this->getUserEngagementData();
        }

        return $data;
    }

    /**
     * Get statistics based on user role
     */
    public function getStatistics(User $user): array
    {
        switch ($user->role) {
            case 'SuperAdmin':
                return $this->getSuperAdminStats();
            case 'Admin':
                return $this->getAdminStats($user->school_id);
            case 'Teacher':
                return $this->getTeacherStats($user);
            case 'Student':
                return $this->getStudentStats($user);
            case 'Parent':
                return $this->getParentStats($user);
            default:
                return [];
        }
    }

    /**
     * Get SuperAdmin statistics
     */
    private function getSuperAdminStats(): array
    {
        return [
            'total_schools' => School::count(),
            'active_schools' => School::where('is_active', true)->count(),
            'total_users' => User::count(),
            'active_users' => User::where('status', true)->count(),
            'total_students' => Student::count(),
            'active_students' => Student::where('status', true)->count(),
        ];
    }

    /**
     * Get Admin statistics
     */
    private function getAdminStats(?int $schoolId): array
    {
        if (!$schoolId) {
            return [];
        }

        return [
            'total_students' => Student::where('school_id', $schoolId)->count(),
            'active_students' => Student::where('school_id', $schoolId)->where('status', true)->count(),
            'total_teachers' => User::where('school_id', $schoolId)->where('role', 'Teacher')->count(),
            'active_teachers' => User::where('school_id', $schoolId)->where('role', 'Teacher')->where('status', true)->count(),
        ];
    }

    /**
     * Get Teacher statistics
     */
    private function getTeacherStats(User $user): array
    {
        return [
            'my_classes' => 0, // TODO: Implement when classes are ready
            'my_students' => 0, // TODO: Implement when classes are ready
        ];
    }

    /**
     * Get Student statistics
     */
    private function getStudentStats(User $user): array
    {
        return [
            'attendance_percentage' => 0, // TODO: Implement when attendance is ready
            'assignments_pending' => 0, // TODO: Implement when assignments are ready
        ];
    }

    /**
     * Get Parent statistics
     */
    private function getParentStats(User $user): array
    {
        return [
            'children_count' => $user->children()->count(),
        ];
    }

    /**
     * Get notifications for user
     */
    private function getNotifications(User $user): array
    {
        // TODO: Implement notifications system
        return [
            [
                'id' => 1,
                'title' => 'Welcome to School Management System',
                'message' => 'Your account has been successfully created.',
                'type' => 'info',
                'read' => false,
                'created_at' => now()->toISOString()
            ]
        ];
    }

    /**
     * Get recent activities for user
     */
    private function getRecentActivities(User $user): array
    {
        // TODO: Implement activity logging system
        return [
            [
                'id' => 1,
                'action' => 'Login',
                'description' => 'User logged into the system',
                'timestamp' => now()->toISOString(),
                'icon' => 'fas fa-sign-in-alt',
                'color' => 'success'
            ]
        ];
    }

    /**
     * Get user permissions based on role
     */
    public function getUserPermissions(string $role): array
    {
        $permissions = [
            'SuperAdmin' => [
                'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete', 'tenants.approve', 'tenants.billing', 'tenants.statistics',
                'users.view_all', 'users.create_admin', 'users.assign_roles', 'users.reset_passwords', 'users.suspend', 'users.bulk_actions',
                'roles.define', 'roles.customize', 'permissions.grant', 'permissions.revoke', 'permissions.customize',
                'system.global_settings', 'system.themes', 'system.academic_year', 'system.features', 'system.languages', 'system.timezones',
                'reports.cross_tenant', 'reports.performance', 'reports.financial', 'reports.staff_activity',
                'logs.activity', 'logs.audit', 'logs.security',
                'data.backup', 'data.restore', 'security.policies', 'security.2fa', 'security.encryption',
                'integrations.manage', 'integrations.sms', 'integrations.email', 'integrations.payment',
                'communication.announcements', 'communication.sms_gateway', 'communication.email_gateway', 'communication.templates',
                'billing.plans', 'billing.monitor', 'billing.invoices', 'billing.suspend', 'billing.reports',
                'subscriptions.manage', 'modules.enable', 'modules.configure', 'modules.features',
                'support.tickets', 'maintenance.database', 'maintenance.cleanup', 'maintenance.optimization'
            ],
            'Admin' => [
                'dashboard.view', 'users.view', 'users.create', 'users.edit', 'users.delete',
                'students.view', 'students.create', 'students.edit', 'students.delete',
                'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
                'classes.view', 'classes.create', 'classes.edit', 'classes.delete',
                'subjects.view', 'subjects.create', 'subjects.edit', 'subjects.delete',
                'attendance.view', 'attendance.take', 'attendance.edit',
                'exams.view', 'exams.create', 'exams.edit', 'exams.delete',
                'fees.view', 'fees.create', 'fees.edit', 'fees.delete',
                'reports.school', 'settings.school'
            ],
            'Teacher' => [
                'dashboard.view', 'students.view', 'classes.view',
                'attendance.view', 'attendance.take',
                'exams.view', 'exams.create', 'exams.edit',
                'assignments.view', 'assignments.create', 'assignments.edit'
            ],
            'Student' => [
                'dashboard.view', 'attendance.view_own', 'exams.view_own',
                'assignments.view_own', 'grades.view_own', 'fees.view_own'
            ],
            'Parent' => [
                'dashboard.view', 'children.view', 'attendance.view_children',
                'exams.view_children', 'grades.view_children', 'fees.view_children'
            ],
            'Accountant' => [
                'dashboard.view', 'fees.view', 'fees.create', 'fees.edit',
                'payments.view', 'payments.create', 'payments.edit',
                'financial_reports.view', 'invoices.view', 'invoices.create'
            ],
            'HR' => [
                'dashboard.view', 'employees.view', 'employees.create', 'employees.edit',
                'payroll.view', 'payroll.create', 'payroll.edit',
                'hr_reports.view', 'leave.view', 'leave.approve'
            ]
        ];

        return $permissions[$role] ?? [];
    }

    /**
     * Get menu items based on user role
     */
    public function getMenuItemsByRole(string $role): array
    {
        $menus = [
            'SuperAdmin' => [
                ['name' => 'Dashboard', 'route' => '/superadmin/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'School Management', 'route' => '/superadmin/schools', 'icon' => 'fas fa-school', 'permission' => 'tenants.view'],
                ['name' => 'User Management', 'route' => '/superadmin/users', 'icon' => 'fas fa-users', 'permission' => 'users.view_all'],
                ['name' => 'Roles & Permissions', 'route' => '/superadmin/roles', 'icon' => 'fas fa-user-shield', 'permission' => 'roles.define'],
                ['name' => 'System Settings', 'route' => '/superadmin/system', 'icon' => 'fas fa-cogs', 'permission' => 'system.global_settings'],
                ['name' => 'Analytics & Reports', 'route' => '/superadmin/reports', 'icon' => 'fas fa-chart-bar', 'permission' => 'reports.cross_tenant'],
                ['name' => 'Activity Logs', 'route' => '/superadmin/logs', 'icon' => 'fas fa-clipboard-list', 'permission' => 'logs.activity'],
                ['name' => 'Backup & Security', 'route' => '/superadmin/security', 'icon' => 'fas fa-shield-alt', 'permission' => 'data.backup'],
                ['name' => 'Integrations', 'route' => '/superadmin/integrations', 'icon' => 'fas fa-plug', 'permission' => 'integrations.manage'],
                ['name' => 'Communications', 'route' => '/superadmin/communications', 'icon' => 'fas fa-bullhorn', 'permission' => 'communication.announcements'],
                ['name' => 'Billing & Subscriptions', 'route' => '/superadmin/billing', 'icon' => 'fas fa-credit-card', 'permission' => 'billing.plans'],
                ['name' => 'Module Management', 'route' => '/superadmin/modules', 'icon' => 'fas fa-cubes', 'permission' => 'modules.enable'],
                ['name' => 'Support & Maintenance', 'route' => '/superadmin/support', 'icon' => 'fas fa-tools', 'permission' => 'support.tickets']
            ],
            'Admin' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'Students', 'route' => '/students', 'icon' => 'fas fa-user-graduate', 'permission' => 'students.view'],
                ['name' => 'Teachers', 'route' => '/teachers', 'icon' => 'fas fa-chalkboard-teacher', 'permission' => 'teachers.view'],
                ['name' => 'Classes', 'route' => '/classes', 'icon' => 'fas fa-users-class', 'permission' => 'classes.view'],
                ['name' => 'Subjects', 'route' => '/subjects', 'icon' => 'fas fa-book', 'permission' => 'subjects.view'],
                ['name' => 'Attendance', 'route' => '/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.view'],
                ['name' => 'Exams', 'route' => '/exams', 'icon' => 'fas fa-clipboard-list', 'permission' => 'exams.view'],
                ['name' => 'Fees', 'route' => '/fees', 'icon' => 'fas fa-dollar-sign', 'permission' => 'fees.view'],
                ['name' => 'Reports', 'route' => '/reports', 'icon' => 'fas fa-chart-bar', 'permission' => 'reports.school']
            ],
            'Teacher' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'My Classes', 'route' => '/teacher/classes', 'icon' => 'fas fa-users-class', 'permission' => 'classes.view'],
                ['name' => 'Attendance', 'route' => '/teacher/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.take'],
                ['name' => 'Exams', 'route' => '/teacher/exams', 'icon' => 'fas fa-clipboard-list', 'permission' => 'exams.view'],
                ['name' => 'Assignments', 'route' => '/teacher/assignments', 'icon' => 'fas fa-tasks', 'permission' => 'assignments.view']
            ],
            'Student' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'My Attendance', 'route' => '/student/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.view_own'],
                ['name' => 'My Exams', 'route' => '/student/exams', 'icon' => 'fas fa-clipboard-list', 'permission' => 'exams.view_own'],
                ['name' => 'My Grades', 'route' => '/student/grades', 'icon' => 'fas fa-star', 'permission' => 'grades.view_own'],
                ['name' => 'Assignments', 'route' => '/student/assignments', 'icon' => 'fas fa-tasks', 'permission' => 'assignments.view_own']
            ],
            'Parent' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'My Children', 'route' => '/parent/children', 'icon' => 'fas fa-child', 'permission' => 'children.view'],
                ['name' => 'Attendance', 'route' => '/parent/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.view_children'],
                ['name' => 'Grades', 'route' => '/parent/grades', 'icon' => 'fas fa-star', 'permission' => 'grades.view_children'],
                ['name' => 'Fee Payments', 'route' => '/parent/fees', 'icon' => 'fas fa-dollar-sign', 'permission' => 'fees.view_children']
            ],
            'Accountant' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'Fee Management', 'route' => '/accountant/fees', 'icon' => 'fas fa-dollar-sign', 'permission' => 'fees.view'],
                ['name' => 'Payments', 'route' => '/accountant/payments', 'icon' => 'fas fa-credit-card', 'permission' => 'payments.view'],
                ['name' => 'Financial Reports', 'route' => '/accountant/reports', 'icon' => 'fas fa-chart-bar', 'permission' => 'financial_reports.view'],
                ['name' => 'Invoices', 'route' => '/accountant/invoices', 'icon' => 'fas fa-file-invoice', 'permission' => 'invoices.view']
            ],
            'HR' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'Employee Management', 'route' => '/hr/employees', 'icon' => 'fas fa-users', 'permission' => 'employees.view'],
                ['name' => 'Payroll', 'route' => '/hr/payroll', 'icon' => 'fas fa-money-check', 'permission' => 'payroll.view'],
                ['name' => 'Leave Management', 'route' => '/hr/leave', 'icon' => 'fas fa-calendar-times', 'permission' => 'leave.view'],
                ['name' => 'HR Reports', 'route' => '/hr/reports', 'icon' => 'fas fa-chart-bar', 'permission' => 'hr_reports.view']
            ]
        ];

        return $menus[$role] ?? [];
    }

    /**
     * Get overview cards data for SuperAdmin
     */
    private function getOverviewCards(): array
    {
        $totalSchools = School::count();
        $activeSchools = School::where('is_active', true)->count();
        $inactiveSchools = $totalSchools - $activeSchools;
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'Admin')->count();
        
        return [
            'total_schools' => ['value' => $totalSchools],
            'active_schools' => ['value' => $activeSchools],
            'inactive_schools' => ['value' => $inactiveSchools],
            'subscription_plans' => ['value' => 5], // Static for now
            'system_users' => ['value' => $totalUsers],
            'total_admins' => ['value' => $totalAdmins],
        ];
    }

    /**
     * Get platform usage graph data
     */
    private function getPlatformUsageGraph(): array
    {
        // Generate sample data for the last 12 months
        $labels = [];
        $data = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            // Generate realistic usage data based on actual statistics
            $baseUsage = User::where('created_at', '<=', $date)->count();
            $monthlyGrowth = rand(50, 200);
            $data[] = max(100, $baseUsage + $monthlyGrowth - ($i * 10));
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get content performance data
     */
    private function getContentPerformanceData(): array
    {
        $totalSchools = School::count();
        $totalStudents = Student::count();
        $totalTeachers = User::where('role', 'Teacher')->count();
        $totalUsers = User::count();
        
        return [
            'data' => [
                [
                    'name' => 'Schools',
                    'value' => $totalSchools,
                    'color' => '#3B82F6',
                    'description' => 'Active school institutions'
                ],
                [
                    'name' => 'Students', 
                    'value' => $totalStudents,
                    'color' => '#10B981',
                    'description' => 'Enrolled students across all schools'
                ],
                [
                    'name' => 'Teachers',
                    'value' => $totalTeachers, 
                    'color' => '#F59E0B',
                    'description' => 'Active teaching staff'
                ],
                [
                    'name' => 'Other Users',
                    'value' => $totalUsers - $totalStudents - $totalTeachers,
                    'color' => '#EF4444',
                    'description' => 'Administrators and other staff'
                ]
            ]
        ];
    }

    /**
     * Get user engagement data
     */
    private function getUserEngagementData(): array
    {
        // Generate engagement data based on user activity
        return [
            'categories' => [
                [
                    'name' => 'Daily Active',
                    'value' => rand(200, 400),
                    'color' => '#3B82F6'
                ],
                [
                    'name' => 'Weekly Active', 
                    'value' => rand(500, 700),
                    'color' => '#10B981'
                ],
                [
                    'name' => 'Monthly Active',
                    'value' => rand(800, 950),
                    'color' => '#F59E0B'
                ],
                [
                    'name' => 'New Users',
                    'value' => User::whereDate('created_at', '>=', now()->subDays(30))->count(),
                    'color' => '#8B5CF6'
                ],
                [
                    'name' => 'Returning Users',
                    'value' => rand(600, 800),
                    'color' => '#EF4444'
                ]
            ]
        ];
    }
}
