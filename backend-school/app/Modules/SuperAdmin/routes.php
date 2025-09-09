<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SuperAdmin\Controllers\SuperAdminController;
use App\Modules\SuperAdmin\Controllers\TenantController;
use App\Modules\SuperAdmin\Controllers\SubscriptionPlanController;
use App\Modules\SuperAdmin\Controllers\RolePermissionController;

/*
|--------------------------------------------------------------------------
| SuperAdmin Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the SuperAdmin module. All routes are protected
| by auth:sanctum middleware.
|
*/

// Test route without auth
Route::get('superadmin/test', function() {
    return response()->json(['success' => true, 'message' => 'SuperAdmin routes loaded!']);
});

// Test route with auth but no role check
Route::middleware(['auth:sanctum'])->get('superadmin/test-auth', function() {
    $user = auth()->user();
    return response()->json([
        'success' => true, 
        'message' => 'SuperAdmin auth test successful!',
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'has_role_method' => method_exists($user, 'hasAnyRole'),
            'available_methods' => get_class_methods($user)
        ]
    ]);
});

Route::middleware(['auth:sanctum'])->prefix('superadmin')->group(function () {
    // Dashboard & Analytics
    Route::get('/dashboard/stats', [SuperAdminController::class, 'getDashboardStats'])->name('superadmin.dashboard.stats');
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
    Route::get('/system-overview', [SuperAdminController::class, 'systemOverview'])->name('superadmin.system.overview');
    Route::get('/revenue-analytics', [SuperAdminController::class, 'revenueAnalytics'])->name('superadmin.revenue.analytics');
    Route::get('/tenant-growth-analytics', [SuperAdminController::class, 'tenantGrowthAnalytics'])->name('superadmin.tenant.growth');
    Route::get('/user-activity-analytics', [SuperAdminController::class, 'userActivityAnalytics'])->name('superadmin.user.activity');
    Route::get('/system-health', [SuperAdminController::class, 'systemHealth'])->name('superadmin.system.health');
    Route::get('/recent-activities', [SuperAdminController::class, 'recentActivities'])->name('superadmin.recent.activities');
    Route::get('/alerts', [SuperAdminController::class, 'alerts'])->name('superadmin.alerts');
    
    // Communication
    Route::post('/send-announcement', [SuperAdminController::class, 'sendAnnouncement'])->name('superadmin.send.announcement');
    
    // Maintenance
    Route::post('/perform-maintenance', [SuperAdminController::class, 'performMaintenance'])->name('superadmin.perform.maintenance');
    
    // Data Export
    Route::post('/export-data', [SuperAdminController::class, 'exportData'])->name('superadmin.export.data');
    
    // Logs
    Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs'])->name('superadmin.audit.logs');
    Route::get('/security-logs', [SuperAdminController::class, 'securityLogs'])->name('superadmin.security.logs');
    
    // System Configuration
    Route::put('/system-config', [SuperAdminController::class, 'updateSystemConfig'])->name('superadmin.system.config');
    
    // Tenant Management
    Route::get('/tenants', [TenantController::class, 'index'])->name('superadmin.tenants.index');
    Route::post('/tenants', [TenantController::class, 'store'])->name('superadmin.tenants.store');
    Route::get('/tenants/overview', [TenantController::class, 'overview'])->name('superadmin.tenants.overview');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('superadmin.tenants.show');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('superadmin.tenants.update');
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('superadmin.tenants.destroy');
    Route::post('/tenants/{tenant}/approve', [TenantController::class, 'approve'])->name('superadmin.tenants.approve');
    Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('superadmin.tenants.suspend');
    Route::post('/tenants/{tenant}/reactivate', [TenantController::class, 'reactivate'])->name('superadmin.tenants.reactivate');
    Route::put('/tenants/{tenant}/subscription', [TenantController::class, 'updateSubscription'])->name('superadmin.tenants.subscription');
    Route::put('/tenants/{tenant}/features', [TenantController::class, 'updateFeatures'])->name('superadmin.tenants.features');
    Route::get('/tenants/{tenant}/statistics', [TenantController::class, 'statistics'])->name('superadmin.tenants.statistics');
    Route::get('/tenants/{tenant}/activity-logs', [TenantController::class, 'activityLogs'])->name('superadmin.tenants.activity-logs');
    Route::post('/tenants/{tenant}/backup', [TenantController::class, 'backup'])->name('superadmin.tenants.backup');
    Route::post('/tenants/{tenant}/restore', [TenantController::class, 'restore'])->name('superadmin.tenants.restore');
    Route::get('/tenants/{tenant}/billing', [TenantController::class, 'billing'])->name('superadmin.tenants.billing');
    Route::post('/tenants/{tenant}/generate-invoice', [TenantController::class, 'generateInvoice'])->name('superadmin.tenants.generate-invoice');
    
    // Subscription Plans Management
    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->name('superadmin.subscription-plans.index');
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store'])->name('superadmin.subscription-plans.store');
    Route::get('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'show'])->name('superadmin.subscription-plans.show');
    Route::put('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update'])->name('superadmin.subscription-plans.update');
    Route::delete('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy'])->name('superadmin.subscription-plans.destroy');
    Route::get('/subscription-plans/{subscriptionPlan}/statistics', [SubscriptionPlanController::class, 'statistics'])->name('superadmin.subscription-plans.statistics');
    
    // Role & Permission Management
    Route::prefix('roles')->group(function () {
        Route::get('/', [RolePermissionController::class, 'getRoles'])->name('superadmin.roles.index');
        Route::get('/default', [RolePermissionController::class, 'getDefaultRoles'])->name('superadmin.roles.default');
        Route::post('/', [RolePermissionController::class, 'createRole'])->name('superadmin.roles.store');
        Route::put('/{role}', [RolePermissionController::class, 'updateRole'])->name('superadmin.roles.update');
        Route::delete('/{role}', [RolePermissionController::class, 'deleteRole'])->name('superadmin.roles.destroy');
        Route::get('/statistics', [RolePermissionController::class, 'getRoleStatistics'])->name('superadmin.roles.statistics');
        Route::post('/initialize-defaults', [RolePermissionController::class, 'initializeDefaultRoles'])->name('superadmin.roles.initialize-defaults');
    });
    
    Route::prefix('permissions')->group(function () {
        Route::get('/modules', [RolePermissionController::class, 'getModulesAndPermissions'])->name('superadmin.permissions.modules');
        Route::get('/tenant', [RolePermissionController::class, 'getTenantPermissions'])->name('superadmin.permissions.tenant');
        Route::put('/tenant', [RolePermissionController::class, 'updateTenantPermissions'])->name('superadmin.permissions.tenant.update');
        Route::post('/grant-module-access', [RolePermissionController::class, 'grantModuleAccess'])->name('superadmin.permissions.grant-module');
        Route::post('/revoke-module-access', [RolePermissionController::class, 'revokeModuleAccess'])->name('superadmin.permissions.revoke-module');
        Route::post('/reset-to-default', [RolePermissionController::class, 'resetToDefault'])->name('superadmin.permissions.reset-default');
    });
});
