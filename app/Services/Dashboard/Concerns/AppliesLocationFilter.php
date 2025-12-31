<?php

namespace App\Services\Dashboard\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait AppliesLocationFilter
{
    /**
     * Apply location-based filtering to a query.
     * Super Admin and System Admin are exempt from location filtering.
     */
    protected function applyLocationFilter(Builder $query, User $user): Builder
    {
        // Super Admin and System Admin see all data
        if ($user->hasRole('Super Admin') || $user->hasRole('System Admin')) {
            return $query;
        }

        // Apply state filter if user has assigned state
        if ($user->state_id) {
            $query->where('state_id', $user->state_id);
        }

        // Apply LGA filter if user has assigned LGA
        if ($user->lga_id) {
            $query->where('lga_id', $user->lga_id);
        }

        return $query;
    }

    /**
     * Apply location filter via member relationship.
     * Use this for models that don't have direct state_id/lga_id fields.
     */
    protected function applyLocationFilterViaMember(Builder $query, User $user): Builder
    {
        // Super Admin and System Admin see all data
        if ($user->hasRole('Super Admin') || $user->hasRole('System Admin')) {
            return $query;
        }

        $query->whereHas('member', function ($q) use ($user) {
            if ($user->state_id) {
                $q->where('state_id', $user->state_id);
            }
            if ($user->lga_id) {
                $q->where('lga_id', $user->lga_id);
            }
        });

        return $query;
    }

    /**
     * Check if user should see location-filtered data.
     */
    protected function shouldApplyLocationFilter(User $user): bool
    {
        return !($user->hasRole('Super Admin') || $user->hasRole('System Admin'));
    }
}
