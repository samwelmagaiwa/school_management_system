<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\SuperAdmin\Models\Role;
use App\Modules\SuperAdmin\Models\TenantPermission;
use Database\Factories\UserFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'role',
        'school_id',
        'profile_picture',
        'status',
        'email_verified_at',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'status' => 'boolean',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    // Define available roles
    const ROLES = [
        'SuperAdmin',
        'Admin',
        'Teacher',
        'Student',
        'Parent',
        'Accountant',
        'HR'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function teacher()
    {
        return $this->hasOne(\App\Modules\Teacher\Models\Teacher::class);
    }

    public function employee()
    {
        return $this->hasOne(\App\Modules\HR\Models\Employee::class);
    }

    // For parent users - children they are responsible for
    public function children()
    {
        return $this->hasMany(\App\Modules\Student\Models\Student::class, 'parent_id');
    }

    // Activity logs
    public function activityLogs()
    {
        return $this->hasMany(\App\Models\ActivityLog::class);
    }

    // Personal access tokens (from Sanctum)
    public function tokens()
    {
        return $this->morphMany(\Laravel\Sanctum\PersonalAccessToken::class, 'tokenable');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
        });
    }

    // Helper methods
    public function isSuperAdmin()
    {
        return $this->role === 'SuperAdmin';
    }

    public function isAdmin()
    {
        return $this->role === 'Admin';
    }

    public function isTeacher()
    {
        return $this->role === 'Teacher';
    }

    public function isStudent()
    {
        return $this->role === 'Student';
    }

    public function isParent()
    {
        return $this->role === 'Parent';
    }

    public function isAccountant()
    {
        return $this->role === 'Accountant';
    }

    public function isHR()
    {
        return $this->role === 'HR';
    }

    public function isActive()
    {
        return $this->status === true;
    }

    public function isInactive()
    {
        return $this->status === false;
    }

    // SuperAdmin specific methods
    public function hasFullAccess()
    {
        return $this->role === 'SuperAdmin';
    }

    public function canManageSchools()
    {
        return $this->role === 'SuperAdmin';
    }

    public function canManageAllUsers()
    {
        return $this->role === 'SuperAdmin';
    }

    public function canAccessSystemSettings()
    {
        return $this->role === 'SuperAdmin';
    }

    public function canViewAllReports()
    {
        return $this->role === 'SuperAdmin';
    }

    public function getAccessibleSchools()
    {
        if ($this->isSuperAdmin()) {
            return \App\Modules\School\Models\School::all();
        }
        
        return collect([$this->school])->filter();
    }

    // Role checking methods
    public function hasRole($role)
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        
        return $this->role === $role;
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }
        
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        
        return false;
    }

    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }
        
        if (is_array($roles)) {
            // For single role system, user can only have one role
            // So hasAllRoles only returns true if array has one role that matches
            return count($roles) === 1 && $this->role === $roles[0];
        }
        
        return false;
    }

    public function getRoles()
    {
        return [$this->role];
    }

    public function getRoleNames()
    {
        return [$this->role];
    }

    // Enhanced permission checking methods
    public function hasPermission($permission)
    {
        // SuperAdmin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Check tenant-specific permissions first
        if ($this->school_id) {
            $tenantPermission = TenantPermission::getForTenantAndRole($this->school_id, $this->role);
            if ($tenantPermission && $tenantPermission->hasPermission($permission)) {
                return true;
            }
        }
        
        // Fall back to default role permissions
        $defaultRole = Role::where('slug', $this->role)
                          ->where('is_system', true)
                          ->first();
        
        if ($defaultRole) {
            return $defaultRole->hasPermission($permission);
        }
        
        // Legacy fallback for backward compatibility
        return $this->hasLegacyPermission($permission);
    }
    
    /**
     * Legacy permission system for backward compatibility
     */
    private function hasLegacyPermission($permission)
    {
        $rolePermissions = [
            'SuperAdmin' => ['*'],
            'Admin' => [
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
            'Teacher' => [
                'dashboard.view',
                'students.view',
                'students.attendance',
                'classes.view',
                'subjects.view',
                'attendance.manage',
                'exams.manage',
                'reports.view'
            ],
            'Student' => [
                'dashboard.view',
                'profile.view',
                'attendance.view',
                'exams.view',
                'results.view',
                'library.view',
                'transport.view'
            ],
            'Parent' => [
                'dashboard.view',
                'children.view',
                'attendance.view',
                'exams.view',
                'results.view',
                'fees.view',
                'communication.view'
            ],
            'Accountant' => [
                'dashboard.view',
                'fees.manage',
                'reports.financial',
                'students.view',
                'billing.manage',
                'invoices.manage'
            ],
            'HR' => [
                'dashboard.view',
                'employees.manage',
                'hr.manage',
                'payroll.manage',
                'attendance.view',
                'reports.hr',
                'users.view'
            ]
        ];

        $userPermissions = $rolePermissions[$this->role] ?? [];
        
        if (in_array('*', $userPermissions)) {
            return true;
        }
        
        return in_array($permission, $userPermissions);
    }

    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            return $this->hasPermission($permissions);
        }
        
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if ($this->hasPermission($permission)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function hasAllPermissions($permissions)
    {
        if (is_string($permissions)) {
            return $this->hasPermission($permissions);
        }
        
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if (!$this->hasPermission($permission)) {
                    return false;
                }
            }
            return true;
        }
        
        return false;
    }

    public function getPermissions()
    {
        // SuperAdmin has all permissions
        if ($this->isSuperAdmin()) {
            return ['*'];
        }
        
        $permissions = [];
        
        // Get tenant-specific permissions
        if ($this->school_id) {
            $tenantPermission = TenantPermission::getForTenantAndRole($this->school_id, $this->role);
            if ($tenantPermission) {
                $permissions = $tenantPermission->getAllPermissions();
            }
        }
        
        // If no tenant-specific permissions, get default role permissions
        if (empty($permissions)) {
            $defaultRole = Role::where('slug', $this->role)
                              ->where('is_system', true)
                              ->first();
            
            if ($defaultRole) {
                $permissions = $defaultRole->permissions ?? [];
            }
        }
        
        return $permissions;
    }

    // Override Laravel's can method to be compatible
    public function can($abilities, $arguments = [])
    {
        // If $abilities is a string (single permission), use our permission system
        if (is_string($abilities)) {
            return $this->hasPermission($abilities);
        }
        
        // If $abilities is an array, check if user has any of those abilities
        if (is_array($abilities)) {
            return $this->hasAnyPermission($abilities);
        }
        
        // Fall back to parent implementation for other cases
        return parent::can($abilities, $arguments);
    }

    public function cannot($abilities, $arguments = [])
    {
        return !$this->can($abilities, $arguments);
    }
    
    /**
     * Check if user has access to a specific module
     */
    public function hasModuleAccess($module)
    {
        // SuperAdmin has access to all modules
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Check tenant-specific module access
        if ($this->school_id) {
            $tenantPermission = TenantPermission::getForTenantAndRole($this->school_id, $this->role);
            if ($tenantPermission && $tenantPermission->hasModuleAccess($module)) {
                return true;
            }
        }
        
        // Fall back to default role module access
        $defaultRole = Role::where('slug', $this->role)
                          ->where('is_system', true)
                          ->first();
        
        if ($defaultRole) {
            return $defaultRole->hasModuleAccess($module);
        }
        
        return false;
    }
    
    /**
     * Get all accessible modules for the user
     */
    public function getAccessibleModules()
    {
        // SuperAdmin has access to all modules
        if ($this->isSuperAdmin()) {
            return array_keys(Role::getAvailableModules());
        }
        
        $modules = [];
        
        // Get tenant-specific module access
        if ($this->school_id) {
            $tenantPermission = TenantPermission::getForTenantAndRole($this->school_id, $this->role);
            if ($tenantPermission) {
                $modules = $tenantPermission->module_access ?? [];
            }
        }
        
        // If no tenant-specific modules, get default role modules
        if (empty($modules)) {
            $defaultRole = Role::where('slug', $this->role)
                              ->where('is_system', true)
                              ->first();
            
            if ($defaultRole) {
                $modules = $defaultRole->module_access ?? [];
            }
        }
        
        return $modules;
    }
    
    /**
     * Get user's role object
     */
    public function getRoleObject()
    {
        return Role::where('slug', $this->role)
                  ->where('is_system', true)
                  ->first();
    }
    
    /**
     * Get tenant-specific permissions for the user
     */
    public function getTenantPermissions()
    {
        if (!$this->school_id) {
            return null;
        }
        
        return TenantPermission::getForTenantAndRole($this->school_id, $this->role);
    }
    
    /**
     * Check if user can perform a specific action on a module
     */
    public function canPerformAction($module, $action)
    {
        $permission = "{$module}.{$action}";
        return $this->hasPermission($permission);
    }
    
    /**
     * Get user's effective permissions (combining role and tenant-specific)
     */
    public function getEffectivePermissions()
    {
        $permissions = $this->getPermissions();
        $modules = $this->getAccessibleModules();
        
        return [
            'permissions' => $permissions,
            'modules' => $modules,
            'role' => $this->role,
            'is_superadmin' => $this->isSuperAdmin(),
            'tenant_id' => $this->school_id
        ];
    }
}