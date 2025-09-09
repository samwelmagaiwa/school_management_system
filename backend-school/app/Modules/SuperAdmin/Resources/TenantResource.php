<?php

namespace App\Modules\SuperAdmin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'status' => $this->status,
            'subscription_status' => $this->subscription_status,
            'contact_person' => $this->contact_person,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'billing_address' => $this->billing_address,
            'subscription_plan' => $this->whenLoaded('subscriptionPlan', function () {
                return [
                    'id' => $this->subscriptionPlan->id,
                    'name' => $this->subscriptionPlan->name,
                    'price_monthly' => $this->subscriptionPlan->price_monthly,
                    'price_yearly' => $this->subscriptionPlan->price_yearly,
                    'features' => $this->subscriptionPlan->features ?? []
                ];
            }),
            'subscription_plan_id' => $this->subscription_plan_id,
            'users_limit' => $this->users_limit,
            'users_count' => $this->when(
                isset($this->users_count),
                $this->users_count,
                function () {
                    return $this->users()->count();
                }
            ),
            'storage_limit' => $this->storage_limit,
            'storage_used' => $this->storage_used,
            'storage_percentage' => $this->storage_limit > 0 ? 
                round(($this->storage_used / $this->storage_limit) * 100, 2) : 0,
            'is_trial' => $this->is_trial,
            'trial_expires_at' => $this->trial_expires_at?->toISOString(),
            'subscription_expires_at' => $this->subscription_expires_at?->toISOString(),
            'billing_cycle' => $this->billing_cycle,
            'auto_renew' => $this->auto_renew,
            'features_enabled' => $this->features_enabled ?? [],
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'full_name' => $this->creator->full_name,
                    'email' => $this->creator->email
                ];
            }),
            'approver' => $this->whenLoaded('approver', function () {
                return [
                    'id' => $this->approver->id,
                    'full_name' => $this->approver->full_name,
                    'email' => $this->approver->email
                ];
            }),
            'approved_at' => $this->approved_at?->toISOString(),
            'suspended_at' => $this->suspended_at?->toISOString(),
            'suspension_reason' => $this->suspension_reason,
            'metadata' => $this->metadata ?? [],
            
            // Computed fields
            'status_color' => $this->getStatusColor(),
            'subscription_status_color' => $this->getSubscriptionStatusColor(),
            'days_until_expiration' => $this->getDaysUntilExpiration(),
            'trial_days_remaining' => $this->getTrialDaysRemaining(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'is_overdue' => $this->isOverdue()
        ];
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => '#10b981',
            'pending' => '#f59e0b',
            'suspended' => '#ef4444',
            'cancelled' => '#6b7280',
            default => '#6b7280'
        };
    }

    /**
     * Get subscription status color for UI
     */
    private function getSubscriptionStatusColor(): string
    {
        return match ($this->subscription_status) {
            'active' => '#10b981',
            'expired' => '#ef4444',
            'cancelled' => '#6b7280',
            'suspended' => '#f59e0b',
            default => '#6b7280'
        };
    }

    /**
     * Get days until subscription expiration
     */
    private function getDaysUntilExpiration(): ?int
    {
        if (!$this->subscription_expires_at) {
            return null;
        }

        return now()->diffInDays($this->subscription_expires_at, false);
    }

    /**
     * Get trial days remaining
     */
    private function getTrialDaysRemaining(): ?int
    {
        if (!$this->is_trial || !$this->trial_expires_at) {
            return null;
        }

        return now()->diffInDays($this->trial_expires_at, false);
    }

    /**
     * Check if subscription is expiring soon (within 7 days)
     */
    private function isExpiringSoon(): bool
    {
        if (!$this->subscription_expires_at) {
            return false;
        }

        $daysUntilExpiration = $this->getDaysUntilExpiration();
        return $daysUntilExpiration !== null && $daysUntilExpiration <= 7 && $daysUntilExpiration > 0;
    }

    /**
     * Check if subscription is overdue
     */
    private function isOverdue(): bool
    {
        if (!$this->subscription_expires_at) {
            return false;
        }

        return $this->subscription_expires_at->isPast();
    }
}