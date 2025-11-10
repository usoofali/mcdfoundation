<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_loans');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->hasPermission('view_loans');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('apply_loans');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Loan $loan): bool
    {
        return $user->hasPermission('edit_loans');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->hasPermission('edit_loans');
    }

    /**
     * Determine whether the user can approve loans at Level 1.
     */
    public function approveL1(User $user, Loan $loan): bool
    {
        return $user->hasPermission('approve_loans_l1');
    }

    /**
     * Determine whether the user can approve loans at Level 2.
     */
    public function approveL2(User $user, Loan $loan): bool
    {
        return $user->hasPermission('approve_loans_l2');
    }

    /**
     * Determine whether the user can approve loans at Level 3.
     */
    public function approveL3(User $user, Loan $loan): bool
    {
        return $user->hasPermission('approve_loans_l3');
    }

    /**
     * Determine whether the user can disburse loans.
     */
    public function disburse(User $user, Loan $loan): bool
    {
        return $user->hasPermission('disburse_loans');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Loan $loan): bool
    {
        return $user->hasPermission('edit_loans');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Loan $loan): bool
    {
        return $user->hasPermission('edit_loans');
    }
}
