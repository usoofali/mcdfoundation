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
        return $user->hasPermission('view_contributions');
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
        return $user->hasPermission('edit_contributions');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contribution $contribution): bool
    {
        return $user->hasPermission('delete_contributions');
    }

    /**
     * Determine whether the user can confirm the model.
     */
    public function confirm(User $user, Contribution $contribution): bool
    {
        return $user->hasPermission('confirm_contributions');
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
        return $user->hasPermission('edit_contributions');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contribution $contribution): bool
    {
        return $user->hasPermission('delete_contributions');
    }
}
