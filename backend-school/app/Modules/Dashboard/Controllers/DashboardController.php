<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Dashboard\Services\DashboardService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('auth:sanctum');
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get dashboard statistics and data
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Dashboard Load Error', 'Dashboard', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user-specific menu items
     */
    public function getMenuItems(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData['menu_items']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load menu items',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user permissions
     */
    public function getPermissions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData['permissions']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load permissions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get quick stats for widgets
     */
    public function getQuickStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData['quick_stats']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quick stats',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData['recent_activities']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent activities',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get notifications
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData['notifications']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load notifications',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get charts data
     */
    public function getChartsData(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData['charts_data']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load charts data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }


}