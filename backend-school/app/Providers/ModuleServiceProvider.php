<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        $this->registerModuleServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load module routes if auto-loading is disabled
        if (!config('modules.auto_load_routes', true)) {
            $this->loadModuleRoutes();
        }

        // Register module view paths
        $this->registerModuleViews();
        
        // Register module commands
        $this->registerModuleCommands();
    }

    /**
     * Register module-specific services
     */
    protected function registerModuleServices(): void
    {
        // Register IDCard Generator Service
        $this->app->singleton(\App\Modules\IDCard\Services\IDCardGenerator::class);
        
        // Register School Service
        $this->app->singleton(\App\Services\SchoolService::class);
        
        // Register Notification Service
        $this->app->singleton(\App\Services\NotificationService::class);
        
        // Register HR Services
        $this->app->singleton(\App\Modules\HR\Services\HRService::class);
        $this->app->singleton(\App\Modules\HR\Services\LeaveService::class);
        $this->app->singleton(\App\Modules\HR\Services\PayrollService::class);
    }

    /**
     * Load module routes manually
     */
    protected function loadModuleRoutes(): void
    {
        $modules = config('modules.modules', []);
        $defaultMiddleware = config('modules.default_middleware', ['api']);

        foreach ($modules as $moduleKey => $moduleConfig) {
            if ($moduleConfig['enabled'] ?? false) {
                $routeFile = $moduleConfig['routes'] ?? null;
                
                if ($routeFile && file_exists($routeFile)) {
                    Route::middleware($defaultMiddleware)->group($routeFile);
                }
            }
        }
    }

    /**
     * Register module view paths
     */
    protected function registerModuleViews(): void
    {
        // Register IDCard views
        $this->loadViewsFrom(
            app_path('Modules/IDCard/Views'),
            'idcard'
        );
    }

    /**
     * Register module commands
     */
    protected function registerModuleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add module-specific commands here
                // \App\Modules\School\Commands\SetupSchoolCommand::class,
            ]);
        }
    }

    /**
     * Get list of enabled modules
     */
    public function getEnabledModules(): array
    {
        $modules = config('modules.modules', []);
        
        return array_filter($modules, function ($module) {
            return $module['enabled'] ?? false;
        });
    }

    /**
     * Check if a module is enabled
     */
    public function isModuleEnabled(string $moduleName): bool
    {
        $modules = config('modules.modules', []);
        
        return $modules[$moduleName]['enabled'] ?? false;
    }
}