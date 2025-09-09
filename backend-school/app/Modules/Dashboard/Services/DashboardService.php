<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Teacher\Models\Teacher;
use App\Modules\Class\Models\SchoolClass;
use App\Modules\Subject\Models\Subject;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Fee\Models\Fee;
use App\Modules\Exam\Models\Exam;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get comprehensive dashboard data for user
     */
    public function getDashboardData(User $user): array
    {
        $cacheKey = "dashboard_data_{$user->id}_{$user->role}_{$user->school_id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            $dashboardData = [
                'user' => $this->getUserData($user),
                'school' => $this->getSchoolData($user),
                'stats' => $this->getStatsForRole($user),
                'permissions' => $this->getUserPermissions($user->role),
                'menu_items' => $this->getMenuItemsByRole($user->role),
                'quick_stats' => $this->getQuickStats($user),
                'recent_activities' => $this->getRecentActivities($user),
                'notifications' => $this->getNotifications($user),
                'charts_data' => $this->getChartsData($user),
                'upcoming_events' => $this->getUpcomingEvents($user),
                'timestamp' => now()->toISOString()
            ];

            ActivityLogger::log('Dashboard Data Retrieved', 'Dashboard', [
                'user_role' => $user->role,
                'school_id' => $user->school_id,
                'data_sections' => array_keys($dashboardData)
            ]);

            return $dashboardData;
        });
    }

    /**
     * Get user data
     */
    private function getUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
            'school_id' => $user->school_id,
            'profile_picture' => $user->profile_picture,
            'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
            'created_at' => $user->created_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Get school data
     */
    private function getSchoolData(User $user): ?array
    {
        if (!$user->school) {
            return null;
        }

        return [
            'id' => $user->school->id,
            'name' => $user->school->name,
            'code' => $user->school->code,
            'address' => $user->school->address,
            'phone' => $user->school->phone,
            'email' => $user->school->email,
            'logo' => $user->school->logo,
            'established_year' => $user->school->established_year
        ];
    }

    /**
     * Get statistics based on user role
     */
    private function getStatsForRole(User $user): array
    {
        switch ($user->role) {
            case 'SuperAdmin':
                return $this->getSuperAdminStats();
            case 'Admin':
                return $this->getAdminStats($user);
            case 'Teacher':
                return $this->getTeacherStats($user);
            case 'Student':
                return $this->getStudentStats($user);
            case 'Parent':
                return $this->getParentStats($user);
            case 'HR':
                return $this->getHRStats($user);
            default:
                return [];
        }
    }

    /**
     * Get Super Admin statistics
     */
    private function getSuperAdminStats(): array
    {
        return [
            'total_schools' => School::count(),
            'active_schools' => School::where('is_active', true)->count(),
            'total_users' => User::count(),
            'active_users' => User::where('status', true)->count(),
            'total_students' => User::where('role', 'Student')->count(),
            'total_teachers' => User::where('role', 'Teacher')->count(),
            'total_admins' => User::where('role', 'Admin')->count(),
            'system_health' => $this->getSystemHealth(),
            'monthly_growth' => $this->getMonthlyGrowth()
        ];
    }

    /**
     * Get Admin statistics
     */
    private function getAdminStats(User $user): array
    {
        $schoolId = $user->school_id;
        
        return [
            'school_users' => User::where('school_id', $schoolId)->count(),
            'school_students' => User::where('school_id', $schoolId)->where('role', 'Student')->count(),
            'school_teachers' => User::where('school_id', $schoolId)->where('role', 'Teacher')->count(),
            'school_classes' => SchoolClass::where('school_id', $schoolId)->count(),
            'active_classes' => SchoolClass::where('school_id', $schoolId)->where('is_active', true)->count(),
            'total_subjects' => Subject::where('school_id', $schoolId)->count(),
            'attendance_rate' => $this->getSchoolAttendanceRate($schoolId),
            'fee_collection_rate' => $this->getFeeCollectionRate($schoolId),
            'upcoming_exams' => $this->getUpcomingExamsCount($schoolId),
            'pending_admissions' => $this->getPendingAdmissionsCount($schoolId)
        ];
    }

    /**
     * Get Teacher statistics
     */
    private function getTeacherStats(User $user): array
    {
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return ['error' => 'Teacher profile not found'];
        }

        $myClasses = SchoolClass::where('class_teacher_id', $teacher->id)->get();
        $myStudents = Student::whereIn('class_id', $myClasses->pluck('id'))->get();

        return [
            'my_classes' => $myClasses->count(),
            'my_students' => $myStudents->count(),
            'subjects_teaching' => $teacher->subjects()->count(),
            'attendance_taken_today' => $this->getAttendanceTakenToday($teacher->id),
            'pending_grades' => $this->getPendingGrades($teacher->id),
            'upcoming_classes' => $this->getUpcomingClasses($teacher->id),
            'average_attendance' => $this->getTeacherClassesAttendance($teacher->id),
            'recent_activities' => $this->getTeacherRecentActivities($teacher->id)
        ];
    }

    /**
     * Get Student statistics
     */
    private function getStudentStats(User $user): array
    {
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return ['error' => 'Student profile not found'];
        }

        return [
            'current_class' => $student->class?->full_name,
            'subjects_enrolled' => $student->class?->subjects()->count() ?? 0,
            'attendance_percentage' => $this->getStudentAttendancePercentage($student->id),
            'current_gpa' => $this->getStudentGPA($student->id),
            'pending_assignments' => $this->getPendingAssignments($student->id),
            'upcoming_exams' => $this->getStudentUpcomingExams($student->id),
            'fee_status' => $this->getStudentFeeStatus($student->id),
            'recent_grades' => $this->getStudentRecentGrades($student->id)
        ];
    }

    /**
     * Get Parent statistics
     */
    private function getParentStats(User $user): array
    {
        $children = Student::where('parent_id', $user->id)->get();
        
        return [
            'total_children' => $children->count(),
            'children_details' => $children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->user->full_name,
                    'class' => $child->class?->full_name,
                    'attendance_percentage' => $this->getStudentAttendancePercentage($child->id),
                    'fee_status' => $this->getStudentFeeStatus($child->id)
                ];
            }),
            'total_pending_fees' => $this->getTotalPendingFees($children->pluck('id')),
            'upcoming_events' => $this->getUpcomingEventsForChildren($children->pluck('id')),
            'recent_notifications' => $this->getParentNotifications($user->id)
        ];
    }

    /**
     * Get HR statistics
     */
    private function getHRStats(User $user): array
    {
        $schoolId = $user->school_id;
        
        return [
            'total_staff' => User::where('school_id', $schoolId)
                ->whereIn('role', ['Teacher', 'Admin', 'HR'])
                ->count(),
            'present_today' => $this->getStaffPresentToday($schoolId),
            'on_leave' => $this->getStaffOnLeave($schoolId),
            'pending_reviews' => $this->getPendingStaffReviews($schoolId),
            'new_applications' => $this->getNewJobApplications($schoolId),
            'upcoming_birthdays' => $this->getUpcomingBirthdays($schoolId),
            'contract_renewals' => $this->getUpcomingContractRenewals($schoolId)
        ];
    }

    /**
     * Get quick stats for widgets
     */
    private function getQuickStats(User $user): array
    {
        return [
            'today_logins' => $this->getTodayLogins($user),
            'pending_tasks' => $this->getPendingTasks($user),
            'notifications' => $this->getNotificationsCount($user),
            'system_alerts' => $this->getSystemAlerts($user)
        ];
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(User $user): array
    {
        // This would typically come from an activities table
        // For now, return sample data
        return [
            [
                'id' => 1,
                'action' => 'Class Created',
                'description' => 'New class "Grade 10-A" was created',
                'timestamp' => now()->subHours(2)->toISOString(),
                'icon' => 'fas fa-plus-circle',
                'color' => 'success'
            ],
            [
                'id' => 2,
                'action' => 'Student Enrolled',
                'description' => '5 new students enrolled in Grade 9',
                'timestamp' => now()->subHours(4)->toISOString(),
                'icon' => 'fas fa-user-plus',
                'color' => 'info'
            ],
            [
                'id' => 3,
                'action' => 'Attendance Taken',
                'description' => 'Attendance recorded for Grade 8-B',
                'timestamp' => now()->subHours(6)->toISOString(),
                'icon' => 'fas fa-calendar-check',
                'color' => 'primary'
            ]
        ];
    }

    /**
     * Get notifications
     */
    private function getNotifications(User $user): array
    {
        // This would typically come from a notifications table
        return [
            [
                'id' => 1,
                'title' => 'Fee Payment Reminder',
                'message' => 'Monthly fees are due for Grade 10 students',
                'type' => 'warning',
                'read' => false,
                'created_at' => now()->subHours(1)->toISOString()
            ],
            [
                'id' => 2,
                'title' => 'Exam Schedule Released',
                'message' => 'Mid-term exam schedule has been published',
                'type' => 'info',
                'read' => false,
                'created_at' => now()->subHours(3)->toISOString()
            ]
        ];
    }

    /**
     * Get charts data
     */
    private function getChartsData(User $user): array
    {
        switch ($user->role) {
            case 'SuperAdmin':
                return $this->getSuperAdminChartsData();
            case 'Admin':
                return $this->getAdminChartsData($user);
            case 'Teacher':
                return $this->getTeacherChartsData($user);
            default:
                return [];
        }
    }

    /**
     * Get upcoming events
     */
    private function getUpcomingEvents(User $user): array
    {
        // This would typically come from an events table
        return [
            [
                'id' => 1,
                'title' => 'Parent-Teacher Meeting',
                'date' => now()->addDays(3)->format('Y-m-d'),
                'time' => '10:00 AM',
                'type' => 'meeting'
            ],
            [
                'id' => 2,
                'title' => 'Annual Sports Day',
                'date' => now()->addDays(7)->format('Y-m-d'),
                'time' => '9:00 AM',
                'type' => 'event'
            ]
        ];
    }

    /**
     * Get user permissions based on role
     */
    public function getUserPermissions(string $role): array
    {
        $permissions = [
            'SuperAdmin' => array_keys(\App\Modules\SuperAdmin\Models\Permission::getSuperAdminPermissions()),
            'Admin' => [
                'dashboard.view', 'users.manage', 'students.manage', 'teachers.manage',
                'classes.manage', 'subjects.manage', 'fees.manage', 'exams.manage',
                'attendance.manage', 'library.manage', 'transport.manage', 'idcards.manage',
                'hr.manage', 'reports.view'
            ],
            'Teacher' => [
                'dashboard.view', 'students.view', 'classes.view', 'subjects.view',
                'attendance.manage', 'exams.view', 'library.view', 'reports.view'
            ],
            'Student' => [
                'dashboard.view', 'classes.view', 'subjects.view', 'fees.view',
                'exams.view', 'attendance.view', 'library.view'
            ],
            'Parent' => [
                'dashboard.view', 'students.view', 'classes.view', 'subjects.view',
                'fees.view', 'exams.view', 'attendance.view'
            ],
            'HR' => [
                'dashboard.view', 'users.manage', 'teachers.manage', 'hr.manage',
                'reports.view'
            ]
        ];

        return $permissions[$role] ?? ['dashboard.view'];
    }

    /**
     * Get menu items based on role
     */
    public function getMenuItemsByRole(string $role): array
    {
        $menuItems = [
            'SuperAdmin' => [
                // Dashboard
                ['name' => 'Dashboard', 'route' => '/superadmin/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                
                // Tenant Management
                ['name' => 'School Management', 'route' => '/superadmin/schools', 'icon' => 'fas fa-school', 'permission' => 'tenants.view'],
                
                // User Management
                ['name' => 'User Management', 'route' => '/superadmin/users', 'icon' => 'fas fa-users', 'permission' => 'users.view_all'],
                
                // Role & Permission Control
                ['name' => 'Roles & Permissions', 'route' => '/superadmin/roles', 'icon' => 'fas fa-user-shield', 'permission' => 'roles.define'],
                
                // System Configuration
                ['name' => 'System Settings', 'route' => '/superadmin/system', 'icon' => 'fas fa-cogs', 'permission' => 'system.global_settings'],
                
                // Monitoring & Reporting
                ['name' => 'Analytics & Reports', 'route' => '/superadmin/reports', 'icon' => 'fas fa-chart-bar', 'permission' => 'reports.cross_tenant'],
                ['name' => 'Activity Logs', 'route' => '/superadmin/logs', 'icon' => 'fas fa-clipboard-list', 'permission' => 'logs.activity'],
                
                // Data & Security
                ['name' => 'Backup & Security', 'route' => '/superadmin/security', 'icon' => 'fas fa-shield-alt', 'permission' => 'data.backup'],
                ['name' => 'Integrations', 'route' => '/superadmin/integrations', 'icon' => 'fas fa-plug', 'permission' => 'integrations.manage'],
                
                // Communication Control
                ['name' => 'Communications', 'route' => '/superadmin/communications', 'icon' => 'fas fa-bullhorn', 'permission' => 'communication.announcements'],
                
                // Billing & Subscription
                ['name' => 'Billing & Subscriptions', 'route' => '/superadmin/billing', 'icon' => 'fas fa-credit-card', 'permission' => 'billing.plans'],
                
                // Module Management
                ['name' => 'Module Management', 'route' => '/superadmin/modules', 'icon' => 'fas fa-cubes', 'permission' => 'modules.enable'],
                
                // Support & Maintenance
                ['name' => 'Support & Maintenance', 'route' => '/superadmin/support', 'icon' => 'fas fa-tools', 'permission' => 'support.tickets'],
            ],
            'Admin' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'Students', 'route' => '/students', 'icon' => 'fas fa-user-graduate', 'permission' => 'students.manage'],
                ['name' => 'Teachers', 'route' => '/teachers', 'icon' => 'fas fa-chalkboard-teacher', 'permission' => 'teachers.manage'],
                ['name' => 'Classes', 'route' => '/classes', 'icon' => 'fas fa-door-open', 'permission' => 'classes.manage'],
                ['name' => 'Subjects', 'route' => '/subjects', 'icon' => 'fas fa-book', 'permission' => 'subjects.manage'],
                ['name' => 'Attendance', 'route' => '/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.manage'],
                ['name' => 'Fees', 'route' => '/fees', 'icon' => 'fas fa-dollar-sign', 'permission' => 'fees.manage'],
                ['name' => 'Exams', 'route' => '/exams', 'icon' => 'fas fa-clipboard-list', 'permission' => 'exams.manage'],
                ['name' => 'Reports', 'route' => '/reports', 'icon' => 'fas fa-chart-bar', 'permission' => 'reports.view'],
            ],
            'Teacher' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'My Students', 'route' => '/students', 'icon' => 'fas fa-user-graduate', 'permission' => 'students.view'],
                ['name' => 'My Classes', 'route' => '/classes', 'icon' => 'fas fa-door-open', 'permission' => 'classes.view'],
                ['name' => 'Attendance', 'route' => '/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.manage'],
                ['name' => 'Exams', 'route' => '/exams', 'icon' => 'fas fa-clipboard-list', 'permission' => 'exams.view'],
                ['name' => 'Library', 'route' => '/library', 'icon' => 'fas fa-book-open', 'permission' => 'library.view'],
            ],
            'Student' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'My Classes', 'route' => '/classes', 'icon' => 'fas fa-door-open', 'permission' => 'classes.view'],
                ['name' => 'My Subjects', 'route' => '/subjects', 'icon' => 'fas fa-book', 'permission' => 'subjects.view'],
                ['name' => 'My Attendance', 'route' => '/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.view'],
                ['name' => 'My Exams', 'route' => '/exams', 'icon' => 'fas fa-clipboard-list', 'permission' => 'exams.view'],
                ['name' => 'My Fees', 'route' => '/fees', 'icon' => 'fas fa-dollar-sign', 'permission' => 'fees.view'],
                ['name' => 'Library', 'route' => '/library', 'icon' => 'fas fa-book-open', 'permission' => 'library.view'],
            ],
            'Parent' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'My Children', 'route' => '/students', 'icon' => 'fas fa-user-graduate', 'permission' => 'students.view'],
                ['name' => 'Attendance', 'route' => '/attendance', 'icon' => 'fas fa-calendar-check', 'permission' => 'attendance.view'],
                ['name' => 'Exams', 'route' => '/exams', 'icon' => 'fas fa-clipboard-list', 'permission' => 'exams.view'],
                ['name' => 'Fees', 'route' => '/fees', 'icon' => 'fas fa-dollar-sign', 'permission' => 'fees.view'],
            ],
            'HR' => [
                ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view'],
                ['name' => 'Staff', 'route' => '/hr', 'icon' => 'fas fa-users', 'permission' => 'hr.manage'],
                ['name' => 'Teachers', 'route' => '/teachers', 'icon' => 'fas fa-chalkboard-teacher', 'permission' => 'teachers.manage'],
                ['name' => 'Reports', 'route' => '/reports', 'icon' => 'fas fa-chart-bar', 'permission' => 'reports.view'],
            ]
        ];

        return $menuItems[$role] ?? [
            ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'permission' => 'dashboard.view']
        ];
    }

    // Helper methods for statistics (simplified implementations)
    private function getSystemHealth(): float { return 99.9; }
    private function getMonthlyGrowth(): float { return 12.5; }
    private function getSchoolAttendanceRate(int $schoolId): float { return 94.2; }
    private function getFeeCollectionRate(int $schoolId): float { return 87.5; }
    private function getUpcomingExamsCount(int $schoolId): int { return 3; }
    private function getPendingAdmissionsCount(int $schoolId): int { return 12; }
    private function getAttendanceTakenToday(int $teacherId): int { return 5; }
    private function getPendingGrades(int $teacherId): int { return 8; }
    private function getUpcomingClasses(int $teacherId): int { return 3; }
    private function getTeacherClassesAttendance(int $teacherId): float { return 92.1; }
    private function getTeacherRecentActivities(int $teacherId): array { return []; }
    private function getStudentAttendancePercentage(int $studentId): float { return 95.5; }
    private function getStudentGPA(int $studentId): float { return 3.8; }
    private function getPendingAssignments(int $studentId): int { return 4; }
    private function getStudentUpcomingExams(int $studentId): int { return 2; }
    private function getStudentFeeStatus(int $studentId): array { return ['status' => 'paid', 'amount' => 0]; }
    private function getStudentRecentGrades(int $studentId): array { return []; }
    private function getTotalPendingFees(array $studentIds): float { return 250.00; }
    private function getUpcomingEventsForChildren(array $studentIds): array { return []; }
    private function getParentNotifications(int $parentId): array { return []; }
    private function getStaffPresentToday(int $schoolId): int { return 23; }
    private function getStaffOnLeave(int $schoolId): int { return 2; }
    private function getPendingStaffReviews(int $schoolId): int { return 5; }
    private function getNewJobApplications(int $schoolId): int { return 8; }
    private function getUpcomingBirthdays(int $schoolId): array { return []; }
    private function getUpcomingContractRenewals(int $schoolId): array { return []; }
    private function getTodayLogins(User $user): int { return 45; }
    private function getPendingTasks(User $user): int { return 7; }
    private function getNotificationsCount(User $user): int { return 3; }
    private function getSystemAlerts(User $user): int { return 0; }
    private function getSuperAdminChartsData(): array { return []; }
    private function getAdminChartsData(User $user): array { return []; }
    private function getTeacherChartsData(User $user): array { return []; }
}