<?php

namespace App\Services;

use App\Models\HealthClaim;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HealthClaimService
{
    protected HealthEligibilityService $eligibilityService;

    protected FundLedgerService $fundLedgerService;

    public function __construct(HealthEligibilityService $eligibilityService, FundLedgerService $fundLedgerService)
    {
        $this->eligibilityService = $eligibilityService;
        $this->fundLedgerService = $fundLedgerService;
    }

    /**
     * Submit a new health claim.
     */
    public function submitClaim(array $data): HealthClaim
    {
        return DB::transaction(function () use ($data) {
            // Validate eligibility before creating claim
            $eligibilityValidation = $this->eligibilityService->validateClaimEligibility($data);

            if (! $eligibilityValidation['valid']) {
                throw new \Exception($eligibilityValidation['message']);
            }

            // Create the claim
            $claim = HealthClaim::create($data);

            return $claim;
        });
    }

    /**
     * Approve a health claim.
     */
    public function approveClaim(HealthClaim $claim, ?string $remarks = null): bool
    {
        if ($claim->status !== 'submitted') {
            throw new \Exception('Only submitted claims can be approved');
        }

        return DB::transaction(function () use ($claim, $remarks) {
            $claim->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'remarks' => $remarks,
            ]);

            return true;
        });
    }

    /**
     * Reject a health claim.
     */
    public function rejectClaim(HealthClaim $claim, ?string $remarks = null): bool
    {
        if ($claim->status !== 'submitted') {
            throw new \Exception('Only submitted claims can be rejected');
        }

        return DB::transaction(function () use ($claim, $remarks) {
            $claim->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'remarks' => $remarks,
            ]);

            return true;
        });
    }

    /**
     * Process payment for an approved claim.
     */
    public function payClaim(HealthClaim $claim, ?string $remarks = null): bool
    {
        if ($claim->status !== 'approved') {
            throw new \Exception('Only approved claims can be paid');
        }

        return DB::transaction(function () use ($claim, $remarks) {
            // Update claim status
            $claim->update([
                'status' => 'paid',
                'paid_by' => auth()->id(),
                'paid_date' => now()->toDateString(),
                'remarks' => $remarks,
            ]);

            // Create fund ledger entry (outflow)
            $this->fundLedgerService->recordOutflow([
                'member_id' => $claim->member_id,
                'source' => 'health_claim',
                'amount' => $claim->covered_amount,
                'description' => "Health claim payment - {$claim->claim_type_label} - Claim #{$claim->claim_number}",
                'transaction_date' => now()->toDateString(),
                'reference' => $claim->claim_number,
                'created_by' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Update an existing claim.
     */
    public function updateClaim(HealthClaim $claim, array $data): bool
    {
        if ($claim->status !== 'submitted') {
            throw new \Exception('Only submitted claims can be updated');
        }

        return $claim->update($data);
    }

    /**
     * Delete a claim (only if submitted).
     */
    public function deleteClaim(HealthClaim $claim): ?bool
    {
        if ($claim->status !== 'submitted') {
            throw new \Exception('Only submitted claims can be deleted');
        }

        return $claim->delete();
    }

    /**
     * Get claims with filters.
     */
    public function getClaims(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = HealthClaim::with(['member', 'healthcareProvider', 'approver', 'payer']);

        // Apply filters
        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['claim_type'])) {
            $query->where('claim_type', $filters['claim_type']);
        }

        if (isset($filters['healthcare_provider_id'])) {
            $query->where('healthcare_provider_id', $filters['healthcare_provider_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('claim_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('claim_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('claim_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('healthcareProvider', function ($providerQuery) use ($search) {
                        $providerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get member's claim history.
     */
    public function getMemberClaims(Member $member, int $perPage = 15): LengthAwarePaginator
    {
        return $member->healthClaims()
            ->with(['healthcareProvider', 'approver', 'payer'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get claim statistics.
     */
    public function getClaimStats(array $filters = []): array
    {
        $query = HealthClaim::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('claim_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('claim_date', '<=', $filters['date_to']);
        }

        $totalClaims = $query->count();
        $totalBilledAmount = $query->sum('billed_amount');
        $totalCoveredAmount = $query->sum('covered_amount');
        $totalCopayAmount = $query->sum('copay_amount');

        $submittedClaims = $query->where('status', 'submitted')->count();
        $approvedClaims = $query->where('status', 'approved')->count();
        $paidClaims = $query->where('status', 'paid')->count();
        $rejectedClaims = $query->where('status', 'rejected')->count();

        // Breakdown by claim type
        $byClaimType = $query->selectRaw('claim_type, COUNT(*) as count, SUM(billed_amount) as total_billed, SUM(covered_amount) as total_covered')
            ->groupBy('claim_type')
            ->get()
            ->keyBy('claim_type');

        return [
            'total_claims' => $totalClaims,
            'total_billed_amount' => $totalBilledAmount,
            'total_covered_amount' => $totalCoveredAmount,
            'total_copay_amount' => $totalCopayAmount,
            'submitted_claims' => $submittedClaims,
            'approved_claims' => $approvedClaims,
            'paid_claims' => $paidClaims,
            'rejected_claims' => $rejectedClaims,
            'by_claim_type' => $byClaimType,
            'average_claim_amount' => $totalClaims > 0 ? $totalBilledAmount / $totalClaims : 0,
            'coverage_rate' => $totalBilledAmount > 0 ? ($totalCoveredAmount / $totalBilledAmount) * 100 : 0,
        ];
    }

    /**
     * Get pending claims for approval.
     */
    public function getPendingClaims(): Collection
    {
        return HealthClaim::with(['member', 'healthcareProvider'])
            ->where('status', 'submitted')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get approved claims ready for payment.
     */
    public function getApprovedClaims(): Collection
    {
        return HealthClaim::with(['member', 'healthcareProvider'])
            ->where('status', 'approved')
            ->orderBy('approved_at', 'asc')
            ->get();
    }

    /**
     * Get claim types with their requirements.
     */
    public function getClaimTypes(): array
    {
        return [
            'outpatient' => [
                'label' => 'Outpatient',
                'description' => 'General outpatient services',
                'requirement' => '60 days registration + any contribution',
                'coverage' => '90%',
            ],
            'inpatient' => [
                'label' => 'Inpatient',
                'description' => 'Hospital admission services',
                'requirement' => '60 days registration + 5 months contributions',
                'coverage' => '90%',
            ],
            'surgery' => [
                'label' => 'Surgery',
                'description' => 'Surgical procedures',
                'requirement' => '60 days registration + 5 months contributions',
                'coverage' => '90%',
            ],
            'maternity' => [
                'label' => 'Maternity',
                'description' => 'Maternity and childbirth services',
                'requirement' => '60 days registration + 5 months contributions',
                'coverage' => '90%',
            ],
        ];
    }
}
