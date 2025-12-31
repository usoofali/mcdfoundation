<?php

namespace App\Services\Dashboard\Widgets\Charts;

use App\Models\Loan;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\ChartWidget;

class LoanDistributionWidget extends ChartWidget
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

        $data = [
            'Pending' => (clone $query)->where('status', 'pending')->count(),
            'Approved' => (clone $query)->where('status', 'approved')->count(),
            'Disbursed' => (clone $query)->where('status', 'disbursed')->count(),
            'Completed' => (clone $query)->where('status', 'completed')->count(),
            'Defaulted' => (clone $query)->where('status', 'defaulted')->count(),
        ];

        // Remove zero values
        $data = array_filter($data, fn($value) => $value > 0);

        return $this->formatDistributionData($data);
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Loan Distribution',
            'category' => 'financial',
            'order' => 20,
        ];
    }
}
