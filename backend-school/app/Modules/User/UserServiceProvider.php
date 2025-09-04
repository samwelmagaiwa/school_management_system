<?php

namespace App\Modules\User;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\User\Models\User;
use App\Modules\User\Policies\UserPolicy;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        $this->app->singleton(\App\Modules\User\Services\UserService::class);
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
        Gate::policy(User::class, UserPolicy::class);

        // Register middleware
        $this->app['router']->aliasMiddleware('role', \App\Modules\User\Middleware\RoleMiddleware::class);
    }
}