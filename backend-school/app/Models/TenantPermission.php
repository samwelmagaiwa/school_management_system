<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantPermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'role_slug',
        'permissions',
        'module_access',
        'custom_permissions',
        'is_active'
    ];

    protected $casts = [
        'permissions' => 'array',
        'module_access' => 'array',
        'custom_permissions' => 'array',
        'is_active' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_slug', 'slug');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForRole($query, $roleSlug)
    {
        return $query->where('role_slug', $roleSlug);
    }

    // Helper methods
    public function hasPermission($permission)
    {
        $permissions = $this->permissions ?? [];
        $customPermissions = $this->custom_permissions ?? [];
        
        // Check in both regular and custom permissions
        return in_array($permission, $permissions) || in_array($permission, $customPermissions);
    }

    public function hasModuleAccess($module)
    {
        $moduleAccess = $this->module_access ?? [];
        return in_array($module, $moduleAccess);
    }

    public function addPermission($permission, $isCustom = false)
    {
        if ($isCustom) {
            $customPermissions = $this->custom_permissions ?? [];
            if (!in_array($permission, $customPermissions)) {
                $customPermissions[] = $permission;
                $this->custom_permissions = $customPermissions;
            }
        } else {
            $permissions = $this->permissions ?? [];
            if (!in_array($permission, $permissions)) {
                $permissions[] = $permission;
                $this->permissions = $permissions;
            }
        }
        
        $this->save();
        return $this;
    }

    public function removePermission($permission, $isCustom = false)
    {
        if ($isCustom) {
            $customPermissions = $this->custom_permissions ?? [];
            if (($key = array_search($permission, $customPermissions)) !== false) {
                unset($customPermissions[$key]);
                $this->custom_permissions = array_values($customPermissions);
            }
        } else {
            $permissions = $this->permissions ?? [];
            if (($key = array_search($permission, $permissions)) !== false) {
                unset($permissions[$key]);
                $this->permissions = array_values($permissions);
            }
        }
        
        $this->save();
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

    public function getAllPermissions()
    {
        $permissions = $this->permissions ?? [];
        $customPermissions = $this->custom_permissions ?? [];
        
        return array_unique(array_merge($permissions, $customPermissions));
    }

    // Static methods
    public static function getForTenantAndRole($tenantId, $roleSlug)
    {
        return self::where('tenant_id', $tenantId)
                   ->where('role_slug', $roleSlug)
                   ->where('is_active', true)
                   ->first();
    }

    public static function createOrUpdateForTenant($tenantId, $roleSlug, $permissions, $moduleAccess)
    {
        return self::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'role_slug' => $roleSlug
            ],
            [
                'permissions' => $permissions,
                'module_access' => $moduleAccess,
                'is_active' => true
            ]
        );
    }

    public static function copyFromDefaultRole($tenantId, $roleSlug)
    {
        $defaultRole = Role::where('slug', $roleSlug)
                          ->where('is_system', true)
                          ->first();
        
        if (!$defaultRole) {
            return null;
        }
        
        return self::createOrUpdateForTenant(
            $tenantId,
            $roleSlug,
            $defaultRole->permissions,
            $defaultRole->module_access
        );
    }

    public static function resetToDefault($tenantId, $roleSlug)
    {
        $tenantPermission = self::getForTenantAndRole($tenantId, $roleSlug);
        
        if ($tenantPermission) {
            $defaultRole = Role::where('slug', $roleSlug)
                              ->where('is_system', true)
                              ->first();
            
            if ($defaultRole) {
                $tenantPermission->update([
                    'permissions' => $defaultRole->permissions,
                    'module_access' => $defaultRole->module_access,
                    'custom_permissions' => []
                ]);
            }
        }
        
        return $tenantPermission;
    }

    // Get statistics for tenant permissions
    public function getStatistics()
    {
        return [
            'total_permissions' => count($this->getAllPermissions()),
            'default_permissions' => count($this->permissions ?? []),
            'custom_permissions' => count($this->custom_permissions ?? []),
            'module_access_count' => count($this->module_access ?? [])
        ];
    }
}