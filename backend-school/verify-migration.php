<?php

/**
 * Laravel School Management System - Migration Verification Script
 * 
 * This script verifies that the migration from modular to standard Laravel structure was successful
 */

echo "🔍 Laravel School Management System - Migration Verification\n";
echo "===========================================================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel root directory (backend-school)\n";
    exit(1);
}

$errors = [];
$warnings = [];
$success = [];

// Check if modules directory was removed
if (is_dir('app/Modules')) {
    $errors[] = "Modules directory still exists at app/Modules";
} else {
    $success[] = "Modules directory successfully removed";
}

// Check standard Laravel directories exist
$requiredDirs = [
    'app/Http/Controllers/Api/V1',
    'app/Models',
    'app/Http/Requests',
    'app/Http/Resources',
    'app/Services',
    'database/migrations',
    'resources/views/id-cards'
];

foreach ($requiredDirs as $dir) {
    if (is_dir($dir)) {
        $success[] = "Directory exists: {$dir}";
    } else {
        $errors[] = "Missing directory: {$dir}";
    }
}

// Check critical files exist
$criticalFiles = [
    'app/Http/Controllers/Api/V1/AuthController.php',
    'app/Http/Controllers/Api/V1/StudentController.php',
    'app/Http/Controllers/Api/V1/QuickActionStudentController.php',
    'app/Models/User.php',
    'app/Models/Student.php',
    'app/Models/School.php',
    'app/Http/Requests/StoreStudentRequest.php',
    'app/Http/Requests/QuickAddStudentRequest.php',
    'app/Http/Resources/StudentResource.php',
    'app/Services/StudentService.php',
    'app/Services/QuickActionStudentService.php',
    'routes/api.php',
    'resources/views/id-cards/student_card.blade.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $success[] = "File exists: {$file}";
    } else {
        $errors[] = "Missing file: {$file}";
    }
}

// Check namespace updates in key files
$filesToCheck = [
    'app/Http/Controllers/Api/V1/StudentController.php' => 'App\\Http\\Controllers\\Api\\V1',
    'app/Models/Student.php' => 'App\\Models',
    'app/Http/Requests/StoreStudentRequest.php' => 'App\\Http\\Requests',
    'app/Services/StudentService.php' => 'App\\Services'
];

foreach ($filesToCheck as $file => $expectedNamespace) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, "namespace {$expectedNamespace}") !== false) {
            $success[] = "Correct namespace in: {$file}";
        } else {
            $errors[] = "Incorrect namespace in: {$file}";
        }
    }
}

// Check routes file for API versioning
if (file_exists('routes/api.php')) {
    $routesContent = file_get_contents('routes/api.php');
    if (strpos($routesContent, "Route::prefix('v1')") !== false) {
        $success[] = "API versioning found in routes/api.php";
    } else {
        $warnings[] = "API versioning not found in routes/api.php";
    }
    
    if (strpos($routesContent, 'QuickActionStudentController') !== false) {
        $success[] = "Quick Action routes found in routes/api.php";
    } else {
        $warnings[] = "Quick Action routes not found in routes/api.php";
    }
}

// Check migration files
$migrationFiles = glob('database/migrations/*.php');
if (count($migrationFiles) > 0) {
    $success[] = "Found " . count($migrationFiles) . " migration files";
} else {
    $errors[] = "No migration files found";
}

// Display results
echo "📊 Verification Results:\n";
echo "========================\n\n";

if (!empty($success)) {
    echo "✅ SUCCESS (" . count($success) . " items):\n";
    foreach ($success as $item) {
        echo "   ✓ {$item}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNINGS (" . count($warnings) . " items):\n";
    foreach ($warnings as $item) {
        echo "   ⚠ {$item}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS (" . count($errors) . " items):\n";
    foreach ($errors as $item) {
        echo "   ✗ {$item}\n";
    }
    echo "\n";
} else {
    echo "🎉 No errors found!\n\n";
}

// Final assessment
if (empty($errors)) {
    echo "🎯 MIGRATION STATUS: ✅ SUCCESSFUL\n";
    echo "===================================\n";
    echo "✨ The migration from modular to standard Laravel structure is complete!\n\n";
    
    echo "📋 Next Steps:\n";
    echo "1. Run: composer dump-autoload\n";
    echo "2. Run: php artisan config:clear\n";
    echo "3. Run: php artisan route:clear\n";
    echo "4. Run: php artisan migrate:fresh --seed\n";
    echo "5. Test API endpoints: curl http://localhost:8000/api/health\n";
    echo "6. Update frontend to use /api/v1/ endpoints\n\n";
    
    echo "🔗 New API Structure:\n";
    echo "- Authentication: /api/v1/auth/*\n";
    echo "- Students: /api/v1/students/*\n";
    echo "- Quick Actions: /api/v1/students/quick-actions/*\n";
    echo "- Teachers: /api/v1/teachers/*\n";
    echo "- All other resources: /api/v1/{resource}/*\n\n";
    
} else {
    echo "🚨 MIGRATION STATUS: ❌ INCOMPLETE\n";
    echo "===================================\n";
    echo "Please fix the errors above before proceeding.\n\n";
}

echo "📄 Documentation:\n";
echo "- See MODULES_MIGRATION_SUMMARY.md for detailed migration report\n";
echo "- See RESTRUCTURE_GUIDE.md for complete documentation\n\n";

echo "🔧 Troubleshooting:\n";
echo "- If you encounter namespace errors, run: composer dump-autoload\n";
echo "- If routes don't work, clear caches: php artisan optimize:clear\n";
echo "- If database issues, check .env configuration\n\n";

exit(empty($errors) ? 0 : 1);