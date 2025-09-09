<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\Role;
use App\Modules\SuperAdmin\Models\Permission;
use App\Modules\SuperAdmin\Models\TenantPermission;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Requests\RoleRequest;
use App\Modules\SuperAdmin\Requests\TenantPermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolePermissionController extends Controller
{
    /**
     * Get all roles with their permissions
     */
    public function getRoles(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->get('tenant_id');
            
            $query = Role::with(['users' => function($q) {
                $q->select('id', 'first_name', 'last_name', 'email', 'role');
            }]);
            
            if ($tenantId) {
                $query->forTenant($tenantId);
            }
            
            $roles = $query->active()->get()->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'is_default' => $role->is_default,
                    'is_system' => $role->is_system,
                    'tenant_id' => $role->tenant_id,
                    'permissions' => $role->permissions,
                    'module_access' => $role->module_access,
                    'is_active' => $role->is_active,
                    'users_count' => $role->users->count(),
                    'statistics' => $role->getStatistics(),
                    'can_be_deleted' => $role->canBeDeleted(),
                    'can_be_modified' => $role->canBeModified(),
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching roles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get default system roles
     */
    public function getDefaultRoles(): JsonResponse
    {
        try {
            $defaultRoles = Role::getDefaultRoles();
            
            return response()->json([
                'success' => true,
                'data' => $defaultRoles
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching default roles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch default roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new role
     */
    public function createRole(RoleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $role = Role::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'tenant_id' => $request->tenant_id,
                'permissions' => $request->permissions ?? [],
                'module_access' => $request->module_access ?? [],
                'is_default' => $request->is_default ?? false,
                'is_system' => false, // Custom roles are never system roles
                'is_active' => $request->is_active ?? true
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating role: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a role
     */
    public function updateRole(RoleRequest $request, Role $role): JsonResponse
    {
        try {
            // Check if role can be modified
            if (!$role->canBeModified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be modified'
                ], 403);
            }
            
            DB::beginTransaction();
            
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'permissions' => $request->permissions ?? $role->permissions,
                'module_access' => $request->module_access ?? $role->module_access,
                'is_active' => $request->is_active ?? $role->is_active
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating role: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole(Role $role): JsonResponse
    {
        try {
            // Check if role can be deleted
            if (!$role->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be deleted'
                ], 403);
            }
            
            // Check if role has users
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role that has assigned users'
                ], 400);
            }
            
            DB::beginTransaction();
            
            $role->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting role: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available modules and permissions
     */
    public function getModulesAndPermissions(): JsonResponse
    {
        try {
            $modules = Role::getAvailableModules();
            $permissions = Role::getModulePermissions();
            $allPermissions = Role::getAllPermissions();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'modules' => $modules,
                    'permissions_by_module' => $permissions,
                    'all_permissions' => $allPermissions
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching modules and permissions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch modules and permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tenant-specific permissions
     */
    public function getTenantPermissions(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->get('tenant_id');
            $roleSlug = $request->get('role_slug');
            
            if (!$tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant ID is required'
                ], 400);
            }
            
            $query = TenantPermission::where('tenant_id', $tenantId);
            
            if ($roleSlug) {
                $query->where('role_slug', $roleSlug);
            }
            
            $tenantPermissions = $query->active()->get()->map(function($tp) {
                return [
                    'id' => $tp->id,
                    'tenant_id' => $tp->tenant_id,
                    'role_slug' => $tp->role_slug,
                    'permissions' => $tp->permissions,
                    'module_access' => $tp->module_access,
                    'custom_permissions' => $tp->custom_permissions,
                    'all_permissions' => $tp->getAllPermissions(),
                    'statistics' => $tp->getStatistics(),
                    'is_active' => $tp->is_active,
                    'created_at' => $tp->created_at,
                    'updated_at' => $tp->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $tenantPermissions
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching tenant permissions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tenant permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update tenant-specific permissions
     */
    public function updateTenantPermissions(TenantPermissionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $tenantPermission = TenantPermission::updateOrCreate(
                [
                    'tenant_id' => $request->tenant_id,
                    'role_slug' => $request->role_slug
                ],
                [
                    'permissions' => $request->permissions ?? [],
                    'module_access' => $request->module_access ?? [],
                    'custom_permissions' => $request->custom_permissions ?? [],
                    'is_active' => $request->is_active ?? true
                ]
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Tenant permissions updated successfully',
                'data' => $tenantPermission
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating tenant permissions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tenant permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Grant module access to tenant
     */
    public function grantModuleAccess(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'role_slug' => 'required|string',
                'modules' => 'required|array',
                'modules.*' => 'string'
            ]);
            
            DB::beginTransaction();
            
            $tenantPermission = TenantPermission::firstOrCreate(
                [
                    'tenant_id' => $request->tenant_id,
                    'role_slug' => $request->role_slug
                ],
                [
                    'permissions' => [],
                    'module_access' => [],
                    'is_active' => true
                ]
            );
            
            foreach ($request->modules as $module) {
                $tenantPermission->addModuleAccess($module);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Module access granted successfully',
                'data' => $tenantPermission
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error granting module access: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant module access',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke module access from tenant
     */
    public function revokeModuleAccess(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'role_slug' => 'required|string',
                'modules' => 'required|array',
                'modules.*' => 'string'
            ]);
            
            DB::beginTransaction();
            
            $tenantPermission = TenantPermission::getForTenantAndRole(
                $request->tenant_id,
                $request->role_slug
            );
            
            if (!$tenantPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant permission configuration not found'
                ], 404);
            }
            
            foreach ($request->modules as $module) {
                $tenantPermission->removeModuleAccess($module);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Module access revoked successfully',
                'data' => $tenantPermission
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error revoking module access: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke module access',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset tenant permissions to default
     */
    public function resetToDefault(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'role_slug' => 'required|string'
            ]);
            
            DB::beginTransaction();
            
            $tenantPermission = TenantPermission::resetToDefault(
                $request->tenant_id,
                $request->role_slug
            );
            
            if (!$tenantPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reset permissions to default'
                ], 400);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Permissions reset to default successfully',
                'data' => $tenantPermission
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resetting permissions to default: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset permissions to default',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize default roles for a tenant
     */
    public function initializeDefaultRoles(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id'
            ]);
            
            DB::beginTransaction();
            
            $roles = Role::createDefaultRoles($request->tenant_id);
            
            // Create tenant permission configurations for each role
            foreach ($roles as $role) {
                TenantPermission::createOrUpdateForTenant(
                    $request->tenant_id,
                    $role->slug,
                    $role->permissions,
                    $role->module_access
                );
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Default roles initialized successfully',
                'data' => $roles
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error initializing default roles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize default roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->get('tenant_id');
            
            $query = Role::query();
            
            if ($tenantId) {
                $query->forTenant($tenantId);
            }
            
            $roles = $query->active()->get();
            
            $statistics = [
                'total_roles' => $roles->count(),
                'system_roles' => $roles->where('is_system', true)->count(),
                'custom_roles' => $roles->where('is_system', false)->count(),
                'active_roles' => $roles->where('is_active', true)->count(),
                'roles_with_users' => $roles->filter(function($role) {
                    return $role->users()->count() > 0;
                })->count(),
                'role_details' => $roles->map(function($role) {
                    return [
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'users_count' => $role->users()->count(),
                        'permissions_count' => count($role->permissions ?? []),
                        'modules_count' => count($role->module_access ?? [])
                    ];
                })
            ];
            
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching role statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}