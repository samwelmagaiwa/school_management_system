<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        return $this->hasOne(Teacher::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    // For parent users - children they are responsible for
    public function children()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    // Activity logs (if ActivityLog model exists)
    public function activityLogs()
    {
        // return $this->hasMany(ActivityLog::class);
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
            return School::all();
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

    // Permission relationships
    public function permissions()
    {
        // Get permissions through role
        $roleModel = $this->getRoleModel();
        if (!$roleModel) {
            return collect([]);
        }
        return $roleModel->rolePermissions();
    }
    
    public function roles()
    {
        return $this->hasMany(Role::class, 'slug', 'role');
    }

    // Enhanced permission checking methods
    public function hasPermission($permission)
    {
        // SuperAdmin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Use ProjectPermissionService for comprehensive permission checking
        $permissionService = app(\App\Services\ProjectPermissionService::class);
        return $permissionService->userHasPermission($this, $permission);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all permissions for this user
     */
    public function getAllPermissions(): array
    {
        $permissionService = app(\App\Services\ProjectPermissionService::class);
        return $permissionService->getPermissionsForRole($this->role);
    }
    
    /**
     * Get user's role model
     */
    public function getRoleModel()
    {
        return \App\Models\Role::where('slug', $this->role)->first();
    }
    
    /**
     * Check if user can access a specific module
     */
    public function canAccessModule(string $module): bool
    {
        $roleModel = $this->getRoleModel();
        
        if (!$roleModel) {
            return false;
        }
        
        return $roleModel->hasModuleAccess($module);
    }
    
}
