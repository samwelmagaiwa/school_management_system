<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models$1;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
        'is_system',
        'tenant_id',
        'permissions',
        'module_access',
        'is_active'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'module_access' => 'array'
    ];

    protected $dates = ['deleted_at'];

    // Default system roles
    const DEFAULT_ROLES = [
        'SuperAdmin' => [
            'name' => 'Super Administrator',
            'description' => 'Full system access with tenant management capabilities',
            'is_system' => true,
            'permissions' => ['*'], // All permissions
            'module_access' => ['*'] // All modules
        ],
        'Admin' => [
            'name' => 'School Administrator',
            'description' => 'Full access to school management features',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'students.manage',
                'teachers.manage',
                'classes.manage',
                'subjects.manage',
                'attendance.manage',
                'exams.manage',
                'fees.manage',
                'reports.view',
                'settings.manage',
                'users.manage',
                'library.manage',
                'transport.manage',
                'hr.manage',
                'idcard.manage'
            ],
            'module_access' => [
                'dashboard', 'students', 'teachers', 'classes', 'subjects',
                'attendance', 'exams', 'fees', 'reports', 'settings',
                'users', 'library', 'transport', 'hr', 'idcard'
            ]
        ],
        'Teacher' => [
            'name' => 'Teacher',
            'description' => 'Access to teaching and student management features',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'students.view',
                'students.attendance',
                'classes.view',
                'subjects.view',
                'attendance.manage',
                'exams.manage',
                'reports.view',
                'profile.manage'
            ],
            'module_access' => [
                'dashboard', 'students', 'classes', 'subjects',
                'attendance', 'exams', 'reports'
            ]
        ],
        'Student' => [
            'name' => 'Student',
            'description' => 'Access to student portal features',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'profile.view',
                'attendance.view',
                'exams.view',
                'results.view',
                'library.view',
                'transport.view'
            ],
            'module_access' => [
                'dashboard', 'attendance', 'exams', 'library', 'transport'
            ]
        ],
        'Parent' => [
            'name' => 'Parent/Guardian',
            'description' => 'Access to monitor children\'s academic progress',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'children.view',
                'attendance.view',
                'exams.view',
                'results.view',
                'fees.view',
                'communication.view'
            ],
            'module_access' => [
                'dashboard', 'students', 'attendance', 'exams', 'fees'
            ]
        ],
        'Accountant' => [
            'name' => 'Accountant',
            'description' => 'Access to financial and fee management features',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'fees.manage',
                'reports.financial',
                'students.view',
                'billing.manage',
                'invoices.manage'
            ],
            'module_access' => [
                'dashboard', 'fees', 'reports', 'students'
            ]
        ]
    ];

    // Available modules in the system
    const AVAILABLE_MODULES = [
        'dashboard' => 'Dashboard',
        'students' => 'Student Management',
        'teachers' => 'Teacher Management',
        'classes' => 'Class Management',
        'subjects' => 'Subject Management',
        'attendance' => 'Attendance Management',
        'exams' => 'Exam Management',
        'fees' => 'Fee Management',
        'reports' => 'Reports & Analytics',
        'settings' => 'System Settings',
        'users' => 'User Management',
        'library' => 'Library Management',
        'transport' => 'Transport Management',
        'hr' => 'Human Resources',
        'idcard' => 'ID Card Management',
        'communication' => 'Communication'
    ];

    // Available permissions grouped by module
    const MODULE_PERMISSIONS = [
        'dashboard' => [
            'dashboard.view' => 'View Dashboard'
        ],
        'students' => [
            'students.view' => 'View Students',
            'students.create' => 'Create Students',
            'students.edit' => 'Edit Students',
            'students.delete' => 'Delete Students',
            'students.manage' => 'Full Student Management',
            'students.attendance' => 'Manage Student Attendance',
            'students.results' => 'Manage Student Results'
        ],
        'teachers' => [
            'teachers.view' => 'View Teachers',
            'teachers.create' => 'Create Teachers',
            'teachers.edit' => 'Edit Teachers',
            'teachers.delete' => 'Delete Teachers',
            'teachers.manage' => 'Full Teacher Management'
        ],
        'classes' => [
            'classes.view' => 'View Classes',
            'classes.create' => 'Create Classes',
            'classes.edit' => 'Edit Classes',
            'classes.delete' => 'Delete Classes',
            'classes.manage' => 'Full Class Management'
        ],
        'subjects' => [
            'subjects.view' => 'View Subjects',
            'subjects.create' => 'Create Subjects',
            'subjects.edit' => 'Edit Subjects',
            'subjects.delete' => 'Delete Subjects',
            'subjects.manage' => 'Full Subject Management'
        ],
        'attendance' => [
            'attendance.view' => 'View Attendance',
            'attendance.mark' => 'Mark Attendance',
            'attendance.edit' => 'Edit Attendance',
            'attendance.manage' => 'Full Attendance Management'
        ],
        'exams' => [
            'exams.view' => 'View Exams',
            'exams.create' => 'Create Exams',
            'exams.edit' => 'Edit Exams',
            'exams.delete' => 'Delete Exams',
            'exams.manage' => 'Full Exam Management',
            'results.view' => 'View Results',
            'results.manage' => 'Manage Results'
        ],
        'fees' => [
            'fees.view' => 'View Fees',
            'fees.create' => 'Create Fee Structures',
            'fees.edit' => 'Edit Fee Structures',
            'fees.delete' => 'Delete Fee Structures',
            'fees.manage' => 'Full Fee Management',
            'fees.collect' => 'Collect Fees',
            'invoices.manage' => 'Manage Invoices'
        ],
        'reports' => [
            'reports.view' => 'View Reports',
            'reports.financial' => 'View Financial Reports',
            'reports.academic' => 'View Academic Reports',
            'reports.attendance' => 'View Attendance Reports',
            'reports.export' => 'Export Reports'
        ],
        'settings' => [
            'settings.view' => 'View Settings',
            'settings.manage' => 'Manage Settings',
            'settings.academic' => 'Manage Academic Settings',
            'settings.system' => 'Manage System Settings'
        ],
        'users' => [
            'users.view' => 'View Users',
            'users.create' => 'Create Users',
            'users.edit' => 'Edit Users',
            'users.delete' => 'Delete Users',
            'users.manage' => 'Full User Management',
            'roles.assign' => 'Assign Roles'
        ],
        'library' => [
            'library.view' => 'View Library',
            'library.manage' => 'Manage Library',
            'books.manage' => 'Manage Books',
            'library.issue' => 'Issue Books'
        ],
        'transport' => [
            'transport.view' => 'View Transport',
            'transport.manage' => 'Manage Transport',
            'routes.manage' => 'Manage Routes',
            'vehicles.manage' => 'Manage Vehicles'
        ],
        'hr' => [
            'hr.view' => 'View HR',
            'hr.manage' => 'Manage HR',
            'employees.manage' => 'Manage Employees',
            'payroll.manage' => 'Manage Payroll'
        ],
        'idcard' => [
            'idcard.view' => 'View ID Cards',
            'idcard.manage' => 'Manage ID Cards',
            'idcard.generate' => 'Generate ID Cards'
        ],
        'communication' => [
            'communication.view' => 'View Communications',
            'communication.send' => 'Send Communications',
            'announcements.manage' => 'Manage Announcements'
        ]
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rolePermissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where(function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhere('is_system', true);
        });
    }

    // Helper methods
    public function hasPermission($permission)
    {
        $permissions = $this->permissions ?? [];
        
        // SuperAdmin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }
        
        return in_array($permission, $permissions);
    }

    public function hasModuleAccess($module)
    {
        $moduleAccess = $this->module_access ?? [];
        
        // SuperAdmin has access to all modules
        if (in_array('*', $moduleAccess)) {
            return true;
        }
        
        return in_array($module, $moduleAccess);
    }

    public function addPermission($permission)
    {
        $permissions = $this->permissions ?? [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }
        
        return $this;
    }

    public function removePermission($permission)
    {
        $permissions = $this->permissions ?? [];
        
        if (($key = array_search($permission, $permissions)) !== false) {
            unset($permissions[$key]);
            $this->permissions = array_values($permissions);
            $this->save();
        }
        
        return $this;
    }

    public function addModuleAccess($module)
    {
        $moduleAccess = $this->module_access ?? [];
        
        if (!in_array($module, $moduleAccess)) {
            $moduleAccess[] = $module;
            $this->module_access = $moduleAccess;
            $this->save();
        }
        
        return $this;
    }

    public function removeModuleAccess($module)
    {
        $moduleAccess = $this->module_access ?? [];
        
        if (($key = array_search($module, $moduleAccess)) !== false) {
            unset($moduleAccess[$key]);
            $this->module_access = array_values($moduleAccess);
            $this->save();
        }
        
        return $this;
    }

    // Static methods
    public static function getDefaultRoles()
    {
        return self::DEFAULT_ROLES;
    }

    public static function getAvailableModules()
    {
        return self::AVAILABLE_MODULES;
    }

    public static function getModulePermissions($module = null)
    {
        if ($module) {
            return self::MODULE_PERMISSIONS[$module] ?? [];
        }
        
        return self::MODULE_PERMISSIONS;
    }

    public static function getAllPermissions()
    {
        $allPermissions = [];
        
        foreach (self::MODULE_PERMISSIONS as $module => $permissions) {
            $allPermissions = array_merge($allPermissions, $permissions);
        }
        
        return $allPermissions;
    }

    public static function createDefaultRoles($tenantId = null)
    {
        $roles = [];
        
        foreach (self::DEFAULT_ROLES as $slug => $roleData) {
            $role = self::updateOrCreate(
                [
                    'slug' => $slug,
                    'tenant_id' => $tenantId
                ],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_default' => true,
                    'is_system' => $roleData['is_system'],
                    'permissions' => $roleData['permissions'],
                    'module_access' => $roleData['module_access'],
                    'is_active' => true
                ]
            );
            
            $roles[] = $role;
        }
        
        return $roles;
    }

    public static function getPermissionsForRole($roleSlug)
    {
        $defaultRoles = self::DEFAULT_ROLES;
        
        if (isset($defaultRoles[$roleSlug])) {
            return $defaultRoles[$roleSlug]['permissions'];
        }
        
        return [];
    }

    public static function getModuleAccessForRole($roleSlug)
    {
        $defaultRoles = self::DEFAULT_ROLES;
        
        if (isset($defaultRoles[$roleSlug])) {
            return $defaultRoles[$roleSlug]['module_access'];
        }
        
        return [];
    }

    // Check if role can be deleted (system roles cannot be deleted)
    public function canBeDeleted()
    {
        return !$this->is_system;
    }

    // Check if role can be modified
    public function canBeModified()
    {
        return !$this->is_system || auth()->user()?->isSuperAdmin();
    }

    // Get role statistics
    public function getStatistics()
    {
        return [
            'total_users' => $this->users()->count(),
            'active_users' => $this->users()->active()->count(),
            'permissions_count' => count($this->permissions ?? []),
            'modules_count' => count($this->module_access ?? [])
        ];
    }
}