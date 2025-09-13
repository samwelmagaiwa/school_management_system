#!/bin/bash

echo "🔍 School Management System - Setup Checker"
echo "============================================"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the backend-school directory"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "⚠️  .env file not found. Creating from .env.example..."
    cp .env.example .env
    echo "✅ .env file created. Please update database credentials."
else
    echo "✅ .env file exists"
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "⚠️  Vendor directory not found. Running composer install..."
    composer install
else
    echo "✅ Composer dependencies installed"
fi

# Check if APP_KEY is set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "⚠️  APP_KEY not set. Generating..."
    php artisan key:generate
else
    echo "✅ APP_KEY is set"
fi

# Check database connection
echo "🔍 Checking database connection..."
php artisan migrate:status > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Database connection successful"
    
    # Check if tables exist
    TABLE_COUNT=$(php artisan migrate:status | grep -c "Y")
    if [ $TABLE_COUNT -gt 0 ]; then
        echo "✅ Database tables exist ($TABLE_COUNT migrations run)"
    else
        echo "⚠️  No migrations run. Running migrations..."
        php artisan migrate --seed
    fi
else
    echo "❌ Database connection failed"
    echo "📝 Please check your database configuration in .env file"
    echo "   - Ensure MySQL is running"
    echo "   - Verify database credentials"
    echo "   - Create database if it doesn't exist"
fi

# Check if storage is writable
if [ -w "storage" ]; then
    echo "✅ Storage directory is writable"
else
    echo "⚠️  Storage directory not writable. Fixing permissions..."
    chmod -R 775 storage bootstrap/cache
fi

# Test API endpoints
echo "🌐 Testing API endpoints..."
php artisan serve --host=127.0.0.1 --port=8000 &
SERVER_PID=$!
sleep 3

# Test health endpoint
HEALTH_RESPONSE=$(curl -s http://127.0.0.1:8000/api/health)
if [[ $HEALTH_RESPONSE == *"success"* ]]; then
    echo "✅ Health endpoint working"
else
    echo "❌ Health endpoint failed"
fi

# Stop the server
kill $SERVER_PID 2>/dev/null

echo ""
echo "🎯 Setup Summary:"
echo "=================="
echo "✅ Environment file: .env"
echo "✅ Dependencies: vendor/"
echo "✅ Application key: Generated"
echo "✅ Database: Check connection manually"
echo "✅ Permissions: storage/ and bootstrap/cache/"
echo ""
echo "🚀 To start the server:"
echo "   php artisan serve"
echo ""
echo "🔑 Default login credentials:"
echo "   SuperAdmin: superadmin@school.com / password"
echo "   Admin: admin@demohigh.edu / password"
echo ""
echo "📚 For detailed setup instructions, see DATABASE_SETUP.md"