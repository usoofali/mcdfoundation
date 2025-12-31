<?php

namespace App\Services\Dashboard\Widgets\Actions;

use App\Models\User;
use App\Services\Dashboard\Widgets\QuickActionWidget;

class ProgramActionsWidget extends QuickActionWidget
{
    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_programs');
    }

    public function getData(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('view_programs')) {
            $actions[] = $this->createAction(
                'View Programs',
                route('programs.index'),
                'academic-cap',
                'purple',
                'view_programs'
            );
        }

        if ($user->hasPermission('manage_programs')) {
            $actions[] = $this->createAction(
                'Manage Programs',
                route('programs.index'),
                'cog',
                'gray',
                'manage_programs'
            );
        }

        if ($user->hasPermission('enroll_members')) {
            $actions[] = $this->createAction(
                'Enroll Members',
                route('program-enrollments.index'),
                'user-plus',
                'blue',
                'enroll_members'
            );
        }

        return $this->filterByPermissions($actions, $user);
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Program Actions',
            'category' => 'programs',
            'order' => 40,
        ];
    }
}
