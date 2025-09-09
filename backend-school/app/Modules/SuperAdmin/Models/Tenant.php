<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database_name',
        'status',
        'subscription_plan_id',
        'subscription_status',
        'subscription_expires_at',
        'billing_email',
        'billing_address',
        'billing_cycle',
        'auto_renew',
        'contact_person',
        'contact_email',
        'contact_phone',
        'settings',
        'features_enabled',
        'storage_used',
        'storage_limit',
        'users_limit',
        'is_trial',
        'trial_expires_at',
        'last_activity_at',
        'created_by',
        'approved_by',
        'approved_at',
        'suspended_at',
        'suspended_by',
        'suspension_reason',
        'reactivated_at',
        'reactivated_by',
        'deleted_by',
        'metadata'
    ];

    protected $casts = [
        'settings' => 'array',
        'features_enabled' => 'array',
        'metadata' => 'array',
        'storage_used' => 'integer',
        'storage_limit' => 'integer',
        'users_limit' => 'integer',
        'is_trial' => 'boolean',
        'auto_renew' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'trial_expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'approved_at' => 'datetime',
        'suspended_at' => 'datetime',
        'reactivated_at' => 'datetime'
    ];

    protected $dates = ['deleted_at'];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';

    // Subscription status constants
    const SUBSCRIPTION_ACTIVE = 'active';
    const SUBSCRIPTION_EXPIRED = 'expired';
    const SUBSCRIPTION_CANCELLED = 'cancelled';
    const SUBSCRIPTION_SUSPENDED = 'suspended';

    /**
     * Get the subscription plan for this tenant
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get all schools for this tenant
     */
    public function schools()
    {
        return $this->hasMany(School::class);
    }

    /**
     * Get all users for this tenant
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the user who created this tenant
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this tenant
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who suspended this tenant
     */
    public function suspender()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Get the user who reactivated this tenant
     */
    public function reactivator()
    {
        return $this->belongsTo(User::class, 'reactivated_by');
    }

    /**
     * Get the user who deleted this tenant
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get tenant statistics
     */
    public function getStatistics()
    {
        return [
            'total_schools' => $this->schools()->count(),
            'total_users' => $this->users()->count(),
            'active_users' => $this->users()->where('status', true)->count(),
            'total_students' => $this->users()->where('role', 'Student')->count(),
            'total_teachers' => $this->users()->where('role', 'Teacher')->count(),
            'storage_used_mb' => round($this->storage_used / 1024 / 1024, 2),
            'storage_limit_mb' => round($this->storage_limit / 1024 / 1024, 2),
            'storage_percentage' => $this->storage_limit > 0 ? round(($this->storage_used / $this->storage_limit) * 100, 2) : 0,
            'users_percentage' => $this->users_limit > 0 ? round(($this->users()->count() / $this->users_limit) * 100, 2) : 0,
            'days_until_expiry' => $this->subscription_expires_at ? now()->diffInDays($this->subscription_expires_at, false) : null,
            'is_trial_expired' => $this->is_trial && $this->trial_expires_at && $this->trial_expires_at->isPast(),
            'is_subscription_expired' => $this->subscription_expires_at && $this->subscription_expires_at->isPast()
        ];
    }

    /**
     * Check if tenant is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if tenant subscription is active
     */
    public function hasActiveSubscription()
    {
        return $this->subscription_status === self::SUBSCRIPTION_ACTIVE && 
               (!$this->subscription_expires_at || $this->subscription_expires_at->isFuture());
    }

    /**
     * Check if tenant is in trial
     */
    public function isInTrial()
    {
        return $this->is_trial && 
               (!$this->trial_expires_at || $this->trial_expires_at->isFuture());
    }

    /**
     * Check if feature is enabled
     */
    public function hasFeature($feature)
    {
        return in_array($feature, $this->features_enabled ?? []);
    }

    /**
     * Enable a feature
     */
    public function enableFeature($feature)
    {
        $features = $this->features_enabled ?? [];
        if (!in_array($feature, $features)) {
            $features[] = $feature;
            $this->update(['features_enabled' => $features]);
        }
    }

    /**
     * Disable a feature
     */
    public function disableFeature($feature)
    {
        $features = $this->features_enabled ?? [];
        $features = array_filter($features, function($f) use ($feature) {
            return $f !== $feature;
        });
        $this->update(['features_enabled' => array_values($features)]);
    }

    /**
     * Update last activity
     */
    public function updateLastActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Scope for active tenants
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for pending tenants
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for suspended tenants
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    /**
     * Scope for trial tenants
     */
    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpiredSubscriptions($query)
    {
        return $query->where('subscription_expires_at', '<', now());
    }

    /**
     * Scope for expiring soon (within 7 days)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->whereBetween('subscription_expires_at', [now(), now()->addDays(7)]);
    }
}