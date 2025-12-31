<?php

namespace App\Services\Dashboard\Widgets\Stats;

use App\Models\ExpectedContribution;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\StatsWidget;

class DueSoonContributionsStatsWidget extends StatsWidget
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
        $query = ExpectedContribution::dueSoon(7)
            ->whereHas('member', function ($q) use ($user) {
                $this->applyLocationFilter($q, $user);
            });

        $dueSoonCount = $query->count();
        $totalAmount = $query->sum('expected_amount');

        return [
            [
                'title' => 'Due This Week',
                'value' => $dueSoonCount,
                'icon' => 'calendar',
                'color' => 'yellow',
                'description' => 'Contributions due in 7 days',
                'trend' => null,
            ],
            [
                'title' => 'Expected Amount',
                'value' => '₦' . number_format($totalAmount, 2),
                'icon' => 'currency-dollar',
                'color' => 'yellow',
                'description' => 'Total amount due this week',
                'trend' => null,
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Due Soon Contributions',
            'category' => 'financial',
            'order' => 46,
        ];
    }
}
