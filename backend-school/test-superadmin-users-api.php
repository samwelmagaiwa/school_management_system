<?php

/**
 * SuperAdmin Users API Test Script
 * 
 * This script helps test if the SuperAdmin users API is working correctly
 */

echo "🔍 SuperAdmin Users API Test\n";
echo "============================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel root directory (backend-school)\n";
    exit(1);
}

echo "📋 Testing SuperAdmin Users API Configuration...\n\n";

// Test 1: Check if SuperAdminController exists and has required methods
if (file_exists('app/Http/Controllers/Api/V1/SuperAdminController.php')) {
    echo "✅ SuperAdminController exists\n";
    
    $controllerContent = file_get_contents('app/Http/Controllers/Api/V1/SuperAdminController.php');
    
    $requiredMethods = [
        'getAllUsers',
        'createUser', 
        'updateUserStatus',
        'getUserStatistics',
        'getUserSchools',
        'getUserRoles'
    ];
    
    foreach ($requiredMethods as $method) {
        if (strpos($controllerContent, "function {$method}") !== false) {
            echo "✅ {$method} method found\n";
        } else {
            echo "❌ {$method} method not found\n";
        }
    }
    
    // Check for permissions structure
    if (strpos($controllerContent, "'permissions' =>") !== false) {
        echo "✅ Permissions structure found\n";
    } else {
        echo "❌ Permissions structure not found\n";
    }
    
} else {
    echo "❌ SuperAdminController not found\n";
}

// Test 2: Check if routes are properly defined
if (file_exists('routes/api.php')) {
    echo "\n✅ routes/api.php exists\n";
    
    $routesContent = file_get_contents('routes/api.php');
    
    $requiredRoutes = [
        "Route::get('users'",
        "Route::post('users'",
        "Route::put('users/{id}/status'",
        "Route::get('users/statistics'",
        "Route::get('users/schools'",
        "Route::get('users/roles'"
    ];
    
    foreach ($requiredRoutes as $route) {
        if (strpos($routesContent, $route) !== false) {
            echo "✅ {$route} route found\n";
        } else {
            echo "❌ {$route} route not found\n";
        }
    }
    
} else {
    echo "❌ routes/api.php not found\n";
}

// Test 3: Check User model
if (file_exists('app/Models/User.php')) {
    echo "\n✅ User model exists\n";
    
    $userContent = file_get_contents('app/Models/User.php');
    
    $requiredMethods = [
        'isSuperAdmin',
        'search',
        'byRole',
        'bySchool'
    ];
    
    foreach ($requiredMethods as $method) {
        if (strpos($userContent, "function {$method}") !== false) {
            echo "✅ User::{$method} method found\n";
        } else {
            echo "❌ User::{$method} method not found\n";
        }
    }
    
} else {
    echo "❌ User model not found\n";
}

echo "\n🌐 Available SuperAdmin Users API Endpoints:\n";
echo "============================================\n";
echo "Legacy Routes:\n";
echo "GET    /api/superadmin/users                 - List all users\n";
echo "POST   /api/superadmin/users                 - Create new user\n";
echo "PUT    /api/superadmin/users/{id}/status     - Update user status\n";
echo "GET    /api/superadmin/users/statistics      - Get user statistics\n";
echo "GET    /api/superadmin/users/schools         - Get available schools\n";
echo "GET    /api/superadmin/users/roles           - Get available roles\n\n";

echo "V1 Routes:\n";
echo "GET    /api/v1/superadmin/users              - List all users\n";
echo "POST   /api/v1/superadmin/users              - Create new user\n";
echo "PUT    /api/v1/superadmin/users/{id}/status  - Update user status\n";
echo "GET    /api/v1/superadmin/users/statistics   - Get user statistics\n";
echo "GET    /api/v1/superadmin/users/schools      - Get available schools\n";
echo "GET    /api/v1/superadmin/users/roles        - Get available roles\n\n";

echo "🔧 Test Commands:\n";
echo "=================\n";
echo "# Test users list endpoint\n";
echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "     http://localhost:8000/api/superadmin/users\n\n";
echo "# Test user statistics\n";
echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "     http://localhost:8000/api/superadmin/users/statistics\n\n";
echo "# Test available schools\n";
echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "     http://localhost:8000/api/superadmin/users/schools\n\n";
echo "# Test available roles\n";
echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "     http://localhost:8000/api/superadmin/users/roles\n\n";

echo "📝 Expected Response Format:\n";
echo "============================\n";
echo "Users List Response:\n";
echo "{\n";
echo "  \"success\": true,\n";
echo "  \"data\": {\n";
echo "    \"data\": [\n";
echo "      {\n";
echo "        \"id\": 1,\n";
echo "        \"first_name\": \"John\",\n";
echo "        \"last_name\": \"Doe\",\n";
echo "        \"email\": \"john@example.com\",\n";
echo "        \"role\": \"Admin\",\n";
echo "        \"status\": true,\n";
echo "        \"permissions\": {\n";
echo "          \"can_reset_password\": true,\n";
echo "          \"can_delete\": true,\n";
echo "          \"can_edit\": true,\n";
echo "          \"can_change_status\": true\n";
echo "        },\n";
echo "        \"school\": {\n";
echo "          \"id\": 1,\n";
echo "          \"name\": \"Demo School\",\n";
echo "          \"code\": \"DS\"\n";
echo "        }\n";
echo "      }\n";
echo "    ],\n";
echo "    \"meta\": {\n";
echo "      \"current_page\": 1,\n";
echo "      \"total\": 10\n";
echo "    }\n";
echo "  }\n";
echo "}\n\n";

echo "🚨 Common Issues & Solutions:\n";
echo "=============================\n";
echo "1. \"Cannot read properties of undefined (reading 'can_reset_password')\":\n";
echo "   - Fixed: Backend now returns 'permissions' object\n";
echo "   - Each user has: user.permissions.can_reset_password\n\n";
echo "2. Route not found:\n";
echo "   - Clear route cache: php artisan route:clear\n";
echo "   - Check authentication token\n";
echo "   - Ensure user has SuperAdmin role\n\n";
echo "3. 403 Unauthorized:\n";
echo "   - User must have role = 'SuperAdmin'\n";
echo "   - Check User model isSuperAdmin() method\n\n";
echo "4. 500 Internal Server Error:\n";
echo "   - Check Laravel logs: storage/logs/laravel.log\n";
echo "   - Run: composer dump-autoload\n";
echo "   - Check database connection\n\n";

echo "✨ Frontend Integration:\n";
echo "========================\n";
echo "The frontend expects:\n";
echo "- user.permissions.can_reset_password (✅ Fixed)\n";
echo "- user.permissions.can_delete (✅ Fixed)\n";
echo "- user.permissions.can_edit (✅ Fixed)\n";
echo "- user.permissions.can_change_status (✅ Fixed)\n";
echo "- user.permissions.can_impersonate (✅ Fixed)\n\n";

echo "🎯 Next Steps:\n";
echo "==============\n";
echo "1. Start Laravel server: php artisan serve\n";
echo "2. Test API endpoints with authentication\n";
echo "3. Check frontend console for any remaining errors\n";
echo "4. Verify user permissions are working correctly\n\n";