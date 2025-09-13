<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'features',
        'limits',
        'is_popular',
        'is_active',
        'status', // Added for compatibility
        'type', // Added for compatibility
        'is_featured', // Added for compatibility
        'trial_days',
        'setup_fee',
        'billing_cycle',
        'max_schools',
        'max_users',
        'max_storage_gb',
        'modules_included',
        'support_level',
        'custom_branding',
        'api_access',
        'backup_frequency',
        'sort_order',
        'metadata', // Added for compatibility
        'created_by', // Added for compatibility
        'deleted_by' // Added for compatibility
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'modules_included' => 'array',
        'metadata' => 'array', // Added for compatibility
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean', // Added for compatibility
        'custom_branding' => 'boolean',
        'api_access' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'max_schools' => 'integer',
        'max_users' => 'integer',
        'max_storage_gb' => 'integer',
        'trial_days' => 'integer',
        'sort_order' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    // Billing cycle constants
    const BILLING_MONTHLY = 'monthly';
    const BILLING_YEARLY = 'yearly';
    const BILLING_LIFETIME = 'lifetime';

    // Support level constants
    const SUPPORT_BASIC = 'basic';
    const SUPPORT_STANDARD = 'standard';
    const SUPPORT_PREMIUM = 'premium';
    const SUPPORT_ENTERPRISE = 'enterprise';

    /**
     * Get all tenants using this plan
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get active tenants count
     */
    public function getActiveTenantsCount()
    {
        return $this->tenants()->where('status', Tenant::STATUS_ACTIVE)->count();
    }

    /**
     * Get monthly revenue from this plan
     */
    public function getMonthlyRevenue()
    {
        $monthlyTenants = $this->tenants()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->where('subscription_status', Tenant::SUBSCRIPTION_ACTIVE)
            ->where('billing_cycle', self::BILLING_MONTHLY)
            ->count();

        $yearlyTenants = $this->tenants()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->where('subscription_status', Tenant::SUBSCRIPTION_ACTIVE)
            ->where('billing_cycle', self::BILLING_YEARLY)
            ->count();

        return ($monthlyTenants * $this->price_monthly) + 
               ($yearlyTenants * ($this->price_yearly / 12));
    }

    /**
     * Get yearly revenue from this plan
     */
    public function getYearlyRevenue()
    {
        $monthlyTenants = $this->tenants()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->where('subscription_status', Tenant::SUBSCRIPTION_ACTIVE)
            ->where('billing_cycle', self::BILLING_MONTHLY)
            ->count();

        $yearlyTenants = $this->tenants()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->where('subscription_status', Tenant::SUBSCRIPTION_ACTIVE)
            ->where('billing_cycle', self::BILLING_YEARLY)
            ->count();

        return ($monthlyTenants * $this->price_monthly * 12) + 
               ($yearlyTenants * $this->price_yearly);
    }

    /**
     * Check if plan includes a specific module
     */
    public function includesModule($module)
    {
        return in_array($module, $this->modules_included ?? []);
    }

    /**
     * Get plan features as formatted list
     */
    public function getFormattedFeatures()
    {
        return $this->features ?? [];
    }

    /**
     * Get plan limits as formatted list
     */
    public function getFormattedLimits()
    {
        $limits = $this->limits ?? [];
        $formatted = [];

        if ($this->max_schools) {
            $formatted['Schools'] = $this->max_schools === -1 ? 'Unlimited' : $this->max_schools;
        }

        if ($this->max_users) {
            $formatted['Users'] = $this->max_users === -1 ? 'Unlimited' : number_format($this->max_users);
        }

        if ($this->max_storage_gb) {
            $formatted['Storage'] = $this->max_storage_gb === -1 ? 'Unlimited' : $this->max_storage_gb . ' GB';
        }

        return array_merge($formatted, $limits);
    }

    /**
     * Get discount percentage for yearly billing
     */
    public function getYearlyDiscountPercentage()
    {
        if (!$this->price_monthly || !$this->price_yearly) {
            return 0;
        }

        $monthlyYearly = $this->price_monthly * 12;
        $discount = $monthlyYearly - $this->price_yearly;
        
        return round(($discount / $monthlyYearly) * 100);
    }

    /**
     * Check if plan is free
     */
    public function isFree()
    {
        return $this->price_monthly == 0 && $this->price_yearly == 0;
    }

    /**
     * Get plan statistics
     */
    public function getStatistics()
    {
        return [
            'total_tenants' => $this->tenants()->count(),
            'active_tenants' => $this->getActiveTenantsCount(),
            'monthly_revenue' => $this->getMonthlyRevenue(),
            'yearly_revenue' => $this->getYearlyRevenue(),
            'conversion_rate' => $this->tenants()->count() > 0 ? 
                round(($this->getActiveTenantsCount() / $this->tenants()->count()) * 100, 2) : 0
        ];
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for popular plans
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price_monthly');
    }

    /**
     * Get status attribute (compatibility with is_active)
     */
    public function getStatusAttribute()
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Set status attribute (compatibility with is_active)
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['is_active'] = in_array($value, ['active', true, 1]);
    }

    /**
     * Get type attribute (default to standard)
     */
    public function getTypeAttribute()
    {
        return $this->attributes['type'] ?? 'standard';
    }

    /**
     * Get creator relationship
     */
    public function creator()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'created_by');
    }

    /**
     * Get deleter relationship
     */
    public function deleter()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'deleted_by');
    }
}