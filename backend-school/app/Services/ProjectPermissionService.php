<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\File;

class ProjectPermissionService
{
    /**
     * Analyze project controllers to extract modules and permissions
     */
    public function analyzeProjectStructure(): array
    {
        $controllers = $this->getControllers();
        $models = $this->getModels();
        
        return [
            'controllers' => $controllers,
            'models' => $models,
            'permissions' => $this->generatePermissionsFromStructure($controllers, $models)
        ];
    }

    /**
     * Get all controllers from the project
     */
    private function getControllers(): array
    {
        $controllers = [];
        $controllerPaths = [
            'app/Http/Controllers/Api/V1',
            'app/Http/Controllers'
        ];

        foreach ($controllerPaths as $path) {
            if (File::exists($path)) {
                $files = File::allFiles($path);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        $className = str_replace('.php', '', $file->getFilename());
                        if (str_contains($className, 'Controller') && $className !== 'Controller') {
                            $module = str_replace('Controller', '', $className);
                            $controllers[$module] = [
                                'name' => $className,
                                'path' => $file->getPathname(),
                                'module' => strtolower($module)
                            ];
                        }
                    }
                }
            }
        }

        return $controllers;
    }

    /**
     * Get all models from the project
     */
    private function getModels(): array
    {
        $models = [];
        $modelPath = 'app/Models';

        if (File::exists($modelPath)) {
            $files = File::allFiles($modelPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $className = str_replace('.php', '', $file->getFilename());
                    $models[strtolower($className)] = [
                        'name' => $className,
                        'path' => $file->getPathname()
                    ];
                }
            }
        }

        return $models;
    }

    /**
     * Generate comprehensive permissions based on project structure
     */
    public function generatePermissionsFromStructure(array $controllers, array $models): array
    {
        $permissions = [];

        // Define standard CRUD operations
        $crudOperations = ['view', 'create', 'edit', 'update', 'delete', 'manage'];
        
        // Define additional operations per module
        $moduleSpecificOperations = [
            'dashboard' => ['access', 'view_stats', 'export_data'],
            'auth' => ['login', 'logout', 'register', 'reset_password'],
            'user' => ['activate', 'deactivate', 'assign_role', 'reset_password', 'change_status'],
            'superadmin' => ['system_access', 'global_manage', 'tenant_manage', 'billing_access'],
            'student' => ['promote', 'transfer', 'graduate', 'assign_class', 'view_grades', 'manage_attendance'],
            'teacher' => ['assign_classes', 'manage_grades', 'view_performance', 'assign_subjects'],
            'school' => ['configure', 'manage_settings', 'view_stats', 'activate', 'deactivate'],
            'class' => ['assign_students', 'assign_teachers', 'schedule_manage'],
            'subject' => ['assign_teachers', 'manage_curriculum', 'assign_classes'],
            'attendance' => ['mark', 'bulk_mark', 'generate_reports', 'export'],
            'exam' => ['schedule', 'grade', 'publish_results', 'generate_reports'],
            'fee' => ['collect', 'generate_invoices', 'payment_manage', 'discount_apply'],
            'library' => ['issue_books', 'return_books', 'manage_inventory', 'generate_reports'],
            'transport' => ['manage_routes', 'assign_students', 'track_vehicles', 'manage_drivers'],
            'idcard' => ['generate', 'print', 'bulk_generate', 'design_templates'],
            'hr' => ['manage_payroll', 'manage_leave', 'performance_review', 'recruitment'],
            'report' => ['academic', 'financial', 'attendance', 'performance', 'export', 'schedule']
        ];

        // Core system modules (always present)
        $coreModules = [
            'dashboard' => 'Dashboard Management',
            'auth' => 'Authentication & Authorization',
            'user' => 'User Management',
            'role' => 'Role & Permission Management',
            'school' => 'School Management',
            'settings' => 'System Settings',
            'report' => 'Reports & Analytics'
        ];

        // Generate permissions for core modules
        foreach ($coreModules as $module => $description) {
            $permissions[$module] = [
                'module' => $module,
                'description' => $description,
                'permissions' => $this->generateModulePermissions($module, $crudOperations, $moduleSpecificOperations)
            ];
        }

        // Generate permissions based on discovered controllers
        foreach ($controllers as $controllerModule => $controllerData) {
            $module = strtolower($controllerModule);
            
            // Skip if already covered in core modules
            if (isset($coreModules[$module])) {
                continue;
            }

            $permissions[$module] = [
                'module' => $module,
                'description' => ucfirst($module) . ' Management',
                'permissions' => $this->generateModulePermissions($module, $crudOperations, $moduleSpecificOperations)
            ];
        }

        return $permissions;
    }

    /**
     * Generate permissions for a specific module
     */
    private function generateModulePermissions(string $module, array $crudOperations, array $moduleSpecificOperations): array
    {
        $permissions = [];

        // Add CRUD permissions
        foreach ($crudOperations as $operation) {
            $permissionSlug = $module . '.' . $operation;
            $permissions[$permissionSlug] = [
                'name' => ucfirst($operation) . ' ' . ucfirst($module),
                'slug' => $permissionSlug,
                'description' => ucfirst($operation) . ' ' . $module . ' records',
                'category' => 'CRUD'
            ];
        }

        // Add module-specific permissions
        if (isset($moduleSpecificOperations[$module])) {
            foreach ($moduleSpecificOperations[$module] as $operation) {
                $permissionSlug = $module . '.' . $operation;
                $permissions[$permissionSlug] = [
                    'name' => ucfirst(str_replace('_', ' ', $operation)) . ' ' . ucfirst($module),
                    'slug' => $permissionSlug,
                    'description' => ucfirst(str_replace('_', ' ', $operation)) . ' for ' . $module,
                    'category' => 'Specific'
                ];
            }
        }

        return $permissions;
    }

    /**
     * Define role-based permission assignments
     */
    public function getRolePermissionMapping(): array
    {
        return [
            'SuperAdmin' => [
                'description' => 'Complete system access with all permissions',
                'permissions' => ['*'], // All permissions
                'modules' => ['*'] // All modules
            ],
            'Admin' => [
                'description' => 'School-level administration with most permissions',
                'permissions' => [
                    // Dashboard
                    'dashboard.*',
                    // User Management
                    'user.view', 'user.create', 'user.edit', 'user.activate', 'user.deactivate', 'user.assign_role',
                    // School Management
                    'school.configure', 'school.manage_settings', 'school.view_stats',
                    // Student Management
                    'student.*',
                    // Teacher Management
                    'teacher.*',
                    // Class Management
                    'class.*',
                    // Subject Management
                    'subject.*',
                    // Attendance Management
                    'attendance.*',
                    // Exam Management
                    'exam.*',
                    // Fee Management
                    'fee.*',
                    // Library Management
                    'library.*',
                    // Transport Management
                    'transport.*',
                    // HR Management
                    'hr.*',
                    // ID Card Management
                    'idcard.*',
                    // Reports
                    'report.academic', 'report.financial', 'report.attendance', 'report.performance', 'report.export'
                ],
                'modules' => [
                    'dashboard', 'user', 'school', 'student', 'teacher', 'class', 'subject',
                    'attendance', 'exam', 'fee', 'library', 'transport', 'hr', 'idcard', 'report'
                ]
            ],
            'Teacher' => [
                'description' => 'Teaching staff with student and academic management permissions',
                'permissions' => [
                    // Dashboard
                    'dashboard.access', 'dashboard.view_stats',
                    // Student Management (limited)
                    'student.view', 'student.view_grades', 'student.manage_attendance',
                    // Class Management
                    'class.view', 'class.schedule_manage',
                    // Subject Management
                    'subject.view', 'subject.manage_curriculum',
                    // Attendance Management
                    'attendance.view', 'attendance.mark', 'attendance.bulk_mark', 'attendance.generate_reports',
                    // Exam Management
                    'exam.view', 'exam.create', 'exam.grade', 'exam.generate_reports',
                    // Library (limited)
                    'library.view', 'library.issue_books', 'library.return_books',
                    // Reports (academic only)
                    'report.academic', 'report.attendance', 'report.performance'
                ],
                'modules' => [
                    'dashboard', 'student', 'class', 'subject', 'attendance', 'exam', 'library', 'report'
                ]
            ],
            'Student' => [
                'description' => 'Student access to personal academic information',
                'permissions' => [
                    // Dashboard
                    'dashboard.access',
                    // Student (own records only)
                    'student.view', 'student.view_grades',
                    // Attendance (own records)
                    'attendance.view',
                    // Exam (own results)
                    'exam.view',
                    // Fee (own records)
                    'fee.view',
                    // Library (student services)
                    'library.view',
                    // Transport (own records)
                    'transport.view'
                ],
                'modules' => [
                    'dashboard', 'student', 'attendance', 'exam', 'fee', 'library', 'transport'
                ]
            ],
            'Parent' => [
                'description' => 'Parent access to children\'s academic information',
                'permissions' => [
                    // Dashboard
                    'dashboard.access',
                    // Student (children only)
                    'student.view', 'student.view_grades',
                    // Attendance (children)
                    'attendance.view',
                    // Exam (children results)
                    'exam.view',
                    // Fee (children records and payments)
                    'fee.view', 'fee.collect',
                    // Transport (children records)
                    'transport.view'
                ],
                'modules' => [
                    'dashboard', 'student', 'attendance', 'exam', 'fee', 'transport'
                ]
            ],
            'HR' => [
                'description' => 'Human Resources management access',
                'permissions' => [
                    // Dashboard
                    'dashboard.access', 'dashboard.view_stats',
                    // User Management (staff)
                    'user.view', 'user.create', 'user.edit',
                    // Teacher Management
                    'teacher.view', 'teacher.create', 'teacher.edit', 'teacher.view_performance',
                    // HR Management
                    'hr.*',
                    // Reports (HR related)
                    'report.performance', 'report.export'
                ],
                'modules' => [
                    'dashboard', 'user', 'teacher', 'hr', 'report'
                ]
            ],
            'Accountant' => [
                'description' => 'Financial management and fee collection access',
                'permissions' => [
                    // Dashboard
                    'dashboard.access', 'dashboard.view_stats',
                    // Student (for fee purposes)
                    'student.view',
                    // Fee Management
                    'fee.*',
                    // Reports (financial)
                    'report.financial', 'report.export'
                ],
                'modules' => [
                    'dashboard', 'student', 'fee', 'report'
                ]
            ]
        ];
    }

    /**
     * Create permissions in database
     */
    public function seedPermissions(): array
    {
        $analysis = $this->analyzeProjectStructure();
        $permissions = $analysis['permissions'];
        $createdPermissions = [];

        foreach ($permissions as $moduleData) {
            foreach ($moduleData['permissions'] as $permissionData) {
                $permission = Permission::updateOrCreate(
                    ['slug' => $permissionData['slug']],
                    [
                        'name' => $permissionData['name'],
                        'description' => $permissionData['description'],
                        'module' => $moduleData['module'],
                        'category' => $permissionData['category'] ?? 'General',
                        'is_active' => true
                    ]
                );
                $createdPermissions[] = $permission;
            }
        }

        return $createdPermissions;
    }

    /**
     * Create roles with permissions in database
     */
    public function seedRoles(): array
    {
        $roleMapping = $this->getRolePermissionMapping();
        $createdRoles = [];

        foreach ($roleMapping as $roleSlug => $roleData) {
            $role = Role::updateOrCreate(
                ['slug' => $roleSlug],
                [
                    'name' => $roleData['description'],
                    'description' => $roleData['description'],
                    'is_default' => true,
                    'is_system' => true,
                    'permissions' => $roleData['permissions'],
                    'module_access' => $roleData['modules'],
                    'is_active' => true
                ]
            );
            $createdRoles[] = $role;
        }

        return $createdRoles;
    }

    /**
     * Assign permissions to roles via pivot table
     */
    public function assignPermissionsToRoles(): void
    {
        $roleMapping = $this->getRolePermissionMapping();

        foreach ($roleMapping as $roleSlug => $roleData) {
            $role = Role::where('slug', $roleSlug)->first();
            
            if (!$role) {
                continue;
            }

            // Clear existing permissions
            $role->rolePermissions()->detach();

            // SuperAdmin gets all permissions
            if ($roleSlug === 'SuperAdmin') {
                $allPermissions = Permission::all();
                $role->rolePermissions()->attach($allPermissions->pluck('id')->toArray());
                continue;
            }

            // Other roles get specific permissions
            foreach ($roleData['permissions'] as $permissionPattern) {
                if (str_ends_with($permissionPattern, '*')) {
                    // Wildcard permission - get all permissions for module
                    $module = str_replace('.*', '', $permissionPattern);
                    $permissions = Permission::where('module', $module)->get();
                } else {
                    // Specific permission
                    $permissions = Permission::where('slug', $permissionPattern)->get();
                }

                if ($permissions->isNotEmpty()) {
                    $role->rolePermissions()->attach($permissions->pluck('id')->toArray());
                }
            }
        }
    }

    /**
     * Update existing users with proper roles
     */
    public function updateUserRoles(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Ensure role exists in new system
            $role = Role::where('slug', $user->role)->first();
            
            if (!$role) {
                // If role doesn't exist, assign default role based on current role string
                $defaultRoleMapping = [
                    'SuperAdmin' => 'SuperAdmin',
                    'Admin' => 'Admin',
                    'Teacher' => 'Teacher',
                    'Student' => 'Student',
                    'Parent' => 'Parent',
                    'HR' => 'HR',
                    'Accountant' => 'Accountant'
                ];

                $newRole = $defaultRoleMapping[$user->role] ?? 'Student';
                $user->update(['role' => $newRole]);
            }
        }
    }

    /**
     * Get permissions for a specific role
     */
    public function getPermissionsForRole(string $roleSlug): array
    {
        $role = Role::where('slug', $roleSlug)->first();
        
        if (!$role) {
            return [];
        }

        // If role has wildcard permissions, return all permissions
        if (in_array('*', $role->permissions ?? [])) {
            return Permission::all()->toArray();
        }

        // Get permissions from pivot table
        $permissions = $role->rolePermissions()->get();
        
        return $permissions->toArray();
    }

    /**
     * Check if user has specific permission
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        $role = Role::where('slug', $user->role)->first();
        
        if (!$role) {
            return false;
        }

        // SuperAdmin has all permissions
        if (in_array('*', $role->permissions ?? [])) {
            return true;
        }

        // Check if permission is directly assigned to role
        if (in_array($permission, $role->permissions ?? [])) {
            return true;
        }

        // Check wildcard permissions
        foreach ($role->permissions ?? [] as $rolePermission) {
            if (str_ends_with($rolePermission, '*')) {
                $module = str_replace('.*', '', $rolePermission);
                if (str_starts_with($permission, $module . '.')) {
                    return true;
                }
            }
        }

        // Check via pivot table
        return $role->rolePermissions()->where('slug', $permission)->exists();
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissionsByModule(): array
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $grouped = [];

        foreach ($permissions as $permission) {
            $grouped[$permission->module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Generate comprehensive system report
     */
    public function generateSystemReport(): array
    {
        $analysis = $this->analyzeProjectStructure();
        
        return [
            'project_analysis' => [
                'controllers_found' => count($analysis['controllers']),
                'models_found' => count($analysis['models']),
                'modules_identified' => count($analysis['permissions']),
                'total_permissions' => collect($analysis['permissions'])->sum(function ($module) {
                    return count($module['permissions']);
                })
            ],
            'database_state' => [
                'permissions_in_db' => Permission::count(),
                'roles_in_db' => Role::count(),
                'users_total' => User::count(),
                'active_users' => User::where('status', true)->count()
            ],
            'role_distribution' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->get()
                ->toArray(),
            'modules' => array_keys($analysis['permissions']),
            'role_permissions' => $this->getRolePermissionMapping()
        ];
    }
}
