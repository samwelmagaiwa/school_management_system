#!/bin/bash

echo "🔍 Laravel Routes List"
echo "======================"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (backend-school)"
    exit 1
fi

echo "📋 All API Routes:"
echo "=================="
php artisan route:list --path=api

echo ""
echo "📋 Auth Routes Only:"
echo "===================="
php artisan route:list --path=api | grep auth

echo ""
echo "📋 Route Cache Status:"
echo "======================"
if [ -f "bootstrap/cache/routes-v7.php" ]; then
    echo "⚠️  Route cache exists - run 'php artisan route:clear' to clear"
else
    echo "✅ No route cache found"
fi

echo ""
echo "🔧 Useful Commands:"
echo "==================="
echo "Clear route cache: php artisan route:clear"
echo "Clear all cache: php artisan optimize:clear"
echo "Start server: php artisan serve"
echo "Check specific route: php artisan route:list --name=auth.login"