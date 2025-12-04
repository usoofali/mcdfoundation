<?php

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;

class ContributionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_contributions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contribution $contribution): bool
    {
        // Check permission first
        if (!$user->hasPermission('view_contributions')) {
            return false;
        }

        // Check location access via member
        if (!$contribution->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('record_contributions');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contribution $contribution): bool
    {
        // Check permission first
        if (!$user->hasPermission('edit_contributions')) {
            return false;
        }

        // Check location access via member
        if (!$contribution->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contribution $contribution): bool
    {
        // Check permission first
        if (!$user->hasPermission('delete_contributions')) {
            return false;
        }

        // Check location access via member
        if (!$contribution->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can confirm the model.
     */
    public function confirm(User $user, Contribution $contribution): bool
    {
        // Check permission first
        if (!$user->hasPermission('confirm_contributions')) {
            return false;
        }

        // Check location access via member
        if (!$contribution->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can submit contributions.
     */
    public function submit(User $user): bool
    {
        return $user->hasPermission('submit_contributions');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contribution $contribution): bool
    {
        // Check permission first
        if (!$user->hasPermission('edit_contributions')) {
            return false;
        }

        // Check location access via member
        if (!$contribution->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contribution $contribution): bool
    {
        // Check permission first
        if (!$user->hasPermission('delete_contributions')) {
            return false;
        }

        // Check location access via member
        if (!$contribution->member->isInUserLocation()) {
            return false;
        }

        return true;
    }
}
