<?php

namespace App\Policies;

use App\Models\Dependent;
use App\Models\User;

class DependentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_dependents');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Dependent $dependent): bool
    {
        return $user->hasPermission('view_dependents');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_dependents');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dependent $dependent): bool
    {
        return $user->hasPermission('edit_dependents');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dependent $dependent): bool
    {
        return $user->hasPermission('delete_dependents');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dependent $dependent): bool
    {
        return $user->hasPermission('edit_dependents');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dependent $dependent): bool
    {
        return $user->hasPermission('delete_dependents');
    }
}
