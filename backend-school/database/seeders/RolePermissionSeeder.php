<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SuperAdmin\Models\Role;
use App\Modules\SuperAdmin\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create system permissions
            $this->createPermissions();
            
            // Create default roles
            $this->createDefaultRoles();
        });
    }

    /**
     * Create system permissions
     */
    private function createPermissions(): void
    {
        $modulePermissions = Role::getModulePermissions();
        
        foreach ($modulePermissions as $module => $permissions) {
            foreach ($permissions as $slug => $description) {
                Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $description,
                        'description' => $description,
                        'module' => $module,
                        'category' => $this->getCategoryFromModule($module),
                        'is_active' => true
                    ]
                );
            }
        }

        // Create SuperAdmin specific permissions
        $superAdminPermissions = Permission::getSuperAdminPermissions();
        
        foreach ($superAdminPermissions as $slug => $description) {
            $module = explode('.', $slug)[0];
            
            Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $description,
                    'description' => $description,
                    'module' => $module,
                    'category' => $this->getCategoryFromSlug($slug),
                    'is_active' => true
                ]
            );
        }
    }

    /**
     * Create default system roles
     */
    private function createDefaultRoles(): void
    {
        $defaultRoles = Role::getDefaultRoles();
        
        foreach ($defaultRoles as $slug => $roleData) {
            Role::updateOrCreate(
                ['slug' => $slug, 'tenant_id' => null],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_default' => true,
                    'is_system' => $roleData['is_system'],
                    'permissions' => $roleData['permissions'],
                    'module_access' => $roleData['module_access'],
                    'is_active' => true
                ]
            );
        }
    }

    /**
     * Get category from module name
     */
    private function getCategoryFromModule(string $module): string
    {
        $categoryMap = [
            'dashboard' => 'Core',
            'students' => 'Academic',
            'teachers' => 'Academic',
            'classes' => 'Academic',
            'subjects' => 'Academic',
            'attendance' => 'Academic',
            'exams' => 'Academic',
            'fees' => 'Financial',
            'reports' => 'Analytics',
            'settings' => 'System',
            'users' => 'User Management',
            'library' => 'Resources',
            'transport' => 'Operations',
            'hr' => 'Human Resources',
            'idcard' => 'Operations',
            'communication' => 'Communication'
        ];

        return $categoryMap[$module] ?? 'Other';
    }

    /**
     * Get category from permission slug
     */
    private function getCategoryFromSlug(string $slug): string
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
}