<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Services\SuperAdminService;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\SubscriptionPlan;
use App\Modules\SuperAdmin\Models\SystemSetting;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SuperAdminController extends Controller
{
    protected $superAdminService;

    public function __construct(SuperAdminService $superAdminService)
    {
        $this->superAdminService = $superAdminService;
        $this->middleware('auth:sanctum');
        // TODO: Add role check back when role middleware is properly configured
        // $this->middleware('role:SuperAdmin');
    }

    /**
     * Get SuperAdmin dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        try {
            $data = $this->superAdminService->getDashboardStats();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SuperAdmin dashboard data
     */
    public function dashboard(): JsonResponse
    {
        try {
            $data = $this->superAdminService->getDashboardData();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system overview statistics
     */
    public function systemOverview(): JsonResponse
    {
        try {
            $overview = $this->superAdminService->getSystemOverview();
            
            return response()->json([
                'success' => true,
                'data' => $overview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load system overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue analytics
     */
    public function revenueAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'monthly'); // monthly, yearly
            $analytics = $this->superAdminService->getRevenueAnalytics($period);
            
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load revenue analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tenant growth analytics
     */
    public function tenantGrowthAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '12months');
            $analytics = $this->superAdminService->getTenantGrowthAnalytics($period);
            
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tenant growth analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user activity analytics
     */
    public function userActivityAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '7days');
            $analytics = $this->superAdminService->getUserActivityAnalytics($period);
            
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user activity analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system health status
     */
    public function systemHealth(): JsonResponse
    {
        try {
            $health = $this->superAdminService->getSystemHealth();
            
            return response()->json([
                'success' => true,
                'data' => $health
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check system health',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activities across all tenants
     */
    public function recentActivities(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            $activities = $this->superAdminService->getRecentActivities($limit);
            
            return response()->json([
                'success' => true,
                'data' => $activities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alerts and notifications
     */
    public function alerts(): JsonResponse
    {
        try {
            $alerts = $this->superAdminService->getSystemAlerts();
            
            return response()->json([
                'success' => true,
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send system-wide announcement
     */
    public function sendAnnouncement(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,error',
            'target_tenants' => 'array',
            'target_roles' => 'array',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'schedule_at' => 'nullable|date|after:now'
        ]);

        try {
            $result = $this->superAdminService->sendSystemAnnouncement($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Announcement sent successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send announcement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform system maintenance tasks
     */
    public function performMaintenance(Request $request): JsonResponse
    {
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*' => 'in:cleanup_logs,optimize_database,clear_cache,backup_data,update_statistics'
        ]);

        try {
            $results = $this->superAdminService->performMaintenance($request->tasks);
            
            return response()->json([
                'success' => true,
                'message' => 'Maintenance tasks completed',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance tasks failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export system data
     */
    public function exportData(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:tenants,users,revenue,activities,full_backup',
            'format' => 'in:csv,excel,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'filters' => 'array'
        ]);

        try {
            $export = $this->superAdminService->exportData(
                $request->type,
                $request->format ?? 'csv',
                $request->only(['date_from', 'date_to', 'filters'])
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Export generated successfully',
                'data' => [
                    'download_url' => $export['url'],
                    'filename' => $export['filename'],
                    'size' => $export['size']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'user_id', 'tenant_id', 'action', 'module', 
                'date_from', 'date_to', 'search'
            ]);
            
            $logs = $this->superAdminService->getAuditLogs($filters, $request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get security logs
     */
    public function securityLogs(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'event_type', 'severity', 'ip_address', 
                'date_from', 'date_to', 'search'
            ]);
            
            $logs = $this->superAdminService->getSecurityLogs($filters, $request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load security logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update system configuration
     */
    public function updateSystemConfig(Request $request): JsonResponse
    {
        try {
            $result = $this->superAdminService->updateSystemConfiguration($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'System configuration updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update system configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}