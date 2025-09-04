<?php

namespace App\Modules\Student\Policies;

use App\Modules\User\Models\User;
use App\Modules\Student\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin', 'Teacher']);
    }

    /**
     * Determine whether the user can view the student.
     */
    public function view(User $user, Student $student): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() || $user->isTeacher()) {
            return $user->school_id === $student->school_id;
        }

        // Students and parents can view their own student record
        if ($user->isStudent()) {
            return $user->student && $user->student->id === $student->id;
        }

        if ($user->isParent()) {
            // Assuming parent relationship is established through student record
            return $user->student && $user->student->id === $student->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create students.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can update the student.
     */
    public function update(User $user, Student $student): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->school_id === $student->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, Student $student): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->school_id === $student->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the student.
     */
    public function restore(User $user, Student $student): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->school_id === $student->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the student.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return $user->isSuperAdmin();
    }
}