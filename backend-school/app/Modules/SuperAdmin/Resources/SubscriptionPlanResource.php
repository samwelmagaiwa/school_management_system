<?php

namespace App\Modules\SuperAdmin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly,
            'currency' => $this->currency,
            'billing_cycle' => $this->billing_cycle,
            'trial_days' => $this->trial_days,
            'features' => $this->features ?? [],
            'limits' => $this->limits ?? [],
            'status' => $this->status,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            'is_popular' => $this->is_popular,
            'is_featured' => $this->is_featured,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed fields
            'formatted_price_monthly' => $this->getFormattedPrice($this->price_monthly),
            'formatted_price_yearly' => $this->getFormattedPrice($this->price_yearly),
            'yearly_savings' => $this->getYearlySavings(),
            'yearly_savings_percentage' => $this->getYearlySavingsPercentage(),
            'status_color' => $this->getStatusColor(),
            'type_label' => $this->getTypeLabel(),
            'subscribers_count' => $this->when(
                isset($this->subscribers_count),
                $this->subscribers_count,
                function () {
                    return $this->tenants()->count();
                }
            ),
            'monthly_revenue' => $this->when(
                isset($this->monthly_revenue),
                $this->monthly_revenue,
                function () {
                    return $this->calculateMonthlyRevenue();
                }
            ),
            'is_free' => $this->price_monthly == 0,
            'has_trial' => $this->trial_days > 0,
            'display_order' => $this->sort_order ?? 999
        ];
    }

    /**
     * Get formatted price with currency symbol
     */
    private function getFormattedPrice($price): string
    {
        if ($price == 0) {
            return 'Free';
        }

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$'
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';
        
        return $symbol . number_format($price, 2);
    }

    /**
     * Calculate yearly savings compared to monthly billing
     */
    private function getYearlySavings(): float
    {
        if (!$this->price_yearly || !$this->price_monthly) {
            return 0;
        }

        $yearlyFromMonthly = $this->price_monthly * 12;
        return max(0, $yearlyFromMonthly - $this->price_yearly);
    }

    /**
     * Calculate yearly savings percentage
     */
    private function getYearlySavingsPercentage(): float
    {
        if (!$this->price_yearly || !$this->price_monthly) {
            return 0;
        }

        $yearlyFromMonthly = $this->price_monthly * 12;
        $savings = $yearlyFromMonthly - $this->price_yearly;
        
        return $yearlyFromMonthly > 0 ? round(($savings / $yearlyFromMonthly) * 100, 1) : 0;
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => '#10b981',
            'inactive' => '#6b7280',
            'draft' => '#f59e0b',
            default => '#6b7280'
        };
    }

    /**
     * Get type label for display
     */
    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'free' => 'Free Plan',
            'basic' => 'Basic Plan',
            'standard' => 'Standard Plan',
            'premium' => 'Premium Plan',
            'enterprise' => 'Enterprise Plan',
            default => ucfirst($this->type) . ' Plan'
        };
    }

    /**
     * Calculate monthly revenue for this plan
     */
    private function calculateMonthlyRevenue(): float
    {
        try {
            $monthlySubscribers = $this->tenants()
                ->where('subscription_status', 'active')
                ->where('billing_cycle', 'monthly')
                ->count();
            
            $yearlySubscribers = $this->tenants()
                ->where('subscription_status', 'active')
                ->where('billing_cycle', 'yearly')
                ->count();
            
            return ($monthlySubscribers * $this->price_monthly) + 
                   ($yearlySubscribers * ($this->price_yearly / 12));
        } catch (\Exception $e) {
            return 0;
        }
    }
}