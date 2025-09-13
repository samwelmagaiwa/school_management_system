<?php

/**
 * API Debug Script
 * 
 * This script helps debug API route issues
 */

echo "🔍 API Debug Script\n";
echo "===================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel root directory (backend-school)\n";
    exit(1);
}

echo "📋 Checking Laravel Setup...\n\n";

// Check 1: Laravel installation
if (file_exists('vendor/laravel/framework')) {
    echo "✅ Laravel framework installed\n";
} else {
    echo "❌ Laravel framework not found\n";
}

// Check 2: Environment file
if (file_exists('.env')) {
    echo "✅ .env file exists\n";
    
    $env = file_get_contents('.env');
    if (strpos($env, 'APP_KEY=base64:') !== false) {
        echo "✅ APP_KEY is properly set\n";
    } else {
        echo "⚠️  APP_KEY may not be set properly\n";
    }
} else {
    echo "❌ .env file missing\n";
}

// Check 3: Route cache
if (file_exists('bootstrap/cache/routes-v7.php')) {
    echo "⚠️  Route cache exists - may need clearing\n";
} else {
    echo "✅ No route cache found\n";
}

// Check 4: Config cache
if (file_exists('bootstrap/cache/config.php')) {
    echo "⚠️  Config cache exists - may need clearing\n";
} else {
    echo "✅ No config cache found\n";
}

// Check 5: Controllers
$controllers = [
    'app/Http/Controllers/Api/V1/AuthController.php',
    'app/Http/Controllers/Api/V1/StudentController.php',
    'app/Http/Controllers/Api/V1/DashboardController.php'
];

foreach ($controllers as $controller) {
    if (file_exists($controller)) {
        echo "✅ {$controller} exists\n";
    } else {
        echo "❌ {$controller} missing\n";
    }
}

// Check 6: Models
$models = [
    'app/Models/User.php',
    'app/Models/Student.php',
    'app/Models/School.php'
];

foreach ($models as $model) {
    if (file_exists($model)) {
        echo "✅ {$model} exists\n";
    } else {
        echo "❌ {$model} missing\n";
    }
}

// Check 7: Requests
$requests = [
    'app/Http/Requests/LoginRequest.php',
    'app/Http/Requests/RegisterRequest.php'
];

foreach ($requests as $request) {
    if (file_exists($request)) {
        echo "✅ {$request} exists\n";
    } else {
        echo "❌ {$request} missing\n";
    }
}

echo "\n🔧 Quick Fix Commands:\n";
echo "======================\n";
echo "1. Clear all caches:\n";
echo "   php artisan optimize:clear\n\n";
echo "2. Generate app key (if needed):\n";
echo "   php artisan key:generate\n\n";
echo "3. Install dependencies:\n";
echo "   composer install\n\n";
echo "4. Run migrations:\n";
echo "   php artisan migrate\n\n";
echo "5. Start server:\n";
echo "   php artisan serve\n\n";

echo "🌐 Test Commands:\n";
echo "=================\n";
echo "# Test health endpoint\n";
echo "curl http://localhost:8000/api/health\n\n";
echo "# Test routes info\n";
echo "curl http://localhost:8000/api/test\n\n";
echo "# Test login endpoint\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"email\":\"test@example.com\",\"password\":\"password\"}'\n\n";

echo "📝 Common Issues & Solutions:\n";
echo "=============================\n";
echo "1. Route not found:\n";
echo "   - Clear route cache: php artisan route:clear\n";
echo "   - Check syntax in routes/api.php\n";
echo "   - Ensure Laravel server is running\n\n";
echo "2. Controller not found:\n";
echo "   - Check namespace in controller file\n";
echo "   - Run: composer dump-autoload\n\n";
echo "3. Database errors:\n";
echo "   - Check .env database settings\n";
echo "   - Run: php artisan migrate\n\n";
echo "4. 500 Internal Server Error:\n";
echo "   - Check Laravel logs: storage/logs/laravel.log\n";
echo "   - Enable debug mode: APP_DEBUG=true in .env\n\n";

echo "🚀 If everything looks good, the API should work at:\n";
echo "====================================================\n";
echo "Base URL: http://localhost:8000/api\n";
echo "Login: POST /api/auth/login\n";
echo "Health: GET /api/health\n";
echo "Test: GET /api/test\n\n";