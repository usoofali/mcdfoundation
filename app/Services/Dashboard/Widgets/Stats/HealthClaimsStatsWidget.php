<?php

namespace App\Services\Dashboard\Widgets\Stats;

use App\Models\HealthClaim;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\StatsWidget;

class HealthClaimsStatsWidget extends StatsWidget
{
    use AppliesLocationFilter;

    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_claims');
    }

    public function getData(User $user): array
    {
        $query = HealthClaim::query()->whereHas('member', function ($q) use ($user) {
            $this->applyLocationFilter($q, $user);
        });

        $pendingClaims = (clone $query)->where('status', 'submitted')->count();
        $approvedClaims = (clone $query)->where('status', 'approved')->count();
        $totalCovered = (clone $query)->where('status', 'paid')->sum('covered_amount');

        return [
            [
                'title' => 'Pending Claims',
                'value' => $this->formatNumber($pendingClaims),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
                'order' => 100,
            ],
            [
                'title' => 'Approved Claims',
                'value' => $this->formatNumber($approvedClaims),
                'icon' => 'check-circle',
                'color' => 'green',
                'trend' => null,
                'order' => 110,
            ],
            [
                'title' => 'Total Covered',
                'value' => $this->formatCurrency($totalCovered),
                'icon' => 'heart',
                'color' => 'red',
                'trend' => null,
                'order' => 120,
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Health Claims Statistics',
            'category' => 'health',
            'order' => 100,
        ];
    }
}
