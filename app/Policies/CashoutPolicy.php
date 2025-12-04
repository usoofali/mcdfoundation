<?php

namespace App\Policies;

use App\Models\CashoutRequest;
use App\Models\User;

class CashoutPolicy
{
    /**
     * Determine whether the user can view any cashout requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_cashout');
    }

    /**
     * Determine whether the user can view the cashout request.
     */
    public function view(User $user, CashoutRequest $cashoutRequest): bool
    {
        // Members can view their own requests
        if ($cashoutRequest->member->user_id === $user->id) {
            return true;
        }

        // Check permission first
        if (!$user->hasPermission('view_cashout')) {
            return false;
        }

        // Check location access
        if (!$cashoutRequest->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can create cashout requests.
     */
    public function create(User $user): bool
    {
        // Only members can request cashout
        return $user->hasPermission('request_cashout') && $user->member()->exists();
    }

    /**
     * Determine whether the user can verify the cashout request.
     */
    public function verify(User $user, CashoutRequest $cashoutRequest): bool
    {
        // Only pending requests can be verified
        if ($cashoutRequest->status !== 'pending') {
            return false;
        }

        if (!$user->hasPermission('verify_cashout')) {
            return false;
        }

        if (!$cashoutRequest->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can approve the cashout request.
     */
    public function approve(User $user, CashoutRequest $cashoutRequest): bool
    {
        // Only verified requests can be approved
        if ($cashoutRequest->status !== 'verified') {
            return false;
        }

        if (!$user->hasPermission('approve_cashout')) {
            return false;
        }

        if (!$cashoutRequest->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can disburse the cashout request.
     */
    public function disburse(User $user, CashoutRequest $cashoutRequest): bool
    {
        // Only approved requests can be disbursed
        if ($cashoutRequest->status !== 'approved') {
            return false;
        }

        if (!$user->hasPermission('disburse_cashout')) {
            return false;
        }

        if (!$cashoutRequest->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can reject the cashout request.
     */
    public function reject(User $user, CashoutRequest $cashoutRequest): bool
    {
        // Cannot reject disbursed or already rejected requests
        if (in_array($cashoutRequest->status, ['disbursed', 'rejected'])) {
            return false;
        }

        // Check permissions
        if (!($user->hasPermission('verify_cashout') || $user->hasPermission('approve_cashout'))) {
            return false;
        }

        // Check location access
        if (!$cashoutRequest->member->isInUserLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the cashout request.
     */
    public function delete(User $user, CashoutRequest $cashoutRequest): bool
    {
        // Members can only delete their own pending requests
        if ($cashoutRequest->member->user_id === $user->id) {
            return $cashoutRequest->status === 'pending';
        }

        return false;
    }
}
