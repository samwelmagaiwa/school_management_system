<?php

namespace App\Modules\User\Policies;

use App\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->school_id === $model->school_id;
        }

        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            // Admin can update users in their school, but not SuperAdmin users
            return $user->school_id === $model->school_id && !$model->isSuperAdmin();
        }

        // Users can update their own profile (limited fields)
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            // Admin can delete users in their school, but not SuperAdmin users
            return $user->school_id === $model->school_id && !$model->isSuperAdmin();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->school_id === $model->school_id && !$model->isSuperAdmin();
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }
}