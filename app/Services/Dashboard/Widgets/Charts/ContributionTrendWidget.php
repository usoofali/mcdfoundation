<?php

namespace App\Services\Dashboard\Widgets\Charts;

use App\Models\Contribution;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\ChartWidget;

class ContributionTrendWidget extends ChartWidget
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
        $query = Contribution::query()
            ->where('status', 'paid')
            ->whereHas('member', function ($q) use ($user) {
                $this->applyLocationFilter($q, $user);
            });

        // Get last 6 months of data
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $amount = (clone $query)
                ->whereMonth('payment_date', $month->month)
                ->whereYear('payment_date', $month->year)
                ->sum('amount');

            $data[$month->format('M Y')] = $amount;
        }

        return $this->formatTimeSeriesData($data, 'line');
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Contribution Trend',
            'category' => 'financial',
            'order' => 10,
        ];
    }
}
