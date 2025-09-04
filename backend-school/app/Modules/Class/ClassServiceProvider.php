<?php

namespace App\Modules\Class;

use Illuminate\Support\ServiceProvider;

class ClassServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        $this->app->singleton(\App\Modules\Class\Services\ClassService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        
        // Load views if any
        $this->loadViewsFrom(__DIR__ . '/Views', 'class');
    }
}