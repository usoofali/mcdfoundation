<?php

namespace App\Policies;

use App\Models\HealthClaim;
use App\Models\User;

class HealthClaimPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_claims');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, HealthClaim $claim): bool
    {
        return $user->hasPermission('view_claims');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('submit_claims');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HealthClaim $claim): bool
    {
        // Only allow editing of submitted claims
        if ($claim->status !== 'submitted') {
            return false;
        }

        return $user->hasPermission('edit_claims');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HealthClaim $claim): bool
    {
        // Only allow deletion of submitted claims
        if ($claim->status !== 'submitted') {
            return false;
        }

        return $user->hasPermission('edit_claims');
    }

    /**
     * Determine whether the user can approve the claim.
     */
    public function approve(User $user, HealthClaim $claim): bool
    {
        // Only allow approval of submitted claims
        if ($claim->status !== 'submitted') {
            return false;
        }

        return $user->hasPermission('approve_claims');
    }

    /**
     * Determine whether the user can reject the claim.
     */
    public function reject(User $user, HealthClaim $claim): bool
    {
        // Only allow rejection of submitted claims
        if ($claim->status !== 'submitted') {
            return false;
        }

        return $user->hasPermission('approve_claims');
    }

    /**
     * Determine whether the user can process payment for the claim.
     */
    public function pay(User $user, HealthClaim $claim): bool
    {
        // Only allow payment of approved claims
        if ($claim->status !== 'approved') {
            return false;
        }

        return $user->hasPermission('pay_claims');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, HealthClaim $claim): bool
    {
        return $user->hasPermission('edit_claims');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, HealthClaim $claim): bool
    {
        return $user->hasPermission('edit_claims');
    }
}
