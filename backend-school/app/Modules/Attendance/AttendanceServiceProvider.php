<?php

namespace App\Modules\Attendance;

use Illuminate\Support\ServiceProvider;

class AttendanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        if (class_exists(\App\Modules\Attendance\Services\AttendanceService::class)) {
            $this->app->singleton(\App\Modules\Attendance\Services\AttendanceService::class);
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