<?php

/**
 * Database Setup Script for School Management System
 * 
 * This script helps set up the database with migrations and seeders
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database configuration
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'school_management_system',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "🚀 Setting up School Management System Database...\n\n";

try {
    // Test database connection
    $capsule->connection()->getPdo();
    echo "✅ Database connection successful!\n";
    
    // Check if tables exist
    $tables = [
        'users', 'schools', 'subjects', 'classes', 'students', 
        'teachers', 'teacher_subjects', 'class_subjects', 'attendance'
    ];
    
    $existingTables = [];
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            $existingTables[] = $table;
        }
    }
    
    if (!empty($existingTables)) {
        echo "⚠️  Found existing tables: " . implode(', ', $existingTables) . "\n";
        echo "📝 Please run Laravel migrations manually:\n";
        echo "   cd backend-school\n";
        echo "   php artisan migrate:fresh --seed\n\n";
    } else {
        echo "📝 No existing tables found. Please run:\n";
        echo "   cd backend-school\n";
        echo "   php artisan migrate --seed\n\n";
    }
    
    echo "🎓 Default Login Credentials:\n";
    echo "   SuperAdmin: superadmin@school.com / password\n";
    echo "   Admin: admin@demohigh.edu / password\n";
    echo "   Teacher: michael.brown@demohigh.edu / password\n";
    echo "   Student: alice.johnson@student.demohigh.edu / password\n\n";
    
    echo "🌐 API Endpoints:\n";
    echo "   Health Check: GET http://localhost:8000/api/health\n";
    echo "   Login: POST http://localhost:8000/api/auth/login\n";
    echo "   Dashboard: GET http://localhost:8000/api/dashboard\n\n";
    
    echo "✨ Setup complete! Start the Laravel server with:\n";
    echo "   cd backend-school && php artisan serve\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "📝 Please check your .env file configuration.\n";
    exit(1);
}