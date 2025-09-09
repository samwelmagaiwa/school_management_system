<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\SuperAdmin\Models\Tenant;
use App\Modules\SuperAdmin\Models\SubscriptionPlan;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class TenantService
{
    /**
     * Get all tenants with filters and pagination
     */
    public function getAllTenants(array $filters = [], int $perPage = 15)
    {
        try {
            $query = Tenant::with(['subscriptionPlan', 'creator', 'approver']);

            // Apply filters
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('domain', 'like', "%{$search}%")
                      ->orWhere('contact_email', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%");
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['subscription_status'])) {
                $query->where('subscription_status', $filters['subscription_status']);
            }

            if (!empty($filters['subscription_plan_id'])) {
                $query->where('subscription_plan_id', $filters['subscription_plan_id']);
            }

            if (isset($filters['is_trial']) && $filters['is_trial'] !== '') {
                $query->where('is_trial', $filters['is_trial'] === 'true');
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
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
     * Create a new tenant
     */
    public function createTenant(array $data): Tenant
    {
        DB::beginTransaction();
        
        try {
            // Create tenant
            $tenant = Tenant::create([
                'name' => $data['name'],
                'domain' => $data['domain'] ?? null,
                'status' => 'pending',
                'subscription_status' => 'active',
                'contact_person' => $data['contact_person'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'subscription_plan_id' => $data['subscription_plan_id'] ?? null,
                'users_limit' => $data['users_limit'] ?? 100,
                'storage_limit' => isset($data['storage_limit_gb']) ? $data['storage_limit_gb'] * 1024 * 1024 * 1024 : 5 * 1024 * 1024 * 1024,
                'storage_used' => 0,
                'is_trial' => $data['is_trial'] ?? false,
                'trial_expires_at' => $data['is_trial'] ? now()->addDays($data['trial_days'] ?? 14) : null,
                'subscription_expires_at' => $data['subscription_plan_id'] ? now()->addMonth() : null,
                'features_enabled' => $data['features_enabled'] ?? [],
                'created_by' => auth()->id(),
                'metadata' => [
                    'created_from' => 'superadmin_panel',
                    'initial_setup' => true
                ]
            ]);

            // Create default admin user for the tenant if needed
            if (!empty($data['create_admin_user'])) {
                $this->createTenantAdminUser($tenant, $data);
            }

            DB::commit();
            
            return $tenant->load(['subscriptionPlan', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get tenant details
     */
    public function getTenantDetails(Tenant $tenant): array
    {
        $tenant->load(['subscriptionPlan', 'creator', 'approver']);
        
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'status' => $tenant->status,
            'subscription_status' => $tenant->subscription_status,
            'contact_person' => $tenant->contact_person,
            'contact_email' => $tenant->contact_email,
            'contact_phone' => $tenant->contact_phone,
            'billing_address' => $tenant->billing_address,
            'subscription_plan' => $tenant->subscriptionPlan ? [
                'id' => $tenant->subscriptionPlan->id,
                'name' => $tenant->subscriptionPlan->name,
                'price_monthly' => $tenant->subscriptionPlan->price_monthly,
                'price_yearly' => $tenant->subscriptionPlan->price_yearly,
            ] : null,
            'users_limit' => $tenant->users_limit,
            'users_count' => $this->getTenantUsersCount($tenant),
            'storage_limit' => $tenant->storage_limit,
            'storage_used' => $tenant->storage_used,
            'is_trial' => $tenant->is_trial,
            'trial_expires_at' => $tenant->trial_expires_at,
            'subscription_expires_at' => $tenant->subscription_expires_at,
            'features_enabled' => $tenant->features_enabled,
            'created_at' => $tenant->created_at,
            'updated_at' => $tenant->updated_at,
            'creator' => $tenant->creator ? [
                'id' => $tenant->creator->id,
                'full_name' => $tenant->creator->full_name,
                'email' => $tenant->creator->email
            ] : null,
            'approver' => $tenant->approver ? [
                'id' => $tenant->approver->id,
                'full_name' => $tenant->approver->full_name,
                'email' => $tenant->approver->email
            ] : null,
            'approved_at' => $tenant->approved_at,
            'last_activity_at' => $tenant->last_activity_at,
            'metadata' => $tenant->metadata
        ];
    }

    /**
     * Update tenant
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        DB::beginTransaction();
        
        try {
            $updateData = [];
            
            // Basic information
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['domain'])) $updateData['domain'] = $data['domain'];
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['subscription_status'])) $updateData['subscription_status'] = $data['subscription_status'];
            if (isset($data['contact_person'])) $updateData['contact_person'] = $data['contact_person'];
            if (isset($data['contact_email'])) $updateData['contact_email'] = $data['contact_email'];
            if (isset($data['contact_phone'])) $updateData['contact_phone'] = $data['contact_phone'];
            if (isset($data['billing_address'])) $updateData['billing_address'] = $data['billing_address'];
            
            // Subscription and limits
            if (isset($data['subscription_plan_id'])) $updateData['subscription_plan_id'] = $data['subscription_plan_id'];
            if (isset($data['users_limit'])) $updateData['users_limit'] = $data['users_limit'];
            if (isset($data['storage_limit_gb'])) {
                $updateData['storage_limit'] = $data['storage_limit_gb'] * 1024 * 1024 * 1024;
            }
            
            // Trial settings
            if (isset($data['is_trial'])) {
                $updateData['is_trial'] = $data['is_trial'];
                if ($data['is_trial'] && isset($data['trial_expires_at'])) {
                    $updateData['trial_expires_at'] = $data['trial_expires_at'];
                } elseif (!$data['is_trial']) {
                    $updateData['trial_expires_at'] = null;
                }
            }
            
            // Subscription expiration
            if (isset($data['subscription_expires_at'])) {
                $updateData['subscription_expires_at'] = $data['subscription_expires_at'];
            }
            
            // Features
            if (isset($data['features_enabled'])) {
                $updateData['features_enabled'] = $data['features_enabled'];
            }
            
            $tenant->update($updateData);
            
            DB::commit();
            
            return $tenant->load(['subscriptionPlan', 'creator', 'approver']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete tenant
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        DB::beginTransaction();
        
        try {
            // Here you would typically:
            // 1. Backup tenant data
            // 2. Delete related data (users, schools, etc.)
            // 3. Clean up files and storage
            // 4. Delete the tenant record
            
            // For now, just soft delete or mark as deleted
            $tenant->update([
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
     * Approve tenant
     */
    public function approveTenant(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => auth()->id()
        ]);
        
        return $tenant->load(['subscriptionPlan', 'creator', 'approver']);
    }

    /**
     * Suspend tenant
     */
    public function suspendTenant(Tenant $tenant, string $reason): Tenant
    {
        $tenant->update([
            'status' => 'suspended',
            'suspension_reason' => $reason,
            'suspended_at' => now(),
            'suspended_by' => auth()->id()
        ]);
        
        return $tenant->load(['subscriptionPlan', 'creator', 'approver']);
    }

    /**
     * Reactivate tenant
     */
    public function reactivateTenant(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => 'active',
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
            'reactivated_at' => now(),
            'reactivated_by' => auth()->id()
        ]);
        
        return $tenant->load(['subscriptionPlan', 'creator', 'approver']);
    }

    /**
     * Update tenant subscription
     */
    public function updateSubscription(Tenant $tenant, array $data): Tenant
    {
        $updateData = [
            'subscription_plan_id' => $data['subscription_plan_id'],
            'billing_cycle' => $data['billing_cycle'],
            'subscription_status' => 'active'
        ];
        
        if (isset($data['start_date'])) {
            $startDate = Carbon::parse($data['start_date']);
        } else {
            $startDate = now();
        }
        
        // Calculate expiration based on billing cycle
        if ($data['billing_cycle'] === 'yearly') {
            $updateData['subscription_expires_at'] = $startDate->addYear();
        } else {
            $updateData['subscription_expires_at'] = $startDate->addMonth();
        }
        
        if (isset($data['auto_renew'])) {
            $updateData['auto_renew'] = $data['auto_renew'];
        }
        
        $tenant->update($updateData);
        
        return $tenant->load(['subscriptionPlan']);
    }

    /**
     * Update tenant features
     */
    public function updateFeatures(Tenant $tenant, array $features): Tenant
    {
        $tenant->update([
            'features_enabled' => $features
        ]);
        
        return $tenant->load(['subscriptionPlan']);
    }

    /**
     * Get tenant statistics
     */
    public function getTenantStatistics(Tenant $tenant): array
    {
        return [
            'users_count' => $this->getTenantUsersCount($tenant),
            'storage_used' => $tenant->storage_used,
            'storage_limit' => $tenant->storage_limit,
            'storage_percentage' => $tenant->storage_limit > 0 ? ($tenant->storage_used / $tenant->storage_limit) * 100 : 0,
            'last_activity' => $tenant->last_activity_at,
            'subscription_days_remaining' => $tenant->subscription_expires_at ? 
                now()->diffInDays($tenant->subscription_expires_at, false) : null,
            'trial_days_remaining' => $tenant->is_trial && $tenant->trial_expires_at ? 
                now()->diffInDays($tenant->trial_expires_at, false) : null,
        ];
    }

    /**
     * Get tenant activity logs
     */
    public function getTenantActivityLogs(Tenant $tenant, array $filters = [], int $perPage = 15)
    {
        // This would typically query an activity_logs table
        // For now, return sample data
        return [
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0
            ]
        ];
    }

    /**
     * Backup tenant data
     */
    public function backupTenantData(Tenant $tenant): array
    {
        // This would create a backup of all tenant data
        // For now, return a placeholder
        return [
            'backup_id' => uniqid('backup_'),
            'tenant_id' => $tenant->id,
            'backup_file' => "tenant_{$tenant->id}_backup_" . now()->format('Y-m-d_H-i-s') . '.zip',
            'size' => 0,
            'created_at' => now(),
            'status' => 'completed'
        ];
    }

    /**
     * Restore tenant data
     */
    public function restoreTenantData(Tenant $tenant, string $backupFile, array $options = []): array
    {
        // This would restore tenant data from backup
        // For now, return a placeholder
        return [
            'restore_id' => uniqid('restore_'),
            'tenant_id' => $tenant->id,
            'backup_file' => $backupFile,
            'restored_at' => now(),
            'status' => 'completed'
        ];
    }

    /**
     * Get tenant billing information
     */
    public function getTenantBilling(Tenant $tenant): array
    {
        return [
            'subscription_plan' => $tenant->subscriptionPlan,
            'billing_cycle' => $tenant->billing_cycle ?? 'monthly',
            'next_billing_date' => $tenant->subscription_expires_at,
            'auto_renew' => $tenant->auto_renew ?? true,
            'billing_address' => $tenant->billing_address,
            'payment_method' => null, // Would come from payment provider
            'invoices' => [], // Would come from invoices table
            'payments' => [] // Would come from payments table
        ];
    }

    /**
     * Generate invoice for tenant
     */
    public function generateInvoice(Tenant $tenant, array $data): array
    {
        // This would generate an actual invoice
        // For now, return a placeholder
        return [
            'invoice_id' => uniqid('inv_'),
            'tenant_id' => $tenant->id,
            'billing_period' => $data['billing_period'],
            'due_date' => $data['due_date'],
            'amount' => $tenant->subscriptionPlan->price_monthly ?? 0,
            'status' => 'pending',
            'generated_at' => now()
        ];
    }

    /**
     * Get tenants overview for dashboard
     */
    public function getTenantsOverview(): array
    {
        try {
            $total = Tenant::count();
            $active = Tenant::where('status', 'active')->count();
            $pending = Tenant::where('status', 'pending')->count();
            $suspended = Tenant::where('status', 'suspended')->count();
            $trial = Tenant::where('is_trial', true)->count();
            
            // Calculate monthly revenue
            $monthlyRevenue = Tenant::whereHas('subscriptionPlan')
                ->where('subscription_status', 'active')
                ->with('subscriptionPlan')
                ->get()
                ->sum(function ($tenant) {
                    return $tenant->subscriptionPlan->price_monthly ?? 0;
                });

            return [
                'total' => $total,
                'active' => $active,
                'pending' => $pending,
                'suspended' => $suspended,
                'trial' => $trial,
                'monthly_revenue' => $monthlyRevenue,
                'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'pending' => 0,
                'suspended' => 0,
                'trial' => 0,
                'monthly_revenue' => 0,
                'active_percentage' => 0
            ];
        }
    }

    /**
     * Create admin user for tenant
     */
    private function createTenantAdminUser(Tenant $tenant, array $data): User
    {
        return User::create([
            'first_name' => $data['admin_first_name'] ?? 'Admin',
            'last_name' => $data['admin_last_name'] ?? 'User',
            'email' => $data['admin_email'] ?? $tenant->contact_email,
            'password' => Hash::make($data['admin_password'] ?? 'password123'),
            'role' => 'Admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'email_verified_at' => now()
        ]);
    }

    /**
     * Get tenant users count
     */
    private function getTenantUsersCount(Tenant $tenant): int
    {
        try {
            return User::where('tenant_id', $tenant->id)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}