<?php

/**
 * SuperAdmin Module Migration Runner
 * 
 * This script helps run the SuperAdmin module migrations and seeders
 * Run this from the Laravel root directory: php run_superadmin_migrations.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "SuperAdmin Module Migration Runner\n";
echo "==================================\n\n";

try {
    // Check database connection
    echo "1. Checking database connection...\n";
    DB::connection()->getPdo();
    echo "   ✓ Database connection successful\n\n";
    
    // Check if tables exist
    echo "2. Checking existing tables...\n";
    $tables = [
        'tenants' => 'Tenants table',
        'subscription_plans' => 'Subscription Plans table',
        'system_settings' => 'System Settings table',
        'roles' => 'Roles table',
        'tenant_permissions' => 'Tenant Permissions table',
        'permissions' => 'Permissions table',
        'role_permissions' => 'Role Permissions table'
    ];
    
    $existingTables = [];
    $missingTables = [];
    
    foreach ($tables as $table => $description) {
        if (Schema::hasTable($table)) {
            $existingTables[] = $table;
            echo "   ✓ {$description} exists\n";
        } else {
            $missingTables[] = $table;
            echo "   ✗ {$description} missing\n";
        }
    }
    
    echo "\n";
    
    if (!empty($missingTables)) {
        echo "3. Running migrations for missing tables...\n";
        
        // Run specific migrations
        $migrationFiles = [
            '2024_01_01_000001_create_tenants_table.php',
            '2024_01_01_000002_create_subscription_plans_table.php',
            '2024_01_01_000003_create_system_settings_table.php',
            '2024_01_01_000004_create_roles_table.php',
            '2024_01_01_000005_create_tenant_permissions_table.php',
            '2024_01_01_000006_create_permissions_table.php',
            '2024_01_01_000007_create_role_permissions_table.php'
        ];
        
        foreach ($migrationFiles as $migration) {
            $migrationPath = "app/Modules/SuperAdmin/Database/Migrations/{$migration}";
            if (file_exists($migrationPath)) {
                echo "   Running {$migration}...\n";
                // Copy migration to database/migrations temporarily
                $tempPath = "database/migrations/{$migration}";
                copy($migrationPath, $tempPath);
                
                try {
                    Artisan::call('migrate', ['--path' => "database/migrations/{$migration}", '--force' => true]);
                    echo "   ✓ {$migration} completed\n";
                } catch (Exception $e) {
                    echo "   ✗ {$migration} failed: " . $e->getMessage() . "\n";
                }
                
                // Clean up temporary file
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        }
        echo "\n";
    } else {
        echo "3. All tables exist, skipping migrations\n\n";
    }
    
    // Check if roles exist
    echo "4. Checking default roles...\n";
    $roleCount = DB::table('roles')->where('is_system', true)->count();
    
    if ($roleCount == 0) {
        echo "   No default roles found, running seeder...\n";
        
        // Run the role seeder
        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder', '--force' => true]);
            echo "   ✓ Role seeder completed\n";
        } catch (Exception $e) {
            echo "   ✗ Role seeder failed: " . $e->getMessage() . "\n";
            
            // Manual role creation as fallback
            echo "   Attempting manual role creation...\n";
            createDefaultRolesManually();
        }
    } else {
        echo "   ✓ Found {$roleCount} system roles\n";
    }
    
    echo "\n";
    
    // Final verification
    echo "5. Final verification...\n";
    foreach ($tables as $table => $description) {
        if (Schema::hasTable($table)) {
            $count = DB::table($table)->count();
            echo "   ✓ {$description}: {$count} records\n";
        } else {
            echo "   ✗ {$description}: Table still missing\n";
        }
    }
    
    echo "\n✓ SuperAdmin module setup completed!\n";
    echo "\nYou can now access the role management endpoints:\n";
    echo "- GET /api/superadmin/roles\n";
    echo "- GET /api/superadmin/roles/statistics\n";
    echo "- GET /api/superadmin/permissions/modules\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

function createDefaultRolesManually()
{
    echo "   Creating default roles manually...\n";
    
    $defaultRoles = [
        [
            'name' => 'Super Administrator',
            'slug' => 'SuperAdmin',
            'description' => 'Full system access with tenant management capabilities',
            'is_default' => true,
            'is_system' => true,
            'tenant_id' => null,
            'permissions' => json_encode(['*']),
            'module_access' => json_encode(['*']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'name' => 'School Administrator',
            'slug' => 'Admin',
            'description' => 'Full access to school management features',
            'is_default' => true,
            'is_system' => true,
            'tenant_id' => null,
            'permissions' => json_encode([
                'dashboard.view', 'students.manage', 'teachers.manage', 'classes.manage',
                'subjects.manage', 'attendance.manage', 'exams.manage', 'fees.manage',
                'reports.view', 'settings.manage', 'users.manage', 'library.manage',
                'transport.manage', 'hr.manage', 'idcard.manage'
            ]),
            'module_access' => json_encode([
                'dashboard', 'students', 'teachers', 'classes', 'subjects',
                'attendance', 'exams', 'fees', 'reports', 'settings',
                'users', 'library', 'transport', 'hr', 'idcard'
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'name' => 'Teacher',
            'slug' => 'Teacher',
            'description' => 'Access to teaching and student management features',
            'is_default' => true,
            'is_system' => true,
            'tenant_id' => null,
            'permissions' => json_encode([
                'dashboard.view', 'students.view', 'students.attendance', 'classes.view',
                'subjects.view', 'attendance.manage', 'exams.manage', 'reports.view'
            ]),
            'module_access' => json_encode([
                'dashboard', 'students', 'classes', 'subjects', 'attendance', 'exams', 'reports'
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'name' => 'Student',
            'slug' => 'Student',
            'description' => 'Access to student portal features',
            'is_default' => true,
            'is_system' => true,
            'tenant_id' => null,
            'permissions' => json_encode([
                'dashboard.view', 'profile.view', 'attendance.view', 'exams.view',
                'results.view', 'library.view', 'transport.view'
            ]),
            'module_access' => json_encode([
                'dashboard', 'attendance', 'exams', 'library', 'transport'
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'name' => 'Parent/Guardian',
            'slug' => 'Parent',
            'description' => 'Access to monitor children\'s academic progress',
            'is_default' => true,
            'is_system' => true,
            'tenant_id' => null,
            'permissions' => json_encode([
                'dashboard.view', 'children.view', 'attendance.view', 'exams.view',
                'results.view', 'fees.view', 'communication.view'
            ]),
            'module_access' => json_encode([
                'dashboard', 'students', 'attendance', 'exams', 'fees'
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'name' => 'Accountant',
            'slug' => 'Accountant',
            'description' => 'Access to financial and fee management features',
            'is_default' => true,
            'is_system' => true,
            'tenant_id' => null,
            'permissions' => json_encode([
                'dashboard.view', 'fees.manage', 'reports.financial', 'students.view',
                'billing.manage', 'invoices.manage'
            ]),
            'module_access' => json_encode([
                'dashboard', 'fees', 'reports', 'students'
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]
    ];
    
    foreach ($defaultRoles as $role) {
        try {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug'], 'tenant_id' => null],
                $role
            );
            echo "   ✓ Created role: {$role['name']}\n";
        } catch (Exception $e) {
            echo "   ✗ Failed to create role {$role['name']}: " . $e->getMessage() . "\n";
        }
    }
}