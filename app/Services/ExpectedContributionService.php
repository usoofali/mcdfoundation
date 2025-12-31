<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\ContributionPlan;
use App\Models\ExpectedContribution;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpectedContributionService
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Generate expected contributions for a member based on their plan.
     */
    public function generateForMember(Member $member, ?Carbon $startDate = null, ?int $count = null, bool $isInitial = true): \Illuminate\Support\Collection
    {
        Log::debug("Generating contributions for member ID: {$member->id}");

        $plan = $member->contributionPlan ?? ContributionPlan::find($member->contribution_plan_id);
        if (!$plan) {
            Log::warning("Member ID: {$member->id} does not have a contribution plan");
            return collect();
        }

        $config = $this->getFrequencyConfig($plan->frequency);
        $count = $count ?? $config['generate'];

        // Ensure start date is at least today
        $now = now()->startOfDay();
        $startDate = $startDate ?? $member->registration_date ?? $now;

        if ($startDate->isPast() && !$startDate->isToday()) {
            $startDate = $now;
        }

        $plan = $member->contributionPlan;

        if (!$plan) {
            throw new \Exception('Member does not have a contribution plan');
        }

        $expectedContributions = collect();
        $currentDate = $startDate->copy()->startOfDay();

        for ($i = 0; $i < $count; $i++) {
            $periodStart = $currentDate->copy();
            $periodEnd = $this->calculatePeriodEnd($periodStart, $plan->frequency);

            // First contribution due date based on frequency - only for initial generation
            if ($i === 0 && $isInitial) {
                $dueDate = match ($plan->frequency) {
                    'daily' => now()->addDay(),
                    'weekly' => now()->addDays(7),
                    'monthly' => now()->addDays(30),
                    'quarterly' => now()->addDays(90),
                    'annual' => now()->addDays(360),
                    default => now()->addDays(30),
                };
                $dueDate->endOfDay();
            } else {
                $dueDate = $periodEnd->copy()->endOfDay();
            }

            // Check if this expected contribution already exists - use date only for comparison
            $exists = ExpectedContribution::where('member_id', $member->id)
                ->whereDate('period_start', $periodStart->format('Y-m-d'))
                ->whereDate('period_end', $periodEnd->format('Y-m-d'))
                ->exists();

            if (!$exists) {
                $expectedContributions->push(ExpectedContribution::create([
                    'member_id' => $member->id,
                    'contribution_plan_id' => $plan->id,
                    'expected_amount' => $plan->amount,
                    'due_date' => $dueDate,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'status' => 'pending',
                ]));
            }

            $currentDate = $this->getNextPeriodStart($currentDate, $plan->frequency);
        }

        Log::debug("Generated {$expectedContributions->count()} contributions for member ID: {$member->id}");

        return $expectedContributions;
    }

    /**
     * Mark overdue contributions and calculate fines.
     */
    public function markOverdueContributions(): int
    {
        $fineSettings = $this->settingService->get('fine_settings', []);
        $finePercent = $fineSettings['late_payment_fine_percent'] ?? 50;

        $overdueCount = ExpectedContribution::where('status', 'pending')
            ->where('due_date', '<', now())
            ->update([
                'status' => 'overdue',
                'overdue_at' => now(),
                'fine_amount' => DB::raw("expected_amount * ({$finePercent} / 100)"),
            ]);

        return $overdueCount;
    }

    /**
     * Force update overdue status and fines for a specific member.
     * Useful for ensuring accurate display on payment pages.
     */
    public function updateMemberOverdueStatus(Member $member): int
    {
        $fineSettings = $this->settingService->get('fine_settings', []);
        $finePercent = $fineSettings['late_payment_fine_percent'] ?? 50;

        $overdueCount = ExpectedContribution::where('member_id', $member->id)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->update([
                'status' => 'overdue',
                'overdue_at' => now(),
                'fine_amount' => DB::raw("expected_amount * ({$finePercent} / 100)"),
            ]);

        return $overdueCount;
    }

    /**
     * Link actual payment to expected contribution.
     */
    public function linkPayment(Contribution $contribution): ?ExpectedContribution
    {
        // Find matching expected contribution
        $expected = ExpectedContribution::where('member_id', $contribution->member_id)
            ->where('contribution_plan_id', $contribution->contribution_plan_id)
            ->whereNull('actual_contribution_id')
            ->where(function ($query) use ($contribution) {
                $query->where(function ($q) use ($contribution) {
                    $q->where('period_start', '<=', $contribution->payment_date)
                        ->where('period_end', '>=', $contribution->payment_date);
                })
                    ->orWhere(function ($q) use ($contribution) {
                        // Also match if payment is for overdue period
                        $q->where('status', 'overdue')
                            ->where('period_end', '<', $contribution->payment_date);
                    });
            })
            ->orderBy('period_start')
            ->first();

        if ($expected) {
            $expected->update([
                'actual_contribution_id' => $contribution->id,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return $expected;
    }

    /**
     * Get overdue contributions count for a member.
     */
    public function getOverdueCount(Member $member): int
    {
        return $member->expectedContributions()
            ->overdue()
            ->notAwaitingVerification()
            ->count();
    }

    /**
     * Get next due contribution for a member.
     */
    public function getNextDue(Member $member): ?ExpectedContribution
    {
        return $member->expectedContributions()
            ->unpaid()
            ->notAwaitingVerification()
            ->orderBy('due_date')
            ->first();
    }

    /**
     * Calculate period end based on frequency.
     */
    protected function calculatePeriodEnd(Carbon $periodStart, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $periodStart->copy()->endOfDay(),
            'weekly' => $periodStart->copy()->addWeek()->subDay()->endOfDay(),
            'monthly' => $periodStart->copy()->addMonth()->subDay()->endOfDay(),
            'quarterly' => $periodStart->copy()->addMonths(3)->subDay()->endOfDay(),
            'annual' => $periodStart->copy()->addYear()->subDay()->endOfDay(),
            default => $periodStart->copy()->addMonth()->subDay()->endOfDay(),
        };
    }

    /**
     * Get next period start based on frequency.
     */
    protected function getNextPeriodStart(Carbon $currentDate, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $currentDate->copy()->addDay()->startOfDay(),
            'weekly' => $currentDate->copy()->addWeek()->startOfDay(),
            'monthly' => $currentDate->copy()->addMonth()->startOfDay(),
            'quarterly' => $currentDate->copy()->addMonths(3)->startOfDay(),
            'annual' => $currentDate->copy()->addYear()->startOfDay(),
            default => $currentDate->copy()->addMonth()->startOfDay(),
        };
    }

    /**
     * Waive fine for an expected contribution.
     */
    public function waiveFine(ExpectedContribution $expected, ?string $reason = null): bool
    {
        return $expected->update([
            'fine_amount' => 0,
            'notes' => $reason ? "Fine waived: {$reason}" : 'Fine waived',
        ]);
    }

    /**
     * Mark expected contribution as waived.
     */
    public function waiveContribution(ExpectedContribution $expected, string $reason): bool
    {
        return $expected->update([
            'status' => 'waived',
            'notes' => "Contribution waived: {$reason}",
        ]);
    }

    /**
     * Ensure member has future expected contributions.
     * Generates more if running low.
     */
    public function ensureFutureContributions(Member $member, ?int $thresholdOverride = null): int
    {
        if (!$member->contribution_plan_id) {
            return 0;
        }

        $plan = $member->contributionPlan ?? ContributionPlan::find($member->contribution_plan_id);
        if (!$plan) {
            return 0;
        }

        $config = $this->getFrequencyConfig($member->contributionPlan->frequency);
        $threshold = $thresholdOverride ?? $config['threshold'];
        $generateCount = $config['generate'];

        // Check how many future pending contributions exist
        $futureCount = $member->expectedContributions()
            ->where('status', 'pending')
            ->where('due_date', '>', now())
            ->count();

        // If less than threshold, generate more
        if ($futureCount < $threshold) {
            $lastExpected = $member->expectedContributions()
                ->orderBy('period_end', 'desc')
                ->first();

            // If we have something, continue from it
            if ($lastExpected) {
                $startDate = $lastExpected->period_end->addDay();
                $generated = $this->generateForMember($member, $startDate, $generateCount, false);
                return $generated->count();
            } else {
                // If brand new, start fresh
                $generated = $this->generateForMember($member, null, $generateCount, true);
                return $generated->count();
            }
        }

        return 0;
    }

    /**
     * Get frequency-based configuration for generation.
     */
    protected function getFrequencyConfig(string $frequency): array
    {
        return match ($frequency) {
            'daily' => ['generate' => 30, 'threshold' => 7],
            'weekly' => ['generate' => 26, 'threshold' => 4],
            'monthly' => ['generate' => 12, 'threshold' => 3],
            'quarterly' => ['generate' => 4, 'threshold' => 1],
            'annual' => ['generate' => 2, 'threshold' => 1],
            default => ['generate' => 12, 'threshold' => 3],
        };
    }

    /**
     * Auto-generate for all active members running low on future contributions.
     */
    public function autoGenerateForAllMembers(): int
    {
        $totalGenerated = 0;

        // Get active members with contribution plans
        Member::active()
            ->whereHas('contributionPlan')
            ->chunk(100, function ($members) use (&$totalGenerated) {
                foreach ($members as $member) {
                    $generated = $this->ensureFutureContributions($member);
                    $totalGenerated += $generated;
                }
            });

        return $totalGenerated;
    }
}
