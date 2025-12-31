<?php

namespace App\Services\Dashboard\Widgets\Actions;

use App\Models\User;
use App\Services\Dashboard\Widgets\QuickActionWidget;

class HealthActionsWidget extends QuickActionWidget
{
    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_claims');
    }

    public function getData(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('approve_claims')) {
            $actions[] = $this->createAction(
                'Approve Claims',
                route('health-claims.index', ['status' => 'submitted']),
                'check-circle',
                'green',
                'approve_claims'
            );
        }

        if ($user->hasPermission('view_claims')) {
            $actions[] = $this->createAction(
                'View Claims',
                route('health-claims.index'),
                'heart',
                'red',
                'view_claims'
            );
        }

        if ($user->hasPermission('view_members')) {
            $actions[] = $this->createAction(
                'Check Eligibility',
                route('members.index'),
                'shield-check',
                'blue',
                'view_members'
            );
        }

        return $this->filterByPermissions($actions, $user);
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Health Actions',
            'category' => 'health',
            'order' => 30,
        ];
    }
}
