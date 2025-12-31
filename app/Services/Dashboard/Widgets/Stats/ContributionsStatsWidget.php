<?php

namespace App\Services\Dashboard\Widgets\Stats;

use App\Models\Contribution;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\StatsWidget;

class ContributionsStatsWidget extends StatsWidget
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
        $query = Contribution::query()->whereHas('member', function ($q) use ($user) {
            $this->applyLocationFilter($q, $user);
        });

        $totalPaid = (clone $query)->where('status', 'paid')->sum('amount');
        $pendingVerification = (clone $query)->where('status', 'pending_verification')->count();
        $thisMonth = (clone $query)
            ->where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        return [
            [
                'title' => 'Total Contributions',
                'value' => $this->formatCurrency($totalPaid),
                'icon' => 'currency-dollar',
                'color' => 'green',
                'trend' => null,
                'order' => 40,
            ],
            [
                'title' => 'Pending Verification',
                'value' => $this->formatNumber($pendingVerification),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
                'order' => 50,
            ],
            [
                'title' => 'This Month',
                'value' => $this->formatCurrency($thisMonth),
                'icon' => 'arrow-trending-up',
                'color' => 'blue',
                'trend' => null,
                'order' => 60,
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Contributions Statistics',
            'category' => 'financial',
            'order' => 40,
        ];
    }
}
