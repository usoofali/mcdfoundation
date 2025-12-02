<?php

namespace App\Services;

use App\Models\CashoutRequest;
use App\Models\FundLedger;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CashoutService
{
    /**
     * Check if member is eligible for cashout.
     */
    public function isEligible(Member $member): bool
    {
        $reasons = $this->getEligibilityReasons($member);
        return empty($reasons);
    }

    /**
     * Get detailed eligibility reasons.
     */
    public function getEligibilityReasons(Member $member): array
    {
        $reasons = [];
        $settingService = app(SettingService::class);
        $cashoutSettings = $settingService->get('cashout_settings', [
            'min_membership_months' => 12,
            'min_contributions_required' => 6,
        ]);

        // Check if member is active
        if ($member->status !== 'active') {
            $reasons[] = 'Member must be active';
        }

        // Check for pending cashout request
        if ($member->has_pending_cashout) {
            $reasons[] = 'Member already has a pending cashout request';
        }

        // Check minimum membership period
        $membershipMonths = $member->registration_date?->diffInMonths(now()) ?? 0;
        $minMonths = $cashoutSettings['min_membership_months'];
        if ($membershipMonths < $minMonths) {
            $reasons[] = "Minimum membership period of {$minMonths} months not met (current: {$membershipMonths} months)";
        }

        // Check bank account details
        if (!$member->user?->account_number || !$member->user?->bank_name) {
            $reasons[] = 'Bank account details not provided';
        }

        // Check for active loans
        $activeLoans = $member->loans()
            ->whereIn('status', ['approved', 'disbursed'])
            ->exists();
        if ($activeLoans) {
            $reasons[] = 'Member has active loans that must be fully repaid first';
        }

        // Check for pending/approved health claims
        $pendingClaims = $member->healthClaims()
            ->whereIn('status', ['submitted', 'approved'])
            ->exists();
        if ($pendingClaims) {
            $reasons[] = 'Member has pending or approved health claims';
        }

        // Check for active program enrollments
        $activeEnrollments = $member->programEnrollments()
            ->where('status', 'enrolled')
            ->exists();
        if ($activeEnrollments) {
            $reasons[] = 'Member is enrolled in active vocational programs';
        }

        // Check minimum contributions
        $contributionCount = $member->contributions()->where('status', 'paid')->count();
        $minContributions = $cashoutSettings['min_contributions_required'];
        if ($contributionCount < $minContributions) {
            $reasons[] = "Minimum {$minContributions} contributions required (current: {$contributionCount})";
        }

        // Check if there's any amount to cashout
        if ($member->cashout_eligible_amount <= 0) {
            $reasons[] = 'No funds available for cashout';
        }

        return $reasons;
    }

    /**
     * Check cashout eligibility with full details.
     */
    public function checkCashoutEligibility(Member $member): array
    {
        $reasons = $this->getEligibilityReasons($member);

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
            'eligible_amount' => $member->cashout_eligible_amount,
            'total_contributions' => $member->total_contributions,
            'total_fines_paid' => $member->total_fines_paid,
            'contribution_count' => $member->contributions()->where('status', 'paid')->count(),
            'membership_months' => $member->registration_date?->diffInMonths(now()) ?? 0,
        ];
    }

    /**
     * Create a cashout request.
     */
    public function createRequest(Member $member, array $data): CashoutRequest
    {
        if (!$this->isEligible($member)) {
            throw new \Exception('Member is not eligible for cashout');
        }

        return DB::transaction(function () use ($member, $data) {
            // Get bank details from user
            $user = $member->user;

            $request = CashoutRequest::create([
                'member_id' => $member->id,
                'requested_amount' => $member->cashout_eligible_amount,
                'account_number' => $user->account_number,
                'account_name' => $user->account_name,
                'bank_name' => $user->bank_name,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);

            return $request;
        });
    }

    /**
     * Verify a cashout request.
     */
    public function verifyRequest(CashoutRequest $request, bool $approved, ?string $notes = null): bool
    {
        if (!$approved) {
            return $request->reject($notes ?? 'Verification failed');
        }

        return $request->markAsVerified(auth()->user(), $notes);
    }

    /**
     * Approve a cashout request.
     */
    public function approveRequest(CashoutRequest $request, float $amount, ?string $notes = null): bool
    {
        return $request->markAsApproved(auth()->user(), $amount, $notes);
    }

    /**
     * Disburse a cashout request.
     */
    public function disburseRequest(CashoutRequest $request, string $reference): bool
    {
        return DB::transaction(function () use ($request, $reference) {
            $result = $request->markAsDisbursed(auth()->user(), $reference);

            if ($result) {
                $this->processCashout($request);
            }

            return $result;
        });
    }

    /**
     * Reject a cashout request.
     */
    public function rejectRequest(CashoutRequest $request, string $reason): bool
    {
        return $request->reject($reason);
    }

    /**
     * Process cashout after disbursement.
     */
    protected function processCashout(CashoutRequest $request): void
    {
        $member = $request->member;

        // 1. Create FundLedger outflow entry
        FundLedger::create([
            'type' => 'outflow',
            'member_id' => $member->id,
            'source' => 'cashout',
            'amount' => $request->approved_amount,
            'description' => "Member cashout - Request #" . $request->id,
            'transaction_date' => now()->toDateString(),
            'reference' => $request->disbursement_reference,
            'created_by' => auth()->id(),
        ]);

        // 2. Update member: last_cashout_date, increment cashout_count
        $member->update([
            'last_cashout_date' => now(),
            'cashout_count' => $member->cashout_count + 1,
            'eligibility_start_date' => null, // Reset eligibility
        ]);

        // 3. Archive all paid contributions (mark as processed/cleared)
        // This prevents double-counting on future cashouts
        // Note: We don't delete them, just mark them as cleared
        $member->contributions()
            ->where('status', 'paid')
            ->update(['notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [Cashout processed: " . now()->toDateString() . "]')")]);
    }

    /**
     * Get cashout requests with filters.
     */
    public function getCashoutRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CashoutRequest::with(['member', 'verifier', 'approver', 'disburser']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('disbursement_reference', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get member's cashout history.
     */
    public function getMemberCashoutHistory(Member $member): Collection
    {
        return $member->cashoutRequests()
            ->with(['verifier', 'approver', 'disburser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get cashout statistics.
     */
    public function getCashoutStats(array $filters = []): array
    {
        $query = CashoutRequest::query();

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $totalRequests = $query->count();
        $pendingRequests = (clone $query)->where('status', 'pending')->count();
        $verifiedRequests = (clone $query)->where('status', 'verified')->count();
        $approvedRequests = (clone $query)->where('status', 'approved')->count();
        $disbursedRequests = (clone $query)->where('status', 'disbursed')->count();
        $rejectedRequests = (clone $query)->where('status', 'rejected')->count();

        $totalDisbursed = (clone $query)->where('status', 'disbursed')->sum('approved_amount');
        $averageCashout = $disbursedRequests > 0 ? $totalDisbursed / $disbursedRequests : 0;

        return [
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'verified_requests' => $verifiedRequests,
            'approved_requests' => $approvedRequests,
            'disbursed_requests' => $disbursedRequests,
            'rejected_requests' => $rejectedRequests,
            'total_disbursed' => $totalDisbursed,
            'average_cashout' => $averageCashout,
        ];
    }
}
