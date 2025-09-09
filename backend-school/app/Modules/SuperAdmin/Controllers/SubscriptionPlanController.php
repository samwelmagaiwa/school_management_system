<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Services\SubscriptionPlanService;
use App\Modules\SuperAdmin\Models\SubscriptionPlan;
use App\Modules\SuperAdmin\Requests\StoreSubscriptionPlanRequest;
use App\Modules\SuperAdmin\Requests\UpdateSubscriptionPlanRequest;
use App\Modules\SuperAdmin\Resources\SubscriptionPlanResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionPlanController extends Controller
{
    protected $subscriptionPlanService;

    public function __construct(SubscriptionPlanService $subscriptionPlanService)
    {
        $this->subscriptionPlanService = $subscriptionPlanService;
        $this->middleware('auth:sanctum');
        // TODO: Re-enable role middleware when role system is fully configured
        // $this->middleware('role:SuperAdmin');
    }

    /**
     * Display a listing of subscription plans
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'status', 'type', 'sort_by', 'sort_order'
            ]);

            $plans = $this->subscriptionPlanService->getAllPlans($filters, $request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => SubscriptionPlanResource::collection($plans->items()),
                'meta' => [
                    'current_page' => $plans->currentPage(),
                    'last_page' => $plans->lastPage(),
                    'per_page' => $plans->perPage(),
                    'total' => $plans->total(),
                    'from' => $plans->firstItem(),
                    'to' => $plans->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load subscription plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created subscription plan
     */
    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        try {
            $plan = $this->subscriptionPlanService->createPlan($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan created successfully',
                'data' => new SubscriptionPlanResource($plan)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified subscription plan
     */
    public function show(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        try {
            $planData = $this->subscriptionPlanService->getPlanDetails($subscriptionPlan);

            return response()->json([
                'success' => true,
                'data' => $planData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load subscription plan details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified subscription plan
     */
    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        try {
            $updatedPlan = $this->subscriptionPlanService->updatePlan($subscriptionPlan, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan updated successfully',
                'data' => new SubscriptionPlanResource($updatedPlan)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified subscription plan
     */
    public function destroy(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        try {
            $this->subscriptionPlanService->deletePlan($subscriptionPlan);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription plan statistics
     */
    public function statistics(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        try {
            $statistics = $this->subscriptionPlanService->getPlanStatistics($subscriptionPlan);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load subscription plan statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}