<?php

namespace App\Services\Dashboard\Widgets\Actions;

use App\Models\User;
use App\Services\Dashboard\Widgets\QuickActionWidget;

class MemberActionsWidget extends QuickActionWidget
{
    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_members');
    }

    public function getData(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('create_members')) {
            $actions[] = $this->createAction(
                'Add Member',
                route('members.create'),
                'user-plus',
                'blue',
                'create_members'
            );
        }

        if ($user->hasPermission('view_members')) {
            $actions[] = $this->createAction(
                'View Members',
                route('members.index'),
                'users',
                'blue',
                'view_members'
            );
        }

        if ($user->hasPermission('approve_members')) {
            $actions[] = $this->createAction(
                'Approve Members',
                route('members.index', ['status' => 'pending']),
                'check-circle',
                'green',
                'approve_members'
            );
        }

        return $this->filterByPermissions($actions, $user);
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Member Actions',
            'category' => 'members',
            'order' => 10,
        ];
    }
}
