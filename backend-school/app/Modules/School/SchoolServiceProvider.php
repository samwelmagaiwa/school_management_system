<?php

namespace App\Modules\School;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\School\Models\School;
use App\Modules\School\Policies\SchoolPolicy;

class SchoolServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        $this->app->singleton(
            \App\Modules\School\Services\SchoolService::class
        );
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

        // Register policies
        Gate::policy(School::class, SchoolPolicy::class);
    }
}