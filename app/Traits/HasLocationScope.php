<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasLocationScope
{
    /**
     * Scope query to authenticated user's assigned location
     * Super Admin and System Admin have access to all locations
     */
    public function scopeForAuthUserLocation(Builder $query): Builder
    {
        $user = Auth::user();

        // Super Admin and System Admin see everything
        if (!$user || $user->hasRole('Super Admin') || $user->hasRole('System Admin')) {
            return $query;
        }

        // Apply location filters for other staff
        if ($user->state_id) {
            $query->where('state_id', $user->state_id);
        }

        if ($user->lga_id) {
            $query->where('lga_id', $user->lga_id);
        }

        return $query;
    }

    /**
     * Check if this record is in the authenticated user's location
     * Super Admin and System Admin can access everything
     */
    public function isInUserLocation(): bool
    {
        $user = Auth::user();

        // Super Admin and System Admin can access everything
        if (!$user || $user->hasRole('Super Admin') || $user->hasRole('System Admin')) {
            return true;
        }

        // Check state match
        if ($user->state_id && $this->state_id !== $user->state_id) {
            return false;
        }

        // Check LGA match
        if ($user->lga_id && $this->lga_id !== $user->lga_id) {
            return false;
        }

        return true;
    }

    /**
     * Scope query for members in authenticated user's location
     * This is for models that have a member relationship
     */
    public function scopeForAuthUserLocationViaMember(Builder $query): Builder
    {
        $user = Auth::user();

        // Super Admin and System Admin see everything
        if (!$user || $user->hasRole('Super Admin') || $user->hasRole('System Admin')) {
            return $query;
        }

        // Apply location filters through member relationship
        return $query->whereHas('member', function ($q) use ($user) {
            if ($user->state_id) {
                $q->where('state_id', $user->state_id);
            }

            if ($user->lga_id) {
                $q->where('lga_id', $user->lga_id);
            }
        });
    }
}
