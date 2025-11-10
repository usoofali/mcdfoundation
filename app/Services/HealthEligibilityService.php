<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

class HealthEligibilityService
{
    /**
     * Check if a member is eligible for health fund benefits.
     */
    public function checkMemberEligibility(Member $member, string $claimType = 'outpatient'): array
    {
        return $member->checkHealthEligibility($claimType);
    }

    /**
     * Get all eligible members for a specific claim type.
     */
    public function getEligibleMembers(string $claimType = 'outpatient'): Collection
    {
        $query = Member::where('status', 'active');

        // Filter by registration date (60+ days)
        $sixtyDaysAgo = now()->subDays(60);
        $query->where(function ($q) use ($sixtyDaysAgo) {
            $q->where('registration_date', '<=', $sixtyDaysAgo)
                ->orWhere('created_at', '<=', $sixtyDaysAgo);
        });

        // Filter by contribution requirements
        $contributionRequirement = $this->getContributionRequirementForClaimType($claimType);

        $query->whereHas('contributions', function ($q) {
            $q->where('status', 'paid');
        }, '>=', $contributionRequirement);

        return $query->with(['contributions', 'healthcareProvider'])->get();
    }

    /**
     * Get members who will become eligible soon.
     */
    public function getUpcomingEligibleMembers(int $daysAhead = 30): Collection
    {
        $futureDate = now()->addDays($daysAhead);
        $sixtyDaysFromNow = now()->addDays(60);

        return Member::where('status', 'active')
            ->where(function ($q) use ($futureDate, $sixtyDaysFromNow) {
                $q->whereBetween('registration_date', [$futureDate, $sixtyDaysFromNow])
                    ->orWhereBetween('created_at', [$futureDate, $sixtyDaysFromNow]);
            })
            ->with(['contributions'])
            ->get()
            ->filter(function ($member) {
                return $member->contributions()->where('status', 'paid')->count() >= 5;
            });
    }

    /**
     * Get eligibility statistics.
     */
    public function getEligibilityStats(): array
    {
        $totalMembers = Member::where('status', 'active')->count();
        $outpatientEligible = $this->getEligibleMembers('outpatient')->count();
        $inpatientEligible = $this->getEligibleMembers('inpatient')->count();
        $upcomingEligible = $this->getUpcomingEligibleMembers()->count();

        return [
            'total_active_members' => $totalMembers,
            'outpatient_eligible' => $outpatientEligible,
            'inpatient_eligible' => $inpatientEligible,
            'upcoming_eligible' => $upcomingEligible,
            'outpatient_percentage' => $totalMembers > 0 ? ($outpatientEligible / $totalMembers) * 100 : 0,
            'inpatient_percentage' => $totalMembers > 0 ? ($inpatientEligible / $totalMembers) * 100 : 0,
        ];
    }

    /**
     * Update eligibility status for all members.
     */
    public function updateAllMemberEligibility(): int
    {
        $count = 0;

        Member::where('status', 'active')->chunk(100, function ($members) use (&$count) {
            foreach ($members as $member) {
                $member->updateEligibilityStatus();
                $count++;
            }
        });

        return $count;
    }

    /**
     * Get contribution requirement for claim type.
     */
    protected function getContributionRequirementForClaimType(string $claimType): int
    {
        return match ($claimType) {
            'outpatient' => 1, // Any contribution
            'inpatient', 'surgery', 'maternity' => 5, // 5 months contributions
            default => 1,
        };
    }

    /**
     * Validate claim eligibility before submission.
     */
    public function validateClaimEligibility(array $claimData): array
    {
        $member = Member::find($claimData['member_id']);

        if (! $member) {
            return [
                'valid' => false,
                'message' => 'Member not found',
            ];
        }

        $eligibility = $this->checkMemberEligibility($member, $claimData['claim_type']);

        if (! $eligibility['eligible']) {
            return [
                'valid' => false,
                'message' => 'Member is not eligible: '.implode(', ', $eligibility['issues']),
                'eligibility_details' => $eligibility,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Member is eligible for this claim type',
            'eligibility_details' => $eligibility,
        ];
    }

    /**
     * Get eligibility report data.
     */
    public function getEligibilityReport(array $filters = []): array
    {
        $query = Member::query();

        // Apply filters
        if (isset($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        if (isset($filters['lga_id'])) {
            $query->where('lga_id', $filters['lga_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $members = $query->with(['contributions', 'state', 'lga'])->get();

        $report = [
            'total_members' => $members->count(),
            'eligible_outpatient' => 0,
            'eligible_inpatient' => 0,
            'not_eligible' => 0,
            'by_state' => [],
            'by_lga' => [],
            'members' => [],
        ];

        foreach ($members as $member) {
            $outpatientEligible = $member->checkHealthEligibility('outpatient')['eligible'];
            $inpatientEligible = $member->checkHealthEligibility('inpatient')['eligible'];

            if ($outpatientEligible) {
                $report['eligible_outpatient']++;
            }
            if ($inpatientEligible) {
                $report['eligible_inpatient']++;
            }
            if (! $outpatientEligible && ! $inpatientEligible) {
                $report['not_eligible']++;
            }

            // Group by state
            if ($member->state) {
                $stateName = $member->state->name;
                if (! isset($report['by_state'][$stateName])) {
                    $report['by_state'][$stateName] = [
                        'total' => 0,
                        'eligible_outpatient' => 0,
                        'eligible_inpatient' => 0,
                    ];
                }
                $report['by_state'][$stateName]['total']++;
                if ($outpatientEligible) {
                    $report['by_state'][$stateName]['eligible_outpatient']++;
                }
                if ($inpatientEligible) {
                    $report['by_state'][$stateName]['eligible_inpatient']++;
                }
            }

            // Group by LGA
            if ($member->lga) {
                $lgaName = $member->lga->name;
                if (! isset($report['by_lga'][$lgaName])) {
                    $report['by_lga'][$lgaName] = [
                        'total' => 0,
                        'eligible_outpatient' => 0,
                        'eligible_inpatient' => 0,
                    ];
                }
                $report['by_lga'][$lgaName]['total']++;
                if ($outpatientEligible) {
                    $report['by_lga'][$lgaName]['eligible_outpatient']++;
                }
                if ($inpatientEligible) {
                    $report['by_lga'][$lgaName]['eligible_inpatient']++;
                }
            }

            $report['members'][] = [
                'id' => $member->id,
                'name' => $member->full_name,
                'registration_no' => $member->registration_no,
                'state' => $member->state?->name,
                'lga' => $member->lga?->name,
                'status' => $member->status,
                'outpatient_eligible' => $outpatientEligible,
                'inpatient_eligible' => $inpatientEligible,
                'eligibility_start_date' => $member->calculateEligibilityStartDate(),
            ];
        }

        return $report;
    }
}
