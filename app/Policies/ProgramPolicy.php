<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    /**
     * Determine whether the user can view any programs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_programs');
    }

    /**
     * Determine whether the user can view the program.
     */
    public function view(User $user, Program $program): bool
    {
        return $user->hasPermission('view_programs');
    }

    /**
     * Determine whether the user can create programs.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_programs');
    }

    /**
     * Determine whether the user can update the program.
     */
    public function update(User $user, Program $program): bool
    {
        return $user->hasPermission('manage_programs');
    }

    /**
     * Determine whether the user can delete the program.
     */
    public function delete(User $user, Program $program): bool
    {
        // Can only delete if user has permission and program has no enrollments
        if (!$user->hasPermission('manage_programs')) {
            return false;
        }

        // Check if program has any enrollments
        if ($program->enrollments()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can enroll members in programs.
     */
    public function enroll(User $user): bool
    {
        return $user->hasPermission('enroll_members');
    }

    /**
     * Determine whether the user can issue certificates.
     */
    public function issueCertificate(User $user): bool
    {
        return $user->hasPermission('issue_certificates');
    }

    /**
     * Determine whether the user can restore the program.
     */
    public function restore(User $user, Program $program): bool
    {
        return $user->hasPermission('manage_programs');
    }

    /**
     * Determine whether the user can permanently delete the program.
     */
    public function forceDelete(User $user, Program $program): bool
    {
        return $user->hasPermission('manage_programs');
    }
}
