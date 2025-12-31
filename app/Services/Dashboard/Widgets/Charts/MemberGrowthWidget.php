<?php

namespace App\Services\Dashboard\Widgets\Charts;

use App\Models\Member;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\ChartWidget;

class MemberGrowthWidget extends ChartWidget
{
    use AppliesLocationFilter;

    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_members');
    }

    public function getData(User $user): array
    {
        $query = Member::query();
        $this->applyLocationFilter($query, $user);

        // Get last 6 months of member growth
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = (clone $query)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $data[$month->format('M Y')] = $count;
        }

        return $this->formatTimeSeriesData($data, 'bar');
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Member Growth',
            'category' => 'members',
            'order' => 30,
        ];
    }
}
