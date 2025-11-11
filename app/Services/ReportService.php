<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\FundLedger;
use App\Models\HealthClaim;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    /**
     * Generate membership report.
     */
    public function generateMembershipReport(array $filters = []): array
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

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $members = $query->with(['state', 'lga', 'dependents', 'contributions'])->get();

        $report = [
            'total_members' => $members->count(),
            'by_status' => $members->groupBy('status')->map->count(),
            'by_state' => $members->groupBy('state.name')->map->count(),
            'by_lga' => $members->groupBy('lga.name')->map->count(),
            'total_dependents' => $members->sum(fn ($m) => $m->dependents->count()),
            'eligible_members' => $members->filter(fn ($m) => $m->checkHealthEligibility('outpatient')['eligible'])->count(),
            'new_registrations' => $members->where('created_at', '>=', now()->subMonth())->count(),
            'members' => $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'registration_no' => $member->registration_no,
                    'full_name' => $member->full_name,
                    'phone' => $member->phone,
                    'state' => $member->state?->name,
                    'lga' => $member->lga?->name,
                    'status' => $member->status,
                    'registration_date' => $member->created_at->format('Y-m-d'),
                    'dependents_count' => $member->dependents->count(),
                    'contributions_count' => $member->contributions->where('status', 'paid')->count(),
                    'is_eligible' => $member->checkHealthEligibility('outpatient')['eligible'],
                ];
            }),
        ];

        return $report;
    }

    /**
     * Generate contribution report.
     */
    public function generateContributionReport(array $filters = []): array
    {
        $query = Contribution::query();

        // Apply filters
        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        $contributions = $query->with(['member', 'contributionPlan'])->get();

        $report = [
            'total_contributions' => $contributions->count(),
            'total_amount' => $contributions->sum('amount'),
            'total_fines' => $contributions->sum('fine_amount'),
            'by_status' => $contributions->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ];
            }),
            'by_plan' => $contributions->groupBy('contributionPlan.name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ];
            }),
            'by_month' => $contributions->groupBy(function ($contribution) {
                return $contribution->payment_date->format('Y-m');
            })->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ];
            }),
            'defaulters' => $this->getDefaulters(),
            'contributions' => $contributions->map(function ($contribution) {
                return [
                    'id' => $contribution->id,
                    'receipt_number' => $contribution->receipt_number,
                    'member_name' => $contribution->member->full_name,
                    'member_registration' => $contribution->member->registration_no,
                    'plan_name' => $contribution->contributionPlan->label ?? 'N/A',
                    'amount' => $contribution->amount,
                    'fine_amount' => $contribution->fine_amount,
                    'total_amount' => $contribution->total_amount,
                    'payment_date' => $contribution->payment_date->format('Y-m-d'),
                    'status' => $contribution->status,
                    'payment_method' => $contribution->payment_method,
                ];
            }),
        ];

        return $report;
    }

    /**
     * Generate loan report.
     */
    public function generateLoanReport(array $filters = []): array
    {
        $query = Loan::query();

        // Apply filters
        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['loan_type'])) {
            $query->where('loan_type', $filters['loan_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $loans = $query->with(['member', 'repayments'])->get();

        $report = [
            'total_loans' => $loans->count(),
            'total_amount' => $loans->sum('amount'),
            'total_disbursed' => $loans->where('status', 'disbursed')->sum('amount'),
            'total_repaid' => $loans->sum(fn ($l) => $l->repayments->sum('amount')),
            'outstanding_balance' => $loans->sum(fn ($l) => $l->outstanding_balance),
            'by_status' => $loans->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ];
            }),
            'by_type' => $loans->groupBy('loan_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ];
            }),
            'defaulters' => $loans->where('status', 'defaulted')->count(),
            'repayment_rate' => $this->calculateRepaymentRate($loans),
            'loans' => $loans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'member_name' => $loan->member->full_name,
                    'member_registration' => $loan->member->registration_no,
                    'loan_type' => $loan->loan_type_label,
                    'amount' => $loan->amount,
                    'outstanding_balance' => $loan->outstanding_balance,
                    'status' => $loan->status_label,
                    'start_date' => $loan->start_date->format('Y-m-d'),
                    'repayment_count' => $loan->repayments->count(),
                    'is_overdue' => $loan->is_overdue,
                ];
            }),
        ];

        return $report;
    }

    /**
     * Generate health claims report.
     */
    public function generateHealthClaimsReport(array $filters = []): array
    {
        $query = HealthClaim::query();

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

        if (isset($filters['date_from'])) {
            $query->where('claim_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('claim_date', '<=', $filters['date_to']);
        }

        $claims = $query->with(['member', 'healthcareProvider'])->get();

        $report = [
            'total_claims' => $claims->count(),
            'total_billed_amount' => $claims->sum('billed_amount'),
            'total_covered_amount' => $claims->sum('covered_amount'),
            'total_copay_amount' => $claims->sum('copay_amount'),
            'by_status' => $claims->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'billed_amount' => $group->sum('billed_amount'),
                    'covered_amount' => $group->sum('covered_amount'),
                ];
            }),
            'by_type' => $claims->groupBy('claim_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'billed_amount' => $group->sum('billed_amount'),
                    'covered_amount' => $group->sum('covered_amount'),
                ];
            }),
            'pending_approvals' => $claims->where('status', 'submitted')->count(),
            'coverage_rate' => $claims->sum('billed_amount') > 0 ?
                ($claims->sum('covered_amount') / $claims->sum('billed_amount')) * 100 : 0,
            'claims' => $claims->map(function ($claim) {
                return [
                    'id' => $claim->id,
                    'claim_number' => $claim->claim_number,
                    'member_name' => $claim->member->full_name,
                    'member_registration' => $claim->member->registration_no,
                    'provider_name' => $claim->healthcareProvider->name,
                    'claim_type' => $claim->claim_type_label,
                    'billed_amount' => $claim->billed_amount,
                    'covered_amount' => $claim->covered_amount,
                    'copay_amount' => $claim->copay_amount,
                    'claim_date' => $claim->claim_date->format('Y-m-d'),
                    'status' => $claim->status_label,
                ];
            }),
        ];

        return $report;
    }

    /**
     * Generate fund ledger report.
     */
    public function generateFundLedgerReport(array $filters = []): array
    {
        $query = FundLedger::query();

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        $transactions = $query->with(['member', 'creator'])->get();

        $inflows = $transactions->where('type', 'inflow');
        $outflows = $transactions->where('type', 'outflow');

        $report = [
            'total_transactions' => $transactions->count(),
            'total_inflows' => $inflows->sum('amount'),
            'total_outflows' => $outflows->sum('amount'),
            'current_balance' => FundLedger::getCurrentBalance(),
            'by_source' => $transactions->groupBy('source')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                    'inflows' => $group->where('type', 'inflow')->sum('amount'),
                    'outflows' => $group->where('type', 'outflow')->sum('amount'),
                ];
            }),
            'by_month' => $transactions->groupBy(function ($transaction) {
                return $transaction->transaction_date->format('Y-m');
            })->map(function ($group) {
                return [
                    'inflows' => $group->where('type', 'inflow')->sum('amount'),
                    'outflows' => $group->where('type', 'outflow')->sum('amount'),
                    'net' => $group->where('type', 'inflow')->sum('amount') - $group->where('type', 'outflow')->sum('amount'),
                ];
            }),
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'source' => $transaction->source,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                    'member_name' => $transaction->member?->full_name,
                    'reference' => $transaction->reference,
                    'created_by' => $transaction->creator->name,
                ];
            }),
        ];

        return $report;
    }

    /**
     * Generate eligibility report.
     */
    public function generateEligibilityReport(array $filters = []): array
    {
        $query = Member::query();

        // Apply filters
        if (isset($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        if (isset($filters['lga_id'])) {
            $query->where('lga_id', $filters['lga_id']);
        }

        $members = $query->with(['state', 'lga', 'contributions'])->get();

        $eligibleOutpatient = $members->filter(fn ($m) => $m->checkHealthEligibility('outpatient')['eligible']);
        $eligibleInpatient = $members->filter(fn ($m) => $m->checkHealthEligibility('inpatient')['eligible']);

        $report = [
            'total_members' => $members->count(),
            'eligible_outpatient' => $eligibleOutpatient->count(),
            'eligible_inpatient' => $eligibleInpatient->count(),
            'not_eligible' => $members->count() - $eligibleOutpatient->count(),
            'eligibility_rate_outpatient' => $members->count() > 0 ?
                ($eligibleOutpatient->count() / $members->count()) * 100 : 0,
            'eligibility_rate_inpatient' => $members->count() > 0 ?
                ($eligibleInpatient->count() / $members->count()) * 100 : 0,
            'by_state' => $members->groupBy('state.name')->map(function ($group) {
                $eligibleOutpatient = $group->filter(fn ($m) => $m->checkHealthEligibility('outpatient')['eligible']);
                $eligibleInpatient = $group->filter(fn ($m) => $m->checkHealthEligibility('inpatient')['eligible']);

                return [
                    'total' => $group->count(),
                    'eligible_outpatient' => $eligibleOutpatient->count(),
                    'eligible_inpatient' => $eligibleInpatient->count(),
                ];
            }),
            'upcoming_eligible' => $this->getUpcomingEligibleMembers(),
            'members' => $members->map(function ($member) {
                $outpatientEligibility = $member->checkHealthEligibility('outpatient');
                $inpatientEligibility = $member->checkHealthEligibility('inpatient');

                return [
                    'id' => $member->id,
                    'registration_no' => $member->registration_no,
                    'full_name' => $member->full_name,
                    'state' => $member->state?->name,
                    'lga' => $member->lga?->name,
                    'status' => $member->status,
                    'registration_date' => $member->created_at->format('Y-m-d'),
                    'days_since_registration' => $outpatientEligibility['days_since_registration'],
                    'contribution_count' => $outpatientEligibility['contribution_count'],
                    'outpatient_eligible' => $outpatientEligibility['eligible'],
                    'inpatient_eligible' => $inpatientEligibility['eligible'],
                    'eligibility_start_date' => $member->calculateEligibilityStartDate()?->format('Y-m-d'),
                ];
            }),
        ];

        return $report;
    }

    /**
     * Get members with overdue contributions.
     */
    protected function getDefaulters(): Collection
    {
        return Member::whereHas('contributions', function ($query) {
            $query->where('status', 'overdue');
        })->with(['contributions' => function ($query) {
            $query->where('status', 'overdue');
        }])->get();
    }

    /**
     * Calculate loan repayment rate.
     */
    protected function calculateRepaymentRate(Collection $loans): float
    {
        $disbursedLoans = $loans->where('status', 'disbursed');
        $totalDisbursed = $disbursedLoans->sum('amount');
        $totalRepaid = $disbursedLoans->sum(fn ($l) => $l->repayments->sum('amount'));

        return $totalDisbursed > 0 ? ($totalRepaid / $totalDisbursed) * 100 : 0;
    }

    /**
     * Get members who will become eligible soon.
     */
    protected function getUpcomingEligibleMembers(): Collection
    {
        $sixtyDaysFromNow = now()->addDays(60);

        return Member::where('status', 'active')
            ->where(function ($q) use ($sixtyDaysFromNow) {
                $q->where('registration_date', '<=', $sixtyDaysFromNow)
                    ->orWhere('created_at', '<=', $sixtyDaysFromNow);
            })
            ->with(['contributions'])
            ->get()
            ->filter(function ($member) {
                return $member->contributions()->where('status', 'paid')->count() >= 5;
            });
    }

    /**
     * Export data to array format for CSV/Excel.
     */
    public function exportToArray(string $reportType, array $filters = []): array
    {
        $method = 'generate'.ucfirst($reportType).'Report';

        if (! method_exists($this, $method)) {
            throw new \InvalidArgumentException("Report type '{$reportType}' not supported");
        }

        $report = $this->$method($filters);

        // Return the 'members', 'contributions', 'loans', 'claims', or 'transactions' array
        $dataKeyMap = [
            'membership' => 'members',
            'contribution' => 'contributions',
            'loan' => 'loans',
            'healthClaims' => 'claims',
            'fundLedger' => 'transactions',
            'eligibility' => 'members',
        ];

        $dataKey = $dataKeyMap[$reportType] ?? rtrim($reportType, 's').'s';
        $data = $report[$dataKey] ?? [];

        // Convert Collection to array if needed
        return is_array($data) ? $data : $data->toArray();
    }

    /**
     * Get available report types.
     */
    public function getAvailableReportTypes(): array
    {
        return [
            'membership' => 'Membership Report',
            'contribution' => 'Contribution Report',
            'loan' => 'Loan Report',
            'healthClaims' => 'Health Claims Report',
            'fundLedger' => 'Fund Ledger Report',
            'eligibility' => 'Eligibility Report',
        ];
    }
}
