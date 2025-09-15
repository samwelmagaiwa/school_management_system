<?php

namespace App\Services;

class MenuService
{
    /**
     * Get menu items based on user role
     */
    public function getMenuItemsByRole(string $role): array
    {
        switch ($role) {
            case 'SuperAdmin':
                return $this->getSuperAdminMenuItems();
            case 'Admin':
                return $this->getAdminMenuItems();
            case 'Teacher':
                return $this->getTeacherMenuItems();
            case 'Student':
                return $this->getStudentMenuItems();
            case 'Parent':
                return $this->getParentMenuItems();
            case 'HR':
                return $this->getHRMenuItems();
            case 'Accountant':
                return $this->getAccountantMenuItems();
            default:
                return [];
        }
    }

    /**
     * Get permissions based on user role using the comprehensive permissions system
     */
    public function getPermissionsByRole(string $role): array
    {
        // Use the comprehensive ProjectPermissionService
        $permissionService = app(\App\Services\ProjectPermissionService::class);
        $permissions = $permissionService->getPermissionsForRole($role);
        
        // If using comprehensive system, return permission slugs
        if (!empty($permissions)) {
            return collect($permissions)->pluck('slug')->toArray();
        }
        
        // Fallback to legacy permissions for backward compatibility
        switch ($role) {
            case 'SuperAdmin':
                return ['*']; // Full access
            case 'Admin':
                return [
                    'dashboard.access', 'dashboard.view_stats',
                    'student.manage', 'teacher.manage', 'class.manage', 'subject.manage',
                    'attendance.manage', 'exam.manage', 'fee.manage',
                    'report.academic', 'report.financial', 'report.attendance',
                    'user.create', 'user.edit', 'user.view',
                    'school.configure', 'school.manage_settings'
                ];
            case 'Teacher':
                return [
                    'dashboard.access',
                    'student.view', 'student.view_grades', 'student.manage_attendance',
                    'class.view', 'subject.view',
                    'attendance.mark', 'attendance.view', 'attendance.bulk_mark',
                    'exam.view', 'exam.create', 'exam.grade',
                    'library.view', 'library.issue_books'
                ];
            case 'Student':
                return [
                    'dashboard.access',
                    'student.view', 'student.view_grades',
                    'attendance.view', 'exam.view', 'fee.view',
                    'library.view', 'transport.view'
                ];
            case 'Parent':
                return [
                    'dashboard.access',
                    'student.view', 'student.view_grades',
                    'attendance.view', 'exam.view', 'fee.view', 'fee.collect',
                    'transport.view'
                ];
            case 'HR':
                return [
                    'dashboard.access', 'dashboard.view_stats',
                    'user.view', 'user.create', 'user.edit',
                    'teacher.view', 'teacher.create', 'teacher.edit',
                    'hr.manage_payroll', 'hr.manage_leave', 'hr.performance_review'
                ];
            case 'Accountant':
                return [
                    'dashboard.access', 'dashboard.view_stats',
                    'student.view', 'fee.manage', 'fee.collect', 'fee.generate_invoices',
                    'report.financial', 'report.export'
                ];
            default:
                return [];
        }
    }

