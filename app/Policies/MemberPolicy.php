<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_members');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Member $member): bool
    {
        if ($member->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('view_members');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_members');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Member $member): bool
    {
        if ($member->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('edit_members');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Member $member): bool
    {
        return $user->hasPermission('delete_members');
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Member $member): bool
    {
        return $user->hasPermission('approve_members');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Member $member): bool
    {
        return $user->hasPermission('edit_members');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Member $member): bool
    {
        return $user->hasPermission('delete_members');
    }
}
