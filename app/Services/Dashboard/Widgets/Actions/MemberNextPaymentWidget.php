<?php

namespace App\Services\Dashboard\Widgets\Actions;

use App\Models\User;
use App\Services\Dashboard\Widgets\QuickActionWidget;
use App\Services\ExpectedContributionService;

class MemberNextPaymentWidget extends QuickActionWidget
{
    public function canView(User $user): bool
    {
        return $user->hasRole('Member') && $user->member !== null;
    }

    public function getData(User $user): array
    {
        $member = $user->member;
        $service = app(ExpectedContributionService::class);

        // Ensure member has future contributions (on-demand check)
        $service->ensureFutureContributions($member, 3);

        $nextDue = $service->getNextDue($member);

        if (!$nextDue) {
            return [
                'title' => 'No Upcoming Payments',
                'description' => 'You have no pending contributions',
                'icon' => 'check-circle',
                'color' => 'green',
            ];
        }

        $isOverdue = $nextDue->is_overdue;
        $daysInfo = $isOverdue
            ? "{$nextDue->days_overdue} days overdue"
            : "{$nextDue->days_until_due} days remaining";

        return [
            'title' => $isOverdue ? 'Overdue Payment' : 'Next Payment Due',
            'description' => "Due: {$nextDue->due_date->format('M d, Y')} ({$daysInfo})",
            'amount' => $nextDue->total_amount,
            'fine' => $nextDue->fine_amount,
            'period' => $nextDue->period_start->format('M Y'),
            'icon' => $isOverdue ? 'exclamation-circle' : 'currency-dollar',
            'color' => $isOverdue ? 'red' : ($nextDue->days_until_due <= 7 ? 'orange' : 'yellow'),
            'action' => [
                'label' => 'Pay Now',
                'url' => route('my.contributions.create'),
            ],
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Next Payment',
            'category' => 'member',
            'order' => 10,
        ];
    }
}