    private function getSuperAdminMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'route' => '/superadmin/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'schools',
                'name' => 'Schools',
                'icon' => 'fas fa-school',
                'children' => [
                    ['name' => 'All Schools', 'route' => '/superadmin/schools', 'icon' => 'fas fa-list'],
                    ['name' => 'Create School', 'route' => '/superadmin/schools/create', 'icon' => 'fas fa-plus'],
                ]
            ],
            [
                'id' => 'users',
                'name' => 'Users',
                'icon' => 'fas fa-users',
                'children' => [
                    ['name' => 'All Users', 'route' => '/superadmin/users', 'icon' => 'fas fa-list'],
                    ['name' => 'Create User', 'route' => '/superadmin/users/create', 'icon' => 'fas fa-user-plus'],
                ]
            ],
            [
                'id' => 'students',
                'name' => 'Students',
                'icon' => 'fas fa-user-graduate',
                'children' => [
                    ['name' => 'All Students', 'route' => '/superadmin/students', 'icon' => 'fas fa-list'],
                    ['name' => 'Admissions', 'route' => '/superadmin/students/admissions', 'icon' => 'fas fa-clipboard-check'],
                ]
            ],
            [
                'id' => 'teachers',
                'name' => 'Teachers',
                'icon' => 'fas fa-chalkboard-teacher',
                'children' => [
                    ['name' => 'All Teachers', 'route' => '/superadmin/teachers', 'icon' => 'fas fa-list'],
                    ['name' => 'Performance Reports', 'route' => '/superadmin/teachers/performance', 'icon' => 'fas fa-chart-line'],
                ]
            ],
            [
                'id' => 'academic',
                'name' => 'Academic',
                'icon' => 'fas fa-graduation-cap',
                'children' => [
                    ['name' => 'Classes', 'route' => '/superadmin/classes', 'icon' => 'fas fa-chalkboard'],
                    ['name' => 'Subjects', 'route' => '/superadmin/subjects', 'icon' => 'fas fa-book'],
                    ['name' => 'Exams', 'route' => '/superadmin/exams', 'icon' => 'fas fa-clipboard-list'],
                ]
            ],
            [
                'id' => 'reports',
                'name' => 'Reports',
                'icon' => 'fas fa-chart-pie',
                'children' => [
                    ['name' => 'School Reports', 'route' => '/superadmin/reports/schools', 'icon' => 'fas fa-school'],
                    ['name' => 'Performance Reports', 'route' => '/superadmin/reports/performance', 'icon' => 'fas fa-chart-line'],
                ]
            ],
        ];
    }

    private function getAdminMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'route' => '/admin/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'students',
                'name' => 'Students',
                'icon' => 'fas fa-user-graduate',
                'children' => [
                    ['name' => 'All Students', 'route' => '/admin/students', 'icon' => 'fas fa-list'],
                    ['name' => 'Add Student', 'route' => '/admin/students/create', 'icon' => 'fas fa-plus'],
                ]
            ],
            [
                'id' => 'teachers',
                'name' => 'Teachers',
                'icon' => 'fas fa-chalkboard-teacher',
                'children' => [
                    ['name' => 'All Teachers', 'route' => '/admin/teachers', 'icon' => 'fas fa-list'],
                    ['name' => 'Add Teacher', 'route' => '/admin/teachers/create', 'icon' => 'fas fa-plus'],
                ]
            ],
            [
                'id' => 'classes',
                'name' => 'Classes',
                'icon' => 'fas fa-chalkboard',
                'children' => [
                    ['name' => 'Manage Classes', 'route' => '/admin/classes', 'icon' => 'fas fa-cogs'],
                    ['name' => 'Class Assignments', 'route' => '/admin/classes/assignments', 'icon' => 'fas fa-tasks'],
                ]
            ],
            [
                'id' => 'reports',
                'name' => 'Reports',
                'route' => '/admin/reports',
                'icon' => 'fas fa-chart-bar'
            ],
        ];
    }

    private function getTeacherMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'route' => '/teacher/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'classes',
                'name' => 'My Classes',
                'route' => '/teacher/classes',
                'icon' => 'fas fa-chalkboard'
            ],
            [
                'id' => 'students',
                'name' => 'Students',
                'route' => '/teacher/students',
                'icon' => 'fas fa-user-graduate'
            ],
            [
                'id' => 'attendance',
                'name' => 'Attendance',
                'route' => '/teacher/attendance',
                'icon' => 'fas fa-calendar-check'
            ],
            [
                'id' => 'grades',
                'name' => 'Grades',
                'route' => '/teacher/grades',
                'icon' => 'fas fa-graduation-cap'
            ],
        ];
    }

    private function getStudentMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'route' => '/student/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'profile',
                'name' => 'My Profile',
                'route' => '/student/profile',
                'icon' => 'fas fa-user'
            ],
            [
                'id' => 'grades',
                'name' => 'My Grades',
                'route' => '/student/grades',
                'icon' => 'fas fa-star'
            ],
            [
                'id' => 'attendance',
                'name' => 'Attendance',
                'route' => '/student/attendance',
                'icon' => 'fas fa-calendar-check'
            ],
            [
                'id' => 'timetable',
                'name' => 'Timetable',
                'route' => '/student/timetable',
                'icon' => 'fas fa-calendar-alt'
            ],
        ];
    }

    private function getParentMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'route' => '/parent/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'children',
                'name' => 'My Children',
                'route' => '/parent/children',
                'icon' => 'fas fa-child'
            ],
            [
                'id' => 'grades',
                'name' => 'Grades & Reports',
                'route' => '/parent/grades',
                'icon' => 'fas fa-chart-line'
            ],
            [
                'id' => 'attendance',
                'name' => 'Attendance',
                'route' => '/parent/attendance',
                'icon' => 'fas fa-calendar-check'
            ],
        ];
    }

    private function getHRMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'route' => '/hr/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'staff',
                'name' => 'Staff Management',
                'icon' => 'fas fa-users',
                'children' => [
                    ['name' => 'All Staff', 'route' => '/hr/staff', 'icon' => 'fas fa-list'],
                    ['name' => 'Add Staff', 'route' => '/hr/staff/create', 'icon' => 'fas fa-plus'],
                ]
            ],
            [
                'id' => 'payroll',
                'name' => 'Payroll',
                'route' => '/hr/payroll',
                'icon' => 'fas fa-money-bill-wave'
            ],
            [
                'id' => 'leaves',
                'name' => 'Leave Management',
                'route' => '/hr/leaves',
                'icon' => 'fas fa-calendar-times'
            ],
        ];
    }

    private function getAccountantMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'route' => '/accountant/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'fees',
                'name' => 'Fee Management',
                'icon' => 'fas fa-dollar-sign',
                'children' => [
                    ['name' => 'Fee Collection', 'route' => '/accountant/fees/collection', 'icon' => 'fas fa-cash-register'],
                    ['name' => 'Fee Reports', 'route' => '/accountant/fees/reports', 'icon' => 'fas fa-chart-bar'],
                ]
            ],
            [
                'id' => 'payments',
                'name' => 'Payments',
                'route' => '/accountant/payments',
                'icon' => 'fas fa-credit-card'
            ],
            [
                'id' => 'invoices',
                'name' => 'Invoices',
                'route' => '/accountant/invoices',
                'icon' => 'fas fa-file-invoice'
            ],
        ];
    }
}
