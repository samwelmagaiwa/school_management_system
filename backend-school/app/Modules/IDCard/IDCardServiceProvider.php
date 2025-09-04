<?php

namespace App\Modules\IDCard;

use Illuminate\Support\ServiceProvider;

class IDCardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        if (class_exists(\App\Modules\IDCard\Services\IDCardService::class)) {
            $this->app->singleton(\App\Modules\IDCard\Services\IDCardService::class);
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

        // Load views
        $this->loadViewsFrom(__DIR__ . '/Views', 'idcard');
    }
}