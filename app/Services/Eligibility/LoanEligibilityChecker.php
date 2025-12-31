<?php

namespace App\Services\Eligibility;

use App\Models\Member;
use App\Services\SettingService;

class LoanEligibilityChecker
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Check loan eligibility for a member.
     */
    public function checkEligibility(Member $member, float $requestedAmount): array
    {
        $reasons = [];
        $settings = $this->getSettings();
        $eligibilityRules = $this->settingService->get('eligibility_rules', []);

        // 1. Member must be active
        if ($member->status !== 'active') {
            $reasons[] = 'Member must be active';
        }

        // 2. Minimum membership period
        $membershipMonths = $member->registration_date?->diffInMonths(now()) ?? 0;
        $minMonths = $eligibilityRules['loan_eligibility_months'] ?? 12;

        if ($membershipMonths < $minMonths) {
            $reasons[] = "Minimum {$minMonths} months membership required (current: {$membershipMonths})";
        }

        // 3. Minimum contributions count
        $contributionCount = $member->contributions()->where('status', 'paid')->count();
        $minContributions = $settings['min_contributions_for_loan'] ?? 12;

        if ($contributionCount < $minContributions) {
            $reasons[] = "Minimum {$minContributions} contributions required (current: {$contributionCount})";
        }

        // 4. Minimum contribution amount
        $totalContributions = $member->total_contributions;
        $minAmount = $settings['min_contribution_amount'] ?? 10000;

        if ($totalContributions < $minAmount) {
            $reasons[] = "Minimum ₦" . number_format($minAmount) . " total contributions required (current: ₦" . number_format($totalContributions) . ")";
        }

        // 5. Maximum loan amount (based on contributions)
        $multiplier = $settings['contribution_multiplier'] ?? 2.0;
        $maxEligibleAmount = $totalContributions * $multiplier;

        if ($requestedAmount > $maxEligibleAmount) {
            $reasons[] = "Maximum eligible loan is ₦" . number_format($maxEligibleAmount) . " (contributions × {$multiplier})";
        }

        // 6. Check for existing active loans
        if (!($settings['allow_multiple_loans'] ?? false)) {
            $hasActiveLoan = $member->loans()
                ->whereIn('status', ['approved', 'disbursed'])
                ->exists();

            if ($hasActiveLoan) {
                $reasons[] = 'Member has an active loan. Multiple loans not allowed.';
            }
        }

        // 7. Check for pending cashout
        if ($member->has_pending_cashout) {
            $reasons[] = 'Member has a pending cashout request';
        }

        // 8. Check loan amount limits
        $minLoanAmount = $settings['min_loan_amount'] ?? 5000;
        $maxLoanAmount = $settings['max_loan_amount'] ?? 100000;

        if ($requestedAmount < $minLoanAmount) {
            $reasons[] = "Minimum loan amount is ₦" . number_format($minLoanAmount);
        }

        if ($requestedAmount > $maxLoanAmount) {
            $reasons[] = "Maximum loan amount is ₦" . number_format($maxLoanAmount);
        }

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
            'max_eligible_amount' => min($maxEligibleAmount, $maxLoanAmount),
            'total_contributions' => $totalContributions,
            'contribution_count' => $contributionCount,
            'membership_months' => $membershipMonths,
            'settings' => [
                'min_contributions' => $minContributions,
                'min_amount' => $minAmount,
                'multiplier' => $multiplier,
                'min_membership_months' => $minMonths,
            ],
        ];
    }

    /**
     * Quick boolean check for eligibility.
     */
    public function isEligible(Member $member, float $requestedAmount): bool
    {
        return $this->checkEligibility($member, $requestedAmount)['eligible'];
    }

    /**
     * Get eligibility reasons only.
     */
    public function getEligibilityReasons(Member $member, float $requestedAmount): array
    {
        return $this->checkEligibility($member, $requestedAmount)['reasons'];
    }

    /**
     * Get loan settings from database.
     */
    protected function getSettings(): array
    {
        return $this->settingService->get('loan_settings', [
            'min_contributions_for_loan' => 12,
            'min_contribution_amount' => 10000,
            'contribution_multiplier' => 2.0,
            'allow_multiple_loans' => false,
            'min_loan_amount' => 5000,
            'max_loan_amount' => 100000,
        ]);
    }
}
