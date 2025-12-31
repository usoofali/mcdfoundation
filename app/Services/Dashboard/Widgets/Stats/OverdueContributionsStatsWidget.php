<?php

namespace App\Services\Dashboard\Widgets\Stats;

use App\Models\ExpectedContribution;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\StatsWidget;

class OverdueContributionsStatsWidget extends StatsWidget
{
    use AppliesLocationFilter;
    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_contributions');
    }

    public function getData(User $user): array
    {
        $query = ExpectedContribution::overdue()
            ->whereHas('member', function ($q) use ($user) {
                $this->applyLocationFilter($q, $user);
            });

        $overdueCount = $query->count();
        $totalFines = $query->sum('fine_amount');
        $totalAmount = $query->sum('expected_amount');

        return [
            [
                'title' => 'Overdue Contributions',
                'value' => $overdueCount,
                'icon' => 'exclamation-triangle',
                'color' => 'red',
                'description' => 'Members with overdue payments',
                'trend' => null,
            ],
            [
                'title' => 'Total Overdue Amount',
                'value' => '₦' . number_format($totalAmount, 2),
                'icon' => 'currency-dollar',
                'color' => 'red',
                'description' => 'Base amount overdue',
                'trend' => null,
            ],
            [
                'title' => 'Total Fines',
                'value' => '₦' . number_format($totalFines, 2),
                'icon' => 'banknotes',
                'color' => 'orange',
                'description' => 'Late payment fines',
                'trend' => null,
            ],
            [
                'title' => 'Total Due',
                'value' => '₦' . number_format($totalAmount + $totalFines, 2),
                'icon' => 'currency-dollar',
                'color' => 'red',
                'description' => 'Amount + fines',
                'trend' => null,
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Overdue Contributions',
            'category' => 'financial',
            'order' => 45,
        ];
    }
}
