<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProjectPermissionService;
use App\Services\PermissionService;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    protected ProjectPermissionService $projectPermissionService;
    protected PermissionService $permissionService;

    public function __construct(
        ProjectPermissionService $projectPermissionService,
        PermissionService $permissionService
    ) {
        $this->projectPermissionService = $projectPermissionService;
        $this->permissionService = $permissionService;
    }

    /**
     * Get user's permissions
     */
    public function userPermissions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $permissions = $user->getAllPermissions();
            $capabilities = $this->permissionService->getRoleCapabilities($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'role' => $user->role
                    ],
                    'permissions' => $permissions,
                    'capabilities' => $capabilities,
                    'total_permissions' => count($permissions)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user has specific permission
     */
    public function checkPermission(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'permission' => 'required|string'
            ]);

            $user = $request->user();
            $permission = $request->input('permission');

            $hasPermission = $user->hasPermission($permission);

            return response()->json([
                'success' => true,
                'data' => [
                    'permission' => $permission,
                    'has_permission' => $hasPermission,
                    'user_role' => $user->role
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available permissions grouped by module
     */
    public function allPermissions(): JsonResponse
    {
        try {
            $permissions = $this->projectPermissionService->getPermissionsByModule();

            return response()->json([
                'success' => true,
                'data' => [
                    'permissions_by_module' => $permissions,
                    'total_modules' => count($permissions),
                    'total_permissions' => collect($permissions)->sum(function ($perms) {
                        return count($perms);
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all roles with their permissions
     */
    public function allRoles(): JsonResponse
    {
        try {
            $roles = Role::with('rolePermissions')->get();
            $rolePermissions = $this->projectPermissionService->getRolePermissionMapping();

            $rolesData = $roles->map(function ($role) use ($rolePermissions) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'is_system' => $role->is_system,
                    'is_active' => $role->is_active,
                    'permissions_count' => $role->rolePermissions->count(),
                    'module_access' => $role->module_access,
                    'users_count' => $role->users()->count(),
                    'permissions_info' => $rolePermissions[$role->slug] ?? null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $rolesData,
                    'total_roles' => $roles->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permissions for a specific role
     */
    public function rolePermissions(Request $request, string $roleSlug): JsonResponse
    {
        try {
            $role = Role::where('slug', $roleSlug)->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            $permissions = $this->projectPermissionService->getPermissionsForRole($roleSlug);
            $permissionsByModule = collect($permissions)->groupBy('module');

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'description' => $role->description,
                        'is_system' => $role->is_system,
                        'module_access' => $role->module_access
                    ],
                    'permissions' => $permissions,
                    'permissions_by_module' => $permissionsByModule,
                    'total_permissions' => count($permissions),
                    'users_count' => $role->users()->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system statistics
     */
    public function systemStats(): JsonResponse
    {
        try {
            $report = $this->projectPermissionService->generateSystemReport();

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate system stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get module permissions
     */
    public function modulePermissions(string $module): JsonResponse
    {
        try {
            $permissions = Permission::where('module', $module)
                ->orderBy('name')
                ->get();

            if ($permissions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module not found or has no permissions'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'module' => $module,
                    'permissions' => $permissions,
                    'total_permissions' => $permissions->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve module permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user role capabilities for frontend use
     */
    public function userCapabilities(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $capabilities = $this->permissionService->getRoleCapabilities($user);

            // Flatten capabilities for easier frontend consumption
            $flatCapabilities = [];
            foreach ($capabilities as $module => $perms) {
                foreach ($perms as $perm => $hasAccess) {
                    $flatCapabilities["{$module}.{$perm}"] = $hasAccess;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_role' => $user->role,
                    'capabilities' => $capabilities,
                    'flat_capabilities' => $flatCapabilities,
                    'accessible_modules' => $user->getRoleModel()?->module_access ?? []
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user capabilities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk check multiple permissions
     */
    public function bulkCheckPermissions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string'
            ]);

            $user = $request->user();
            $permissions = $request->input('permissions');
            $results = [];

            foreach ($permissions as $permission) {
                $results[$permission] = $user->hasPermission($permission);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_role' => $user->role,
                    'results' => $results,
                    'checked_permissions' => count($permissions)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check permissions: ' . $e->getMessage()
            ], 500);
        }
    }
}
