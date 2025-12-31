<?php

namespace App\Services\Dashboard\Widgets\Stats;

use App\Models\Member;
use App\Models\User;
use App\Services\Dashboard\Concerns\AppliesLocationFilter;
use App\Services\Dashboard\Widgets\StatsWidget;

class MembersStatsWidget extends StatsWidget
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

        $total = $query->count();
        $active = (clone $query)->where('status', 'active')->count();
        $pending = (clone $query)->where('status', 'pending')->count();

        return [
            [
                'title' => 'Total Members',
                'value' => $this->formatNumber($total),
                'icon' => 'users',
                'color' => 'blue',
                'trend' => null,
                'order' => 10,
            ],
            [
                'title' => 'Active Members',
                'value' => $this->formatNumber($active),
                'icon' => 'user-plus',
                'color' => 'green',
                'trend' => null,
                'order' => 20,
            ],
            [
                'title' => 'Pending Approval',
                'value' => $this->formatNumber($pending),
                'icon' => 'clock',
                'color' => 'yellow',
                'trend' => null,
                'order' => 30,
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Members Statistics',
            'category' => 'members',
            'order' => 10,
        ];
    }
}
