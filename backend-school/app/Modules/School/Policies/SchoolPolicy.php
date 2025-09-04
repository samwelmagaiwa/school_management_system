<?php

namespace App\Modules\School\Policies;

use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any schools.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can view the school.
     */
    public function view(User $user, School $school): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->school_id === $school->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create schools.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the school.
     */
    public function update(User $user, School $school): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->school_id === $school->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the school.
     */
    public function delete(User $user, School $school): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the school.
     */
    public function restore(User $user, School $school): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the school.
     */
    public function forceDelete(User $user, School $school): bool
    {
        return $user->isSuperAdmin();
    }
}