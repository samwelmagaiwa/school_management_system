<?php

namespace App\Modules\HR;

use Illuminate\Support\ServiceProvider;

class HRServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        if (class_exists(\App\Modules\HR\Services\HRService::class)) {
            $this->app->singleton(\App\Modules\HR\Services\HRService::class);
        }
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
    }
}