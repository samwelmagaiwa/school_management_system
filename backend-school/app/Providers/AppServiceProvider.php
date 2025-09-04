<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module service providers
        $this->registerModuleServiceProviders();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL compatibility
        Schema::defaultStringLength(191);

        // Load migrations from all modules
        $this->loadModuleMigrations();
        
        // Load routes from all modules
        $this->loadModuleRoutes();
    }

    /**
     * Register module service providers automatically
     */
    protected function registerModuleServiceProviders(): void
    {
        $modulesPath = app_path('Modules');
        
        // Check if Modules directory exists
        if (!File::exists($modulesPath)) {
            return;
        }

        // Get all module directories
        $moduleDirectories = File::directories($modulesPath);

        foreach ($moduleDirectories as $moduleDirectory) {
            $moduleName = basename($moduleDirectory);
            $serviceProviderClass = "App\\Modules\\{$moduleName}\\{$moduleName}ServiceProvider";
            
            // Check if the service provider class exists
            if (class_exists($serviceProviderClass)) {
                $this->app->register($serviceProviderClass);
            }
        }
    }

    /**
     * Load migration files from all modules
     */
    protected function loadModuleMigrations(): void
    {
        $modulesPath = app_path('Modules');
        
        // Check if Modules directory exists
        if (!File::exists($modulesPath)) {
            return;
        }

        // Get all module directories
        $moduleDirectories = File::directories($modulesPath);

        foreach ($moduleDirectories as $moduleDirectory) {
            $migrationPath = $moduleDirectory . '/Database/Migrations';
            
            // Check if the module has a Database/Migrations directory
            if (File::exists($migrationPath) && File::isDirectory($migrationPath)) {
                // Load migrations from this module
                $this->loadMigrationsFrom($migrationPath);
                
                // Optional: Log which module migrations are being loaded (for debugging)
                // \Log::info("Loading migrations from: " . basename($moduleDirectory));
            }
        }
    }

    /**
     * Load routes from all modules
     */
    protected function loadModuleRoutes(): void
    {
        $modulesPath = app_path('Modules');
        
        // Check if Modules directory exists
        if (!File::exists($modulesPath)) {
            return;
        }

        // Get all module directories
        $moduleDirectories = File::directories($modulesPath);

        foreach ($moduleDirectories as $moduleDirectory) {
            $routesPath = $moduleDirectory . '/routes.php';
            
            // Check if the module has a routes.php file
            if (File::exists($routesPath)) {
                $this->loadRoutesFrom($routesPath);
            }
        }
    }
}