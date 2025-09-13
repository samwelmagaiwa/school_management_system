<?php

/**
 * Laravel Routes Test Script
 * 
 * This script helps test if the routes are properly configured
 */

echo "🔍 Laravel Routes Test\n";
echo "======================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel root directory (backend-school)\n";
    exit(1);
}

echo "📋 Testing Route Configuration...\n\n";

// Test 1: Check if routes file exists and is readable
if (file_exists('routes/api.php')) {
    echo "✅ routes/api.php exists\n";
    
    $routesContent = file_get_contents('routes/api.php');
    if (strpos($routesContent, "Route::post('login'") !== false) {
        echo "✅ Login route found in routes/api.php\n";
    } else {
        echo "❌ Login route not found in routes/api.php\n";
    }
} else {
    echo "❌ routes/api.php not found\n";
}

// Test 2: Check if AuthController exists
if (file_exists('app/Http/Controllers/Api/V1/AuthController.php')) {
    echo "✅ AuthController exists\n";
} else {
    echo "❌ AuthController not found\n";
}

// Test 3: Check if LoginRequest exists
if (file_exists('app/Http/Requests/LoginRequest.php')) {
    echo "✅ LoginRequest exists\n";
} else {
    echo "❌ LoginRequest not found\n";
}

// Test 4: Check if User model exists
if (file_exists('app/Models/User.php')) {
    echo "✅ User model exists\n";
} else {
    echo "❌ User model not found\n";
}

// Test 5: Check Laravel configuration
echo "\n📋 Laravel Configuration Check...\n\n";

// Check if .env exists
if (file_exists('.env')) {
    echo "✅ .env file exists\n";
    
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'APP_KEY=') !== false) {
        echo "✅ APP_KEY is set\n";
    } else {
        echo "❌ APP_KEY not set\n";
    }
    
    if (strpos($envContent, 'DB_DATABASE=') !== false) {
        echo "✅ Database configuration found\n";
    } else {
        echo "❌ Database configuration not found\n";
    }
} else {
    echo "❌ .env file not found\n";
}

// Test 6: Check vendor directory
if (is_dir('vendor')) {
    echo "✅ Vendor directory exists\n";
} else {
    echo "❌ Vendor directory not found - run 'composer install'\n";
}

// Test 7: Check if Laravel Sanctum is installed
if (file_exists('vendor/laravel/sanctum')) {
    echo "✅ Laravel Sanctum is installed\n";
} else {
    echo "❌ Laravel Sanctum not found\n";
}

echo "\n🔧 Suggested Commands to Fix Issues:\n";
echo "=====================================\n";
echo "1. composer install\n";
echo "2. cp .env.example .env (if .env doesn't exist)\n";
echo "3. php artisan key:generate\n";
echo "4. php artisan config:clear\n";
echo "5. php artisan route:clear\n";
echo "6. php artisan cache:clear\n";
echo "7. php artisan migrate\n";
echo "8. php artisan serve\n\n";

echo "🌐 Test URLs:\n";
echo "=============\n";
echo "Health Check: http://localhost:8000/api/health\n";
echo "Test Route: http://localhost:8000/api/test\n";
echo "Login (Legacy): http://localhost:8000/api/auth/login\n";
echo "Login (V1): http://localhost:8000/api/v1/auth/login\n\n";

echo "📝 Sample Login Request:\n";
echo "========================\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}'\n\n";

echo "✨ If routes still don't work, check:\n";
echo "=====================================\n";
echo "1. Laravel server is running: php artisan serve\n";
echo "2. Database is connected and migrated\n";
echo "3. No syntax errors in routes/api.php\n";
echo "4. Correct namespace imports in routes file\n";
echo "5. Laravel cache is cleared\n\n";