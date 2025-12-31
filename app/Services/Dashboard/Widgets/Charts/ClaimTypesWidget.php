<?php

namespace App\Services\Dashboard\Widgets\Charts;

use App\Models\HealthClaim;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\ChartWidget;

class ClaimTypesWidget extends ChartWidget
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

        $data = [
            'Outpatient' => (clone $query)->where('claim_type', 'outpatient')->count(),
            'Inpatient' => (clone $query)->where('claim_type', 'inpatient')->count(),
            'Maternity' => (clone $query)->where('claim_type', 'maternity')->count(),
            'Surgical' => (clone $query)->where('claim_type', 'surgical')->count(),
        ];

        // Remove zero values
        $data = array_filter($data, fn($value) => $value > 0);

        return $this->formatDistributionData($data);
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Claim Types Distribution',
            'category' => 'health',
            'order' => 40,
        ];
    }
}
