<?php

namespace App\Modules\Student;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Policies\StudentPolicy;

class StudentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        if (class_exists(\App\Modules\Student\Services\StudentService::class)) {
            $this->app->singleton(\App\Modules\Student\Services\StudentService::class);
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

        // Register policies
        if (class_exists(StudentPolicy::class)) {
            Gate::policy(Student::class, StudentPolicy::class);
        }
    }
}