<?php

namespace App\Modules\Dashboard;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        $this->app->singleton(\App\Modules\Dashboard\Services\DashboardService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // Load migrations if any
        if (is_dir(__DIR__ . '/Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        }
        
        // Load views if any
        if (is_dir(__DIR__ . '/Views')) {
            $this->loadViewsFrom(__DIR__ . '/Views', 'dashboard');
        }
    }
}