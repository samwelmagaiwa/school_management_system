#!/bin/bash

echo "🔧 School Management System - Authentication Fix Script"
echo "======================================================"

# Check if we're in the right directory
if [ ! -d "backend-school" ]; then
    echo "❌ Error: Please run this script from the project root directory"
    exit 1
fi

echo "📁 Navigating to backend directory..."
cd backend-school

echo "🧹 Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "🔄 Refreshing configuration..."
php artisan config:cache
php artisan route:cache

echo "📊 Checking database connection..."
php artisan migrate:status

echo "👤 Creating test user (if not exists)..."
php artisan tinker --execute="
try {
    \$user = App\Modules\User\Models\User::where('email', 'admin@example.com')->first();
    if (!\$user) {
        \$user = App\Modules\User\Models\User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'SuperAdmin',
            'status' => true
        ]);
        echo 'Test user created: admin@example.com / password';
    } else {
        echo 'Test user already exists: admin@example.com';
    }
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage();
}
"

echo "🌐 Testing API endpoints..."
echo "Testing health endpoint..."
curl -s http://localhost:8000/api/health | jq '.' || echo "❌ Health endpoint failed"

echo "Testing test endpoint..."
curl -s http://localhost:8000/api/test | jq '.' || echo "❌ Test endpoint failed"

echo "Testing database debug endpoint..."
curl -s http://localhost:8000/api/debug/database | jq '.' || echo "❌ Database debug failed"

echo ""
echo "✅ Fix script completed!"
echo ""
echo "📋 Next steps:"
echo "1. Start the backend server: php artisan serve"
echo "2. Test login with: admin@example.com / password"
echo "3. Check the frontend authentication"
echo ""
echo "🔍 If issues persist, check:"
echo "- Laravel logs: storage/logs/laravel.log"
echo "- Browser console for errors"
echo "- Network tab for API requests"
echo ""
echo "📖 See AUTHENTICATION_TROUBLESHOOTING.md for detailed guide"