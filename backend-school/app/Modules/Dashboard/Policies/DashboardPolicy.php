<?php

namespace App\Modules\Dashboard\Policies;

use App\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dashboard.
     */
    public function view(User $user): bool
    {
        // All authenticated users can view dashboard
        return true;
    }

    /**
     * Determine whether the user can view dashboard statistics.
     */
    public function viewStats(User $user): bool
    {
        // All authenticated users can view their relevant stats
        return true;
    }

    /**
     * Determine whether the user can view system-wide statistics.
     */
    public function viewSystemStats(User $user): bool
    {
        // Only SuperAdmin can view system-wide statistics
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view school statistics.
     */
    public function viewSchoolStats(User $user): bool
    {
        // SuperAdmin and Admin can view school statistics
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can manage dashboard settings.
     */
    public function manageSettings(User $user): bool
    {
        // Only SuperAdmin can manage dashboard settings
        return $user->isSuperAdmin();
    }
}