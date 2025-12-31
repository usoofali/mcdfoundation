<?php

namespace App\Services\Dashboard\Widgets\Stats;

use App\Models\Loan;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\StatsWidget;

class LoansStatsWidget extends StatsWidget
{
    use AppliesLocationFilter;

    public function canView(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('System Admin')
            || $user->hasPermission('view_loans');
    }

    public function getData(User $user): array
    {
        $query = Loan::query()->whereHas('member', function ($q) use ($user) {
            $this->applyLocationFilter($q, $user);
        });

        $totalDisbursed = (clone $query)->where('status', 'disbursed')->sum('amount');
        $outstanding = (clone $query)
            ->whereIn('status', ['disbursed', 'defaulted'])
            ->get()
            ->sum('outstanding_balance');
        $pendingApproval = (clone $query)->where('status', 'pending')->count();

        return [
            [
                'title' => 'Total Disbursed',
                'value' => $this->formatCurrency($totalDisbursed),
                'icon' => 'banknotes',
                'color' => 'blue',
                'trend' => null,
                'order' => 70,
            ],
            [
                'title' => 'Outstanding Balance',
                'value' => $this->formatCurrency($outstanding),
                'icon' => 'exclamation-triangle',
                'color' => 'yellow',
                'trend' => null,
                'order' => 80,
            ],
            [
                'title' => 'Pending Approval',
                'value' => $this->formatNumber($pendingApproval),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
                'order' => 90,
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Loans Statistics',
            'category' => 'financial',
            'order' => 70,
        ];
    }
}
