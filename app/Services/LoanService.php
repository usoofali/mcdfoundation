<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\FundLedger;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a new loan application.
     */
    public function createLoanApplication(array $data): Loan
    {
        return DB::transaction(function () use ($data) {
            $loan = Loan::create($data);

            // Create initial approval record
            $this->createInitialApproval($loan);

            return $loan;
        });
    }

    /**
     * Update an existing loan.
     */
    public function updateLoan(Loan $loan, array $data): bool
    {
        return $loan->update($data);
    }

    /**
     * Delete a loan application (only if pending).
     */
    public function deleteLoan(Loan $loan): ?bool
    {
        if ($loan->status !== 'pending') {
            throw new \Exception('Only pending loans can be deleted');
        }

        return DB::transaction(function () use ($loan) {
            // Delete related approvals
            $loan->approvals()->delete();

            // Delete the loan
            return $loan->delete();
        });
    }

    /**
     * Approve a loan at a specific level.
     */
    public function approveLoan(Loan $loan, int $level, ?string $remarks = null): bool
    {
        return DB::transaction(function () use ($loan, $level, $remarks) {
            // Update approval record
            $approval = $loan->approvals()
                ->where('approval_level', $level)
                ->where('status', 'pending')
                ->first();

            if (! $approval) {
                throw new \Exception('No pending approval found for this level');
            }

            $approval->approve($remarks);

            // If this is the final approval level (3), mark loan as approved
            if ($level === 3) {
                $loan->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approval_date' => now()->toDateString(),
                ]);
            }

            return true;
        });
    }

    /**
     * Reject a loan at a specific level.
     */
    public function rejectLoan(Loan $loan, int $level, ?string $remarks = null): bool
    {
        return DB::transaction(function () use ($loan, $level, $remarks) {
            // Update approval record
            $approval = $loan->approvals()
                ->where('approval_level', $level)
                ->where('status', 'pending')
                ->first();

            if (! $approval) {
                throw new \Exception('No pending approval found for this level');
            }

            $approval->reject($remarks);

            // Mark loan as rejected
            $loan->update(['status' => 'rejected']);

            return true;
        });
    }

    /**
     * Disburse a loan.
     */
    public function disburseLoan(Loan $loan, ?string $remarks = null): bool
    {
        if ($loan->status !== 'approved') {
            throw new \Exception('Only approved loans can be disbursed');
        }

        return DB::transaction(function () use ($loan, $remarks) {
            // Update loan status
            $loan->update([
                'status' => 'disbursed',
                'disbursement_date' => now()->toDateString(),
                'remarks' => $remarks,
            ]);

            // Create fund ledger entry (outflow)
            FundLedger::create([
                'type' => 'outflow',
                'member_id' => $loan->member_id,
                'source' => 'loan_disbursement',
                'amount' => $loan->amount,
                'description' => "Loan disbursement - {$loan->loan_type_label} - Loan ID: {$loan->id}",
                'transaction_date' => now()->toDateString(),
                'reference' => "LOAN-{$loan->id}",
                'created_by' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Record a loan repayment.
     */
    public function recordRepayment(Loan $loan, array $data): LoanRepayment
    {
        return DB::transaction(function () use ($loan, $data) {
            // Create repayment record
            $repayment = LoanRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'received_by' => auth()->id(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            // Create fund ledger entry (inflow)
            FundLedger::create([
                'type' => 'inflow',
                'member_id' => $loan->member_id,
                'source' => 'loan_repayment',
                'amount' => $data['amount'],
                'description' => "Loan repayment - Loan ID: {$loan->id}",
                'transaction_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? "REPAY-{$repayment->id}",
                'created_by' => auth()->id(),
            ]);

            // Check if loan is fully repaid
            if ($loan->outstanding_balance <= 0) {
                $loan->update(['status' => 'repaid']);
            }

            return $repayment;
        });
    }

    /**
     * Get loans with filters.
     */
    public function getLoans(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Loan::with(['member', 'approver', 'repayments']);

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

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('guarantor_name', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get member's loan history.
     */
    public function getMemberLoans(Member $member, int $perPage = 15): LengthAwarePaginator
    {
        return $member->loans()
            ->with(['approver', 'repayments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending approvals for a specific level.
     */
    public function getPendingApprovals(int $level): Collection
    {
        return Approval::with(['entity', 'approver'])
            ->where('entity_type', 'Loan')
            ->where('approval_level', $level)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get loan statistics.
     */
    public function getLoanStats(array $filters = []): array
    {
        $query = Loan::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $totalLoans = $query->count();
        $totalAmount = $query->sum('amount');
        $pendingLoans = $query->where('status', 'pending')->count();
        $approvedLoans = $query->where('status', 'approved')->count();
        $disbursedLoans = $query->where('status', 'disbursed')->count();
        $repaidLoans = $query->where('status', 'repaid')->count();
        $defaultedLoans = $query->where('status', 'defaulted')->count();

        // Calculate outstanding balance
        $outstandingBalance = $query->whereIn('status', ['disbursed', 'defaulted'])
            ->get()
            ->sum('outstanding_balance');

        return [
            'total_loans' => $totalLoans,
            'total_amount' => $totalAmount,
            'pending_loans' => $pendingLoans,
            'approved_loans' => $approvedLoans,
            'disbursed_loans' => $disbursedLoans,
            'repaid_loans' => $repaidLoans,
            'defaulted_loans' => $defaultedLoans,
            'outstanding_balance' => $outstandingBalance,
            'average_loan_amount' => $totalLoans > 0 ? $totalAmount / $totalLoans : 0,
        ];
    }

    /**
     * Mark overdue loans as defaulted.
     */
    public function markOverdueLoansAsDefaulted(): int
    {
        $overdueLoans = Loan::where('status', 'disbursed')
            ->where('start_date', '<', now()->subMonths(6)) // Assuming 6 months is the repayment period
            ->get();

        $count = 0;
        foreach ($overdueLoans as $loan) {
            if ($loan->is_overdue) {
                $loan->update(['status' => 'defaulted']);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Create initial approval record for a loan.
     */
    protected function createInitialApproval(Loan $loan): Approval
    {
        return Approval::create([
            'entity_type' => 'Loan',
            'entity_id' => $loan->id,
            'approved_by' => auth()->id(),
            'role' => auth()->user()->role->name ?? 'Unknown',
            'approval_level' => 1, // Start with LG level
            'status' => 'pending',
        ]);
    }

    /**
     * Validate loan application data.
     */
    public function validateLoanData(array $data): array
    {
        $rules = [
            'member_id' => 'required|exists:members,id',
            'loan_type' => 'required|in:cash,item',
            'item_description' => 'required_if:loan_type,item|nullable|string|max:255',
            'amount' => 'required|numeric|min:1000|max:1000000',
            'repayment_mode' => 'required|in:installments,full',
            'repayment_period' => 'required|string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'security_description' => 'nullable|string|max:1000',
            'guarantor_name' => 'nullable|string|max:150',
            'guarantor_contact' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ];

        return validator($data, $rules)->validate();
    }
}
