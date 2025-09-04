<?php

namespace App\Policies;

use App\Modules\User\Models\User;
use App\Modules\Fee\Models\Fee;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any fees.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin', 'Teacher', 'Student', 'Parent']);
    }

    /**
     * Determine whether the user can view the fee.
     */
    public function view(User $user, Fee $fee): bool
    {
        // SuperAdmin can view any fee
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin and Teachers can view fees in their school
        if (in_array($user->role, ['Admin', 'Teacher']) && $user->school_id === $fee->school_id) {
            return true;
        }

        // Students can view their own fees
        if ($user->isStudent() && $user->student && $user->student->id === $fee->student_id) {
            return true;
        }

        // Parents can view their children's fees
        if ($user->isParent() && $user->children && $user->children->contains('id', $fee->student_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create fees.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can update the fee.
     */
    public function update(User $user, Fee $fee): bool
    {
        // Cannot update paid fees
        if ($fee->status === 'Paid') {
            return false;
        }

        // SuperAdmin can update any fee
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can update fees in their school
        if ($user->isAdmin() && $user->school_id === $fee->school_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the fee.
     */
    public function delete(User $user, Fee $fee): bool
    {
        // Cannot delete paid fees
        if ($fee->status === 'Paid') {
            return false;
        }

        // SuperAdmin can delete any fee
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can delete fees in their school
        if ($user->isAdmin() && $user->school_id === $fee->school_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark fee as paid.
     */
    public function markAsPaid(User $user, Fee $fee): bool
    {
        return $this->update($user, $fee);
    }

    /**
     * Determine whether the user can generate invoice.
     */
    public function generateInvoice(User $user, Fee $fee): bool
    {
        return $this->view($user, $fee);
    }

    /**
     * Determine whether the user can generate receipt.
     */
    public function generateReceipt(User $user, Fee $fee): bool
    {
        // Receipt can only be generated for paid fees
        if ($fee->status !== 'Paid') {
            return false;
        }

        return $this->view($user, $fee);
    }

    /**
     * Determine whether the user can export fees.
     */
    public function export(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin', 'Teacher']);
    }

    /**
     * Determine whether the user can view fee statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can create bulk fees.
     */
    public function bulkCreate(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can apply late fees.
     */
    public function applyLateFees(User $user): bool
    {
        return in_array($user->role, ['SuperAdmin', 'Admin']);
    }
}
