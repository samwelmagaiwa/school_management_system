<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Models
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Fee\Models\Fee;

// Policies
use App\Modules\User\Policies\UserPolicy;
use App\Modules\School\Policies\SchoolPolicy;
use App\Policies\StudentPolicy;
use App\Policies\FeePolicy;
use App\Modules\Dashboard\Policies\DashboardPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        School::class => SchoolPolicy::class,
        Student::class => StudentPolicy::class,
        Fee::class => FeePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates if needed
        Gate::define('manage-schools', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('manage-users', function (User $user) {
            return in_array($user->role, ['SuperAdmin', 'Admin']);
        });

        Gate::define('manage-students', function (User $user) {
            return in_array($user->role, ['SuperAdmin', 'Admin']);
        });

        Gate::define('view-reports', function (User $user) {
            return in_array($user->role, ['SuperAdmin', 'Admin', 'Teacher']);
        });

        Gate::define('view-dashboard', function (User $user) {
            return true; // All authenticated users can view dashboard
        });

        Gate::define('view-system-stats', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('view-school-stats', function (User $user) {
            return in_array($user->role, ['SuperAdmin', 'Admin']);
        });
    }
}