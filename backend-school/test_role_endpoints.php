<?php

/**
 * Role Management API Test Script
 * 
 * This script tests the role management endpoints to help debug issues
 * Run this from the Laravel root directory: php test_role_endpoints.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\SuperAdmin\Models\Role;
use App\Modules\SuperAdmin\Models\Permission;
use App\Modules\SuperAdmin\Models\TenantPermission;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Role Management API Test Script\n";
echo "===============================\n\n";

try {
    // Test 1: Database Connection
    echo "1. Testing database connection...\n";
    DB::connection()->getPdo();
    echo "   ✓ Database connection successful\n\n";
    
    // Test 2: Check if tables exist
    echo "2. Checking required tables...\n";
    $requiredTables = ['roles', 'permissions', 'tenant_permissions'];
    $allTablesExist = true;
    
    foreach ($requiredTables as $table) {
        if (Schema::hasTable($table)) {
            $count = DB::table($table)->count();
            echo "   ✓ Table '{$table}' exists with {$count} records\n";
        } else {
            echo "   ✗ Table '{$table}' does not exist\n";
            $allTablesExist = false;
        }
    }
    
    if (!$allTablesExist) {
        echo "\n   ERROR: Required tables are missing. Please run migrations first.\n";
        echo "   You can use: php run_superadmin_migrations.php\n\n";
        exit(1);
    }
    
    echo "\n";
    
    // Test 3: Test Role Model
    echo "3. Testing Role model...\n";
    try {
        $roles = Role::all();
        echo "   ✓ Role model works, found " . $roles->count() . " roles\n";
        
        foreach ($roles as $role) {
            echo "     - {$role->name} ({$role->slug})\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Role model error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 4: Test Role Controller Methods
    echo "4. Testing Role Controller methods...\n";
    
    try {
        // Test getRoles method
        $controller = new \App\Modules\SuperAdmin\Controllers\RolePermissionController();
        $request = new \Illuminate\Http\Request();\n        
        echo "   Testing getRoles()...\n";
        $response = $controller->getRoles($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success']) {
            echo "   ✓ getRoles() works, returned " . count($responseData['data']) . " roles\n";
        } else {
            echo "   ✗ getRoles() failed: " . $responseData['message'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ✗ Controller test error: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n";
    
    // Test 5: Test Role Statistics
    echo "5. Testing Role Statistics...\n";
    
    try {
        $controller = new \App\Modules\SuperAdmin\Controllers\RolePermissionController();
        $request = new \Illuminate\Http\Request();\n        
        echo "   Testing getRoleStatistics()...\n";
        $response = $controller->getRoleStatistics($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success']) {
            echo "   ✓ getRoleStatistics() works\n";
            echo "     - Total roles: " . $responseData['data']['total_roles'] . "\n";
            echo "     - System roles: " . $responseData['data']['system_roles'] . "\n";
            echo "     - Custom roles: " . $responseData['data']['custom_roles'] . "\n";
        } else {
            echo "   ✗ getRoleStatistics() failed: " . $responseData['message'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ✗ Statistics test error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 6: Test Modules and Permissions
    echo "6. Testing Modules and Permissions...\n";
    
    try {
        $controller = new \App\Modules\SuperAdmin\Controllers\RolePermissionController();
        
        echo "   Testing getModulesAndPermissions()...\n";
        $response = $controller->getModulesAndPermissions();
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success']) {
            echo "   ✓ getModulesAndPermissions() works\n";
            echo "     - Available modules: " . count($responseData['data']['modules']) . "\n";
            echo "     - Permission groups: " . count($responseData['data']['permissions_by_module']) . "\n";
            echo "     - Total permissions: " . count($responseData['data']['all_permissions']) . "\n";
        } else {
            echo "   ✗ getModulesAndPermissions() failed: " . $responseData['message'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ✗ Modules test error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 7: Create sample data if needed
    echo "7. Checking sample data...\n";
    
    $roleCount = Role::count();
    if ($roleCount == 0) {
        echo "   No roles found, creating default roles...\n";
        try {
            $roles = Role::createDefaultRoles();
            echo "   ✓ Created " . count($roles) . " default roles\n";
        } catch (Exception $e) {
            echo "   ✗ Failed to create default roles: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✓ Found {$roleCount} existing roles\n";
    }
    
    echo "\n";
    
    // Test 8: Final API simulation
    echo "8. Simulating API calls...\n";
    
    // Simulate the exact API calls that are failing
    $testUrls = [
        '/api/superadmin/roles',
        '/api/superadmin/roles/statistics',
        '/api/superadmin/permissions/modules'
    ];
    
    foreach ($testUrls as $url) {
        echo "   Testing {$url}...\n";
        
        try {
            // Create a mock request
            $request = \Illuminate\Http\Request::create($url, 'GET');
            $controller = new \App\Modules\SuperAdmin\Controllers\RolePermissionController();
            
            switch ($url) {
                case '/api/superadmin/roles':
                    $response = $controller->getRoles($request);
                    break;
                case '/api/superadmin/roles/statistics':
                    $response = $controller->getRoleStatistics($request);
                    break;
                case '/api/superadmin/permissions/modules':
                    $response = $controller->getModulesAndPermissions();
                    break;
            }
            
            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getContent(), true);
            
            if ($statusCode == 200 && $content['success']) {
                echo "     ✓ {$url} - Status: {$statusCode}, Success: true\n";
            } else {
                echo "     ✗ {$url} - Status: {$statusCode}, Success: " . ($content['success'] ? 'true' : 'false') . "\n";
                if (isset($content['message'])) {
                    echo "       Message: " . $content['message'] . "\n";
                }
                if (isset($content['error'])) {
                    echo "       Error: " . $content['error'] . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "     ✗ {$url} - Exception: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Role Management API testing completed!\n";
    echo "\nIf all tests passed, the role management endpoints should work.\n";
    echo "If there are still 500 errors, check:\n";
    echo "1. Laravel logs in storage/logs/\n";
    echo "2. Web server error logs\n";
    echo "3. Database connection settings\n";
    echo "4. Route caching (php artisan route:clear)\n";
    
} catch (Exception $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}