<?php

namespace App\Services\Dashboard\Widgets\Stats;

use App\Models\CashoutRequest;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\StatsWidget;

class CashoutsStatsWidget extends StatsWidget
{
    use AppliesLocationFilter;

    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_cashout');
    }

    public function getData(User $user): array
    {
        $query = CashoutRequest::query()->whereHas('member', function ($q) use ($user) {
            $this->applyLocationFilter($q, $user);
        });

        $pendingVerification = (clone $query)->where('status', 'pending')->count();
        $pendingApproval = (clone $query)->where('status', 'verified')->count();
        $totalDisbursed = (clone $query)->where('status', 'disbursed')->sum('approved_amount');

        return [
            [
                'title' => 'Pending Verification',
                'value' => $this->formatNumber($pendingVerification),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
                'order' => 130,
            ],
            [
                'title' => 'Pending Approval',
                'value' => $this->formatNumber($pendingApproval),
                'icon' => 'document-check',
                'color' => 'blue',
                'trend' => null,
                'order' => 140,
            ],
            [
                'title' => 'Total Disbursed',
                'value' => $this->formatCurrency($totalDisbursed),
                'icon' => 'banknotes',
                'color' => 'green',
                'trend' => null,
                'order' => 150,
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Cashouts Statistics',
            'category' => 'financial',
            'order' => 130,
        ];
    }
}
