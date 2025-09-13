<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register application services
        $this->registerApplicationServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register application-specific services
     */
    protected function registerApplicationServices(): void
    {
        // Register core services as singletons
        $this->app->singleton(\App\Services\SchoolService::class);
        $this->app->singleton(\App\Services\StudentService::class);
        $this->app->singleton(\App\Services\TeacherService::class);
        $this->app->singleton(\App\Services\AttendanceService::class);
        $this->app->singleton(\App\Services\ExamService::class);
        $this->app->singleton(\App\Services\FeeService::class);
        $this->app->singleton(\App\Services\LibraryService::class);
        $this->app->singleton(\App\Services\TransportService::class);
        $this->app->singleton(\App\Services\IDCardService::class);
        $this->app->singleton(\App\Services\DashboardService::class);
        $this->app->singleton(\App\Services\NotificationService::class);
    }

}
