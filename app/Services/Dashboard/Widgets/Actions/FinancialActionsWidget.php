<?php

namespace App\Services\Dashboard\Widgets\Actions;

use App\Models\User;
use App\Services\Dashboard\Widgets\QuickActionWidget;

class FinancialActionsWidget extends QuickActionWidget
{
    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_contributions')
            || $user->hasPermission('view_loans');
    }

    public function getData(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('record_contributions')) {
            $actions[] = $this->createAction(
                'Record Contribution',
                route('contributions.create'),
                'currency-dollar',
                'green',
                'record_contributions'
            );
        }

        if ($user->hasPermission('view_contributions')) {
            $actions[] = $this->createAction(
                'Verify Contributions',
                route('contributions.verify'),
                'check-circle',
                'green',
                'view_contributions'
            );
        }

        if ($user->hasPermission('view_loans')) {
            $actions[] = $this->createAction(
                'Approve Loans',
                route('loans.index'),
                'banknotes',
                'blue',
                'view_loans'
            );
        }

        if ($user->hasPermission('view_reports')) {
            $actions[] = $this->createAction(
                'View Reports',
                route('reports.index'),
                'chart-bar',
                'purple',
                'view_reports'
            );
        }

        return $this->filterByPermissions($actions, $user);
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Financial Actions',
            'category' => 'financial',
            'order' => 20,
        ];
    }
}
