<?php

/**
 * SuperAdmin Routes Test Script
 * 
 * This script helps test if the SuperAdmin routes are properly configured
 */

echo "🔍 SuperAdmin Routes Test\n";
echo "=========================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel root directory (backend-school)\n";
    exit(1);
}

echo "📋 Testing SuperAdmin Route Configuration...\n\n";

// Test 1: Check if SuperAdminController exists
if (file_exists('app/Http/Controllers/Api/V1/SuperAdminController.php')) {
    echo "✅ SuperAdminController exists\n";
    
    $controllerContent = file_get_contents('app/Http/Controllers/Api/V1/SuperAdminController.php');
    if (strpos($controllerContent, "getAllUsers") !== false) {
        echo "✅ getAllUsers method found in SuperAdminController\n";
    } else {
        echo "❌ getAllUsers method not found in SuperAdminController\n";
    }
} else {
    echo "❌ SuperAdminController not found\n";
}

// Test 2: Check if routes are defined
if (file_exists('routes/api.php')) {
    echo "✅ routes/api.php exists\n";
    
    $routesContent = file_get_contents('routes/api.php');
    if (strpos($routesContent, "Route::prefix('superadmin')") !== false) {
        echo "✅ SuperAdmin routes found in routes/api.php\n";
    } else {
        echo "❌ SuperAdmin routes not found in routes/api.php\n";
    }
    
    if (strpos($routesContent, "SuperAdminController::class") !== false) {
        echo "✅ SuperAdminController reference found in routes\n";
    } else {
        echo "❌ SuperAdminController reference not found in routes\n";
    }
} else {
    echo "❌ routes/api.php not found\n";
}

// Test 3: Check if User model has SuperAdmin methods
if (file_exists('app/Models/User.php')) {
    echo "✅ User model exists\n";
    
    $userContent = file_get_contents('app/Models/User.php');
    if (strpos($userContent, "isSuperAdmin") !== false) {
        echo "✅ isSuperAdmin method found in User model\n";
    } else {
        echo "❌ isSuperAdmin method not found in User model\n";
    }
} else {
    echo "❌ User model not found\n";
}

echo "\n🌐 Available SuperAdmin Routes:\n";
echo "===============================\n";
echo "Legacy Routes (for backward compatibility):\n";
echo "GET    /api/superadmin/dashboard\n";
echo "GET    /api/superadmin/users\n";
echo "POST   /api/superadmin/users\n";
echo "PUT    /api/superadmin/users/{id}/status\n";
echo "GET    /api/superadmin/schools\n";
echo "POST   /api/superadmin/schools\n";
echo "PUT    /api/superadmin/schools/{id}\n";
echo "DELETE /api/superadmin/schools/{id}\n";
echo "GET    /api/superadmin/reports\n\n";

echo "V1 Routes (new versioned API):\n";
echo "GET    /api/v1/superadmin/dashboard\n";
echo "GET    /api/v1/superadmin/users\n";
echo "POST   /api/v1/superadmin/users\n";
echo "PUT    /api/v1/superadmin/users/{id}/status\n";
echo "GET    /api/v1/superadmin/schools\n";
echo "POST   /api/v1/superadmin/schools\n";
echo "PUT    /api/v1/superadmin/schools/{id}\n";
echo "DELETE /api/v1/superadmin/schools/{id}\n";
echo "GET    /api/v1/superadmin/reports\n\n";

echo "🔧 Test Commands:\n";
echo "=================\n";
echo "# Test route info\n";
echo "curl http://localhost:8000/api/test\n\n";
echo "# Test SuperAdmin users endpoint (requires authentication)\n";
echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "     http://localhost:8000/api/superadmin/users\n\n";
echo "# Test SuperAdmin dashboard\n";
echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "     http://localhost:8000/api/superadmin/dashboard\n\n";

echo "📝 Authentication Required:\n";
echo "===========================\n";
echo "All SuperAdmin routes require:\n";
echo "1. Valid authentication token (Bearer token)\n";
echo "2. User must have 'SuperAdmin' role\n";
echo "3. User status must be active\n\n";

echo "🚀 To test with authentication:\n";
echo "===============================\n";
echo "1. Login first: POST /api/auth/login\n";
echo "2. Get the token from login response\n";
echo "3. Use token in Authorization header\n";
echo "4. Ensure user has SuperAdmin role\n\n";

echo "✨ If routes still don't work, check:\n";
echo "=====================================\n";
echo "1. Laravel server is running: php artisan serve\n";
echo "2. Routes are cached: php artisan route:clear\n";
echo "3. Config is cached: php artisan config:clear\n";
echo "4. User has SuperAdmin role in database\n";
echo "5. Authentication token is valid\n\n";