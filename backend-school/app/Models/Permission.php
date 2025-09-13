<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'category',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    // Get all SuperAdmin permissions
    public static function getSuperAdminPermissions()
    {
        return [
            // Tenant Management
            'tenants.view' => 'View all schools/tenants',
            'tenants.create' => 'Create new schools/tenants',
            'tenants.edit' => 'Edit school/tenant details',
            'tenants.delete' => 'Delete schools/tenants',
            'tenants.approve' => 'Approve or deactivate schools',
            'tenants.billing' => 'Manage billing and subscriptions',
            'tenants.statistics' => 'View overall statistics across schools',
            
            // User Management
            'users.view_all' => 'View all users across tenants',
            'users.create_admin' => 'Create school admins',
            'users.assign_roles' => 'Assign roles to users',
            'users.reset_passwords' => 'Reset passwords for any user',
            'users.suspend' => 'Deactivate or suspend users',
            'users.bulk_actions' => 'Perform bulk operations on users',
            
            // Role & Permission Control
            'roles.define' => 'Define default roles',
            'roles.customize' => 'Customize permissions per tenant',
            'permissions.grant' => 'Grant access to modules',
            'permissions.revoke' => 'Revoke access to modules',
            'permissions.customize' => 'Customize tenant permissions',
            
            // System Configuration
            'system.global_settings' => 'Manage global settings',
            'system.themes' => 'Manage themes and branding',
            'system.academic_year' => 'Configure academic year defaults',
            'system.features' => 'Control available features',
            'system.languages' => 'Manage languages and localization',
            'system.timezones' => 'Configure timezone settings',
            
            // Monitoring & Reporting
            'reports.cross_tenant' => 'View reports across tenants',
            'reports.performance' => 'View student performance reports',
            'reports.financial' => 'View financial reports',
            'reports.staff_activity' => 'Track staff activities',
            'logs.activity' => 'Track user activity logs',
            'logs.audit' => 'View audit trails',
            'logs.security' => 'Monitor security events',
            
            // Data & Security
            'data.backup' => 'Backup tenant data',
            'data.restore' => 'Restore tenant data',
            'security.policies' => 'Enforce security policies',
            'security.2fa' => 'Manage 2FA settings',
            'security.encryption' => 'Manage data encryption',
            'integrations.manage' => 'Manage third-party integrations',
            'integrations.sms' => 'Configure SMS gateway',
            'integrations.email' => 'Configure email services',
            'integrations.payment' => 'Configure payment gateways',
            
            // Communication Control
            'communication.announcements' => 'Send system-wide announcements',
            'communication.sms_gateway' => 'Manage SMS gateway APIs',
            'communication.email_gateway' => 'Manage email gateway APIs',
            'communication.templates' => 'Manage communication templates',
            
            // Billing & Subscription
            'billing.plans' => 'Define pricing plans',
            'billing.monitor' => 'Monitor payments and invoices',
            'billing.invoices' => 'Generate and manage invoices',
            'billing.suspend' => 'Suspend tenants for billing issues',
            'billing.reports' => 'View billing reports',
            'subscriptions.manage' => 'Manage subscription lifecycle',
            
            // Module Management
            'modules.enable' => 'Enable/disable modules per tenant',
            'modules.configure' => 'Configure module settings',
            'modules.features' => 'Control feature availability',
            
            // Support & Maintenance
            'support.tickets' => 'Manage support tickets',
            'maintenance.database' => 'Perform database maintenance',
            'maintenance.cleanup' => 'Cleanup old data and logs',
            'maintenance.optimization' => 'System optimization tasks'
        ];
    }

    // Get permissions by category
    public static function getPermissionsByCategory()
    {
        $permissions = self::getSuperAdminPermissions();
        $categorized = [
            'Tenant Management' => [],
            'User Management' => [],
            'Role & Permission Control' => [],
            'System Configuration' => [],
            'Monitoring & Reporting' => [],
            'Data & Security' => [],
            'Communication Control' => [],
            'Billing & Subscription' => [],
            'Module Management' => [],
            'Support & Maintenance' => []
        ];

        foreach ($permissions as $slug => $description) {
            $category = self::getCategoryFromSlug($slug);
            $categorized[$category][] = [
                'slug' => $slug,
                'description' => $description
            ];
        }

        return $categorized;
    }

    private static function getCategoryFromSlug($slug)
    {
        $categoryMap = [
            'tenants' => 'Tenant Management',
            'users' => 'User Management',
            'roles' => 'Role & Permission Control',
            'permissions' => 'Role & Permission Control',
            'system' => 'System Configuration',
            'reports' => 'Monitoring & Reporting',
            'logs' => 'Monitoring & Reporting',
            'data' => 'Data & Security',
            'security' => 'Data & Security',
            'integrations' => 'Data & Security',
            'communication' => 'Communication Control',
            'billing' => 'Billing & Subscription',
            'subscriptions' => 'Billing & Subscription',
            'modules' => 'Module Management',
            'support' => 'Support & Maintenance',
            'maintenance' => 'Support & Maintenance'
        ];

        $prefix = explode('.', $slug)[0];
        return $categoryMap[$prefix] ?? 'Other';
    }

    // Check if permission exists
    public static function hasPermission($slug)
    {
        return array_key_exists($slug, self::getSuperAdminPermissions());
    }

    // Get all module permissions
    public static function getModulePermissions($module)
    {
        $allPermissions = self::getSuperAdminPermissions();
        return array_filter($allPermissions, function($key) use ($module) {
            return strpos($key, $module . '.') === 0;
        }, ARRAY_FILTER_USE_KEY);
    }
}
