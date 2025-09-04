<?php

namespace App\Policies;

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
        // SuperAdmin can view any student
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin and Teachers can view students in their school
        if (in_array($user->role, ['Admin', 'Teacher']) && $user->school_id === $student->school_id) {
            return true;
        }

        // Students can view their own record
        if ($user->isStudent() && $user->student && $user->student->id === $student->id) {
            return true;
        }

        // Parents can view their children's records
        if ($user->isParent() && $user->children && $user->children->contains('id', $student->id)) {
            return true;
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
        // SuperAdmin can update any student
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can update students in their school
        if ($user->isAdmin() && $user->school_id === $student->school_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, Student $student): bool
    {
        // SuperAdmin can delete any student
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can delete students in their school
        if ($user->isAdmin() && $user->school_id === $student->school_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the student.
     */
    public function restore(User $user, Student $student): bool
    {
        return $this->delete($user, $student);
    }

    /**
     * Determine whether the user can permanently delete the student.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        // Only SuperAdmin can permanently delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can promote the student.
     */
    public function promote(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }

    /**
     * Determine whether the user can transfer the student.
     */
    public function transfer(User $user, Student $student): bool
    {
        // SuperAdmin can transfer any student
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can transfer students from their school
        if ($user->isAdmin() && $user->school_id === $student->school_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view student performance.
     */
    public function viewPerformance(User $user, Student $student): bool
    {
        return $this->view($user, $student);
    }

    /**
     * Determine whether the user can view student attendance.
     */
    public function viewAttendance(User $user, Student $student): bool
    {
        return $this->view($user, $student);
    }

    /**
     * Determine whether the user can export students.
     */
    public function export(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin', 'Teacher']);
    }

    /**
     * Determine whether the user can import students.
     */
    public function import(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can view statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin', 'Teacher']);
    }

    /**
     * Determine whether the user can bulk update student status.
     */
    public function bulkUpdate(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }
}
