<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Services\TenantService;
use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Requests\StoreTenantRequest;
use App\Modules\SuperAdmin\Requests\UpdateTenantRequest;
use App\Modules\SuperAdmin\Resources\TenantResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    protected $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
        $this->middleware('auth:sanctum');
        // TODO: Re-enable role middleware when role system is fully configured
        // $this->middleware('role:SuperAdmin');
    }

    /**
     * Display a listing of tenants
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'status', 'subscription_status', 'subscription_plan_id',
                'is_trial', 'date_from', 'date_to', 'sort_by', 'sort_order'
            ]);

            $tenants = $this->tenantService->getAllTenants($filters, $request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => TenantResource::collection($tenants->items()),
                'meta' => [
                    'current_page' => $tenants->currentPage(),
                    'last_page' => $tenants->lastPage(),
                    'per_page' => $tenants->perPage(),
                    'total' => $tenants->total(),
                    'from' => $tenants->firstItem(),
                    'to' => $tenants->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tenants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created tenant
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        try {
            $tenant = $this->tenantService->createTenant($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tenant created successfully',
                'data' => new TenantResource($tenant)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified tenant
     */
    public function show(Tenant $tenant): JsonResponse
    {
        try {
            $tenantData = $this->tenantService->getTenantDetails($tenant);

            return response()->json([
                'success' => true,
                'data' => $tenantData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tenant details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified tenant
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        try {
            $updatedTenant = $this->tenantService->updateTenant($tenant, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tenant updated successfully',
                'data' => new TenantResource($updatedTenant)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified tenant
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        try {
            $this->tenantService->deleteTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a pending tenant
     */
    public function approve(Tenant $tenant): JsonResponse
    {
        try {
            $approvedTenant = $this->tenantService->approveTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant approved successfully',
                'data' => new TenantResource($approvedTenant)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspend a tenant
     */
    public function suspend(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $suspendedTenant = $this->tenantService->suspendTenant($tenant, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Tenant suspended successfully',
                'data' => new TenantResource($suspendedTenant)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate a suspended tenant
     */
    public function reactivate(Tenant $tenant): JsonResponse
    {
        try {
            $reactivatedTenant = $this->tenantService->reactivateTenant($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Tenant reactivated successfully',
                'data' => new TenantResource($reactivatedTenant)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate tenant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update tenant subscription
     */
    public function updateSubscription(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'start_date' => 'nullable|date',
            'auto_renew' => 'boolean'
        ]);

        try {
            $updatedTenant = $this->tenantService->updateSubscription($tenant, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully',
                'data' => new TenantResource($updatedTenant)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manage tenant features
     */
    public function updateFeatures(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'features' => 'required|array',
            'features.*' => 'string'
        ]);

        try {
            $updatedTenant = $this->tenantService->updateFeatures($tenant, $request->features);

            return response()->json([
                'success' => true,
                'message' => 'Features updated successfully',
                'data' => new TenantResource($updatedTenant)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update features',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tenant statistics
     */
    public function statistics(Tenant $tenant): JsonResponse
    {
        try {
            $statistics = $this->tenantService->getTenantStatistics($tenant);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tenant statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tenant activity logs
     */
    public function activityLogs(Request $request, Tenant $tenant): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'action', 'user_id']);
            $logs = $this->tenantService->getTenantActivityLogs($tenant, $filters, $request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Backup tenant data
     */
    public function backup(Tenant $tenant): JsonResponse
    {
        try {
            $backup = $this->tenantService->backupTenantData($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => $backup
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore tenant data
     */
    public function restore(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'backup_file' => 'required|string',
            'restore_options' => 'array'
        ]);

        try {
            $result = $this->tenantService->restoreTenantData($tenant, $request->backup_file, $request->restore_options ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Data restored successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tenant billing information
     */
    public function billing(Tenant $tenant): JsonResponse
    {
        try {
            $billing = $this->tenantService->getTenantBilling($tenant);

            return response()->json([
                'success' => true,
                'data' => $billing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load billing information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate invoice for tenant
     */
    public function generateInvoice(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'billing_period' => 'required|date',
            'due_date' => 'required|date|after:today',
            'items' => 'array'
        ]);

        try {
            $invoice = $this->tenantService->generateInvoice($tenant, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tenant overview for dashboard
     */
    public function overview(): JsonResponse
    {
        try {
            $overview = $this->tenantService->getTenantsOverview();

            return response()->json([
                'success' => true,
                'data' => $overview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tenants overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}