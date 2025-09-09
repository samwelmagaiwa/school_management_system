<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\SubscriptionPlan;
use App\Modules\SuperAdmin\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class SubscriptionPlanService
{
    /**
     * Get all subscription plans with filters and pagination
     */
    public function getAllPlans(array $filters = [], int $perPage = 15)
    {
        try {
            $query = SubscriptionPlan::query();

            // Apply filters
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            // Return empty paginator on error
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }

    /**
     * Create a new subscription plan
     */
    public function createPlan(array $data): SubscriptionPlan
    {
        DB::beginTransaction();
        
        try {
            $plan = SubscriptionPlan::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price_monthly' => $data['price_monthly'],
                'price_yearly' => $data['price_yearly'] ?? ($data['price_monthly'] * 10), // 2 months free
                'currency' => $data['currency'] ?? 'USD',
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'trial_days' => $data['trial_days'] ?? 14,
                'features' => $data['features'] ?? [],
                'limits' => $data['limits'] ?? [],
                'status' => $data['status'] ?? 'active',
                'type' => $data['type'] ?? 'standard',
                'sort_order' => $data['sort_order'] ?? 0,
                'is_popular' => $data['is_popular'] ?? false,
                'is_featured' => $data['is_featured'] ?? false,
                'metadata' => $data['metadata'] ?? [],
                'created_by' => auth()->id()
            ]);

            DB::commit();
            
            return $plan;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get subscription plan details
     */
    public function getPlanDetails(SubscriptionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_monthly' => $plan->price_monthly,
            'price_yearly' => $plan->price_yearly,
            'currency' => $plan->currency,
            'billing_cycle' => $plan->billing_cycle,
            'trial_days' => $plan->trial_days,
            'features' => $plan->features,
            'limits' => $plan->limits,
            'status' => $plan->status,
            'type' => $plan->type,
            'sort_order' => $plan->sort_order,
            'is_popular' => $plan->is_popular,
            'is_featured' => $plan->is_featured,
            'metadata' => $plan->metadata,
            'subscribers_count' => $this->getSubscribersCount($plan),
            'monthly_revenue' => $this->getMonthlyRevenue($plan),
            'yearly_revenue' => $this->getYearlyRevenue($plan),
            'created_at' => $plan->created_at,
            'updated_at' => $plan->updated_at
        ];
    }

    /**
     * Update subscription plan
     */
    public function updatePlan(SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        DB::beginTransaction();
        
        try {
            $updateData = [];
            
            // Basic information
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['price_monthly'])) $updateData['price_monthly'] = $data['price_monthly'];
            if (isset($data['price_yearly'])) $updateData['price_yearly'] = $data['price_yearly'];
            if (isset($data['currency'])) $updateData['currency'] = $data['currency'];
            if (isset($data['billing_cycle'])) $updateData['billing_cycle'] = $data['billing_cycle'];
            if (isset($data['trial_days'])) $updateData['trial_days'] = $data['trial_days'];
            if (isset($data['features'])) $updateData['features'] = $data['features'];
            if (isset($data['limits'])) $updateData['limits'] = $data['limits'];
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['type'])) $updateData['type'] = $data['type'];
            if (isset($data['sort_order'])) $updateData['sort_order'] = $data['sort_order'];
            if (isset($data['is_popular'])) $updateData['is_popular'] = $data['is_popular'];
            if (isset($data['is_featured'])) $updateData['is_featured'] = $data['is_featured'];
            if (isset($data['metadata'])) $updateData['metadata'] = $data['metadata'];
            
            $plan->update($updateData);
            
            DB::commit();
            
            return $plan;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete subscription plan
     */
    public function deletePlan(SubscriptionPlan $plan): bool
    {
        DB::beginTransaction();
        
        try {
            // Check if plan has active subscribers
            $activeSubscribers = Tenant::where('subscription_plan_id', $plan->id)
                ->where('subscription_status', 'active')
                ->count();
            
            if ($activeSubscribers > 0) {
                throw new \Exception("Cannot delete subscription plan with active subscribers. Please migrate subscribers to another plan first.");
            }
            
            // Soft delete or mark as deleted
            $plan->update([
                'status' => 'deleted',
                'deleted_at' => now(),
                'deleted_by' => auth()->id()
            ]);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get subscription plan statistics
     */
    public function getPlanStatistics(SubscriptionPlan $plan): array
    {
        return [
            'subscribers_count' => $this->getSubscribersCount($plan),
            'active_subscribers' => $this->getActiveSubscribersCount($plan),
            'trial_subscribers' => $this->getTrialSubscribersCount($plan),
            'monthly_revenue' => $this->getMonthlyRevenue($plan),
            'yearly_revenue' => $this->getYearlyRevenue($plan),
            'conversion_rate' => $this->getConversionRate($plan),
            'churn_rate' => $this->getChurnRate($plan),
            'average_subscription_duration' => $this->getAverageSubscriptionDuration($plan),
            'growth_this_month' => $this->getGrowthThisMonth($plan)
        ];
    }

    /**
     * Get subscribers count for a plan
     */
    private function getSubscribersCount(SubscriptionPlan $plan): int
    {
        try {
            return Tenant::where('subscription_plan_id', $plan->id)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get active subscribers count for a plan
     */
    private function getActiveSubscribersCount(SubscriptionPlan $plan): int
    {
        try {
            return Tenant::where('subscription_plan_id', $plan->id)
                ->where('subscription_status', 'active')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get trial subscribers count for a plan
     */
    private function getTrialSubscribersCount(SubscriptionPlan $plan): int
    {
        try {
            return Tenant::where('subscription_plan_id', $plan->id)
                ->where('is_trial', true)
                ->where('trial_expires_at', '>', now())
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get monthly revenue for a plan
     */
    private function getMonthlyRevenue(SubscriptionPlan $plan): float
    {
        try {
            $monthlySubscribers = Tenant::where('subscription_plan_id', $plan->id)
                ->where('subscription_status', 'active')
                ->where('billing_cycle', 'monthly')
                ->count();
            
            $yearlySubscribers = Tenant::where('subscription_plan_id', $plan->id)
                ->where('subscription_status', 'active')
                ->where('billing_cycle', 'yearly')
                ->count();
            
            return ($monthlySubscribers * $plan->price_monthly) + 
                   ($yearlySubscribers * ($plan->price_yearly / 12));
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get yearly revenue for a plan
     */
    private function getYearlyRevenue(SubscriptionPlan $plan): float
    {
        return $this->getMonthlyRevenue($plan) * 12;
    }

    /**
     * Get conversion rate for a plan
     */
    private function getConversionRate(SubscriptionPlan $plan): float
    {
        try {
            $totalTrials = Tenant::where('subscription_plan_id', $plan->id)
                ->where('is_trial', true)
                ->count();
            
            $convertedTrials = Tenant::where('subscription_plan_id', $plan->id)
                ->where('is_trial', false)
                ->where('subscription_status', 'active')
                ->count();
            
            return $totalTrials > 0 ? ($convertedTrials / $totalTrials) * 100 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get churn rate for a plan
     */
    private function getChurnRate(SubscriptionPlan $plan): float
    {
        try {
            $totalSubscribers = $this->getSubscribersCount($plan);
            $cancelledThisMonth = Tenant::where('subscription_plan_id', $plan->id)
                ->where('subscription_status', 'cancelled')
                ->whereMonth('updated_at', now()->month)
                ->count();
            
            return $totalSubscribers > 0 ? ($cancelledThisMonth / $totalSubscribers) * 100 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get average subscription duration for a plan
     */
    private function getAverageSubscriptionDuration(SubscriptionPlan $plan): int
    {
        try {
            $subscribers = Tenant::where('subscription_plan_id', $plan->id)
                ->whereNotNull('created_at')
                ->get();
            
            if ($subscribers->isEmpty()) {
                return 0;
            }
            
            $totalDays = $subscribers->sum(function ($tenant) {
                return $tenant->created_at->diffInDays(now());
            });
            
            return round($totalDays / $subscribers->count());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get growth this month for a plan
     */
    private function getGrowthThisMonth(SubscriptionPlan $plan): int
    {
        try {
            return Tenant::where('subscription_plan_id', $plan->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}