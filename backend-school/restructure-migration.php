<?php

/**
 * Laravel School Management System - Restructure Migration Script
 * 
 * This script helps migrate from modular structure to standard Laravel structure
 */

echo "🔄 Laravel School Management System - Restructure Migration\n";
echo "=========================================================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel root directory (backend-school)\n";
    exit(1);
}

// Create backup directory
$backupDir = 'modules_backup_' . date('Y_m_d_H_i_s');
echo "📦 Creating backup directory: {$backupDir}\n";

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Backup existing modular structure
if (is_dir('app/Modules')) {
    echo "💾 Backing up existing modular structure...\n";
    exec("cp -r app/Modules {$backupDir}/");
    echo "✅ Backup created successfully\n\n";
}

// Migration steps
$steps = [
    'Creating standard Laravel directories',
    'Moving models to app/Models',
    'Moving controllers to app/Http/Controllers/Api/V1',
    'Moving requests to app/Http/Requests',
    'Moving services to app/Services',
    'Consolidating migrations',
    'Updating namespaces',
    'Cleaning up old structure'
];

foreach ($steps as $index => $step) {
    echo "📋 Step " . ($index + 1) . ": {$step}\n";
    
    switch ($index) {
        case 0: // Creating directories
            $directories = [
                'app/Http/Controllers/Api',
                'app/Http/Controllers/Api/V1',
                'app/Models',
                'app/Http/Requests',
                'app/Http/Resources',
                'app/Services'
            ];
            
            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                    echo "  ✅ Created: {$dir}\n";
                }
            }
            break;
            
        case 1: // Moving models
            if (is_dir('app/Modules')) {
                $modules = glob('app/Modules/*', GLOB_ONLYDIR);
                foreach ($modules as $module) {
                    $modelsDir = $module . '/Models';
                    if (is_dir($modelsDir)) {
                        $models = glob($modelsDir . '/*.php');
                        foreach ($models as $model) {
                            $filename = basename($model);
                            $newPath = 'app/Models/' . $filename;
                            
                            if (!file_exists($newPath)) {
                                copy($model, $newPath);
                                echo "  ✅ Moved: {$filename} to app/Models/\n";
                                
                                // Update namespace in the file
                                $content = file_get_contents($newPath);
                                $content = preg_replace(
                                    '/namespace App\\\\Modules\\\\[^\\\\]+\\\\Models;/',
                                    'namespace App\\Models;',
                                    $content
                                );
                                file_put_contents($newPath, $content);
                            }
                        }
                    }
                }
            }
            break;
            
        case 2: // Moving controllers
            if (is_dir('app/Modules')) {
                $modules = glob('app/Modules/*', GLOB_ONLYDIR);
                foreach ($modules as $module) {
                    $controllersDir = $module . '/Controllers';
                    if (is_dir($controllersDir)) {
                        $controllers = glob($controllersDir . '/*.php');
                        foreach ($controllers as $controller) {
                            $filename = basename($controller);
                            $newPath = 'app/Http/Controllers/Api/V1/' . $filename;
                            
                            if (!file_exists($newPath)) {
                                copy($controller, $newPath);
                                echo "  ✅ Moved: {$filename} to app/Http/Controllers/Api/V1/\n";
                                
                                // Update namespace in the file
                                $content = file_get_contents($newPath);
                                $content = preg_replace(
                                    '/namespace App\\\\Modules\\\\[^\\\\]+\\\\Controllers;/',
                                    'namespace App\\Http\\Controllers\\Api\\V1;',
                                    $content
                                );
                                file_put_contents($newPath, $content);
                            }
                        }
                    }
                }
            }
            break;
            
        case 3: // Moving requests
            if (is_dir('app/Modules')) {
                $modules = glob('app/Modules/*', GLOB_ONLYDIR);
                foreach ($modules as $module) {
                    $requestsDir = $module . '/Requests';
                    if (is_dir($requestsDir)) {
                        $requests = glob($requestsDir . '/*.php');
                        foreach ($requests as $request) {
                            $filename = basename($request);
                            $newPath = 'app/Http/Requests/' . $filename;
                            
                            if (!file_exists($newPath)) {
                                copy($request, $newPath);
                                echo "  ✅ Moved: {$filename} to app/Http/Requests/\n";
                                
                                // Update namespace in the file
                                $content = file_get_contents($newPath);
                                $content = preg_replace(
                                    '/namespace App\\\\Modules\\\\[^\\\\]+\\\\Requests;/',
                                    'namespace App\\Http\\Requests;',
                                    $content
                                );
                                file_put_contents($newPath, $content);
                            }
                        }
                    }
                }
            }
            break;
            
        case 4: // Moving services
            if (is_dir('app/Modules')) {
                $modules = glob('app/Modules/*', GLOB_ONLYDIR);
                foreach ($modules as $module) {
                    $servicesDir = $module . '/Services';
                    if (is_dir($servicesDir)) {
                        $services = glob($servicesDir . '/*.php');
                        foreach ($services as $service) {
                            $filename = basename($service);
                            $newPath = 'app/Services/' . $filename;
                            
                            if (!file_exists($newPath)) {
                                copy($service, $newPath);
                                echo "  ✅ Moved: {$filename} to app/Services/\n";
                                
                                // Update namespace in the file
                                $content = file_get_contents($newPath);
                                $content = preg_replace(
                                    '/namespace App\\\\Modules\\\\[^\\\\]+\\\\Services;/',
                                    'namespace App\\Services;',
                                    $content
                                );
                                file_put_contents($newPath, $content);
                            }
                        }
                    }
                }
            }
            break;
            
        case 5: // Consolidating migrations
            if (is_dir('app/Modules')) {
                $modules = glob('app/Modules/*', GLOB_ONLYDIR);
                foreach ($modules as $module) {
                    $migrationsDir = $module . '/Database/Migrations';
                    if (is_dir($migrationsDir)) {
                        $migrations = glob($migrationsDir . '/*.php');
                        foreach ($migrations as $migration) {
                            $filename = basename($migration);
                            $newPath = 'database/migrations/' . $filename;
                            
                            if (!file_exists($newPath)) {
                                copy($migration, $newPath);
                                echo "  ✅ Moved: {$filename} to database/migrations/\n";
                            }
                        }
                    }
                }
            }
            break;
            
        case 6: // Updating namespaces
            echo "  🔄 Updating namespace references...\n";
            
            // Update all PHP files to use new namespaces
            $phpFiles = array_merge(
                glob('app/Models/*.php'),
                glob('app/Http/Controllers/Api/V1/*.php'),
                glob('app/Http/Requests/*.php'),
                glob('app/Services/*.php')
            );
            
            foreach ($phpFiles as $file) {
                $content = file_get_contents($file);
                $originalContent = $content;
                
                // Update use statements
                $content = preg_replace(
                    '/use App\\\\Modules\\\\([^\\\\]+)\\\\Models\\\\([^;]+);/',
                    'use App\\Models\\$2;',
                    $content
                );
                
                $content = preg_replace(
                    '/use App\\\\Modules\\\\([^\\\\]+)\\\\Controllers\\\\([^;]+);/',
                    'use App\\Http\\Controllers\\Api\\V1\\$2;',
                    $content
                );
                
                $content = preg_replace(
                    '/use App\\\\Modules\\\\([^\\\\]+)\\\\Requests\\\\([^;]+);/',
                    'use App\\Http\\Requests\\$2;',
                    $content
                );
                
                $content = preg_replace(
                    '/use App\\\\Modules\\\\([^\\\\]+)\\\\Services\\\\([^;]+);/',
                    'use App\\Services\\$2;',
                    $content
                );
                
                if ($content !== $originalContent) {
                    file_put_contents($file, $content);
                    echo "  ✅ Updated namespaces in: " . basename($file) . "\n";
                }
            }
            break;
            
        case 7: // Cleaning up
            echo "  🧹 Old modular structure preserved in backup\n";
            echo "  ℹ️  You can safely remove app/Modules after testing\n";
            break;
    }
    
    echo "\n";
}

echo "🎉 Migration completed successfully!\n\n";

echo "📋 Next Steps:\n";
echo "==============\n";
echo "1. Run: composer dump-autoload\n";
echo "2. Run: php artisan config:clear\n";
echo "3. Run: php artisan route:clear\n";
echo "4. Run: php artisan migrate:fresh --seed\n";
echo "5. Test your API endpoints\n";
echo "6. Update your frontend API calls to use /api/v1/ prefix\n\n";

echo "📚 Documentation:\n";
echo "==================\n";
echo "- See RESTRUCTURE_GUIDE.md for detailed documentation\n";
echo "- API endpoints now follow /api/v1/{resource} pattern\n";
echo "- All models are in App\\Models namespace\n";
echo "- All controllers are in App\\Http\\Controllers\\Api\\V1 namespace\n\n";

echo "⚠️  Important Notes:\n";
echo "====================\n";
echo "- Backup created in: {$backupDir}/\n";
echo "- Test all functionality before removing backup\n";
echo "- Update any hardcoded namespace references in your code\n";
echo "- Update frontend API base URL to include /v1\n\n";

echo "✨ Your Laravel application has been successfully restructured!\n";