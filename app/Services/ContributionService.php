<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\FundLedger;
use App\Models\Member;
use App\Notifications\ContributionSubmitted;
use App\Notifications\ContributionVerified;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ContributionService
{
    /**
     * Record a new contribution.
     */
    public function recordContribution(array $data): Contribution
    {
        return DB::transaction(function () use ($data) {
            // Create the contribution
            $contribution = Contribution::create($data);

            // Create fund ledger entry
            $this->createFundLedgerEntry($contribution);

            return $contribution;
        });
    }

    /**
     * Update an existing contribution.
     */
    public function updateContribution(Contribution $contribution, array $data): bool
    {
        return DB::transaction(function () use ($contribution, $data) {
            $oldAmount = $contribution->amount;
            $oldFineAmount = $contribution->fine_amount;

            $updated = $contribution->update($data);

            if ($updated) {
                // Update fund ledger entry if amount changed
                if ($contribution->isDirty(['amount', 'fine_amount'])) {
                    $this->updateFundLedgerEntry($contribution, $oldAmount, $oldFineAmount);
                }
            }

            return $updated;
        });
    }

    /**
     * Delete a contribution.
     */
    public function deleteContribution(Contribution $contribution): ?bool
    {
        return DB::transaction(function () use ($contribution) {
            // Delete fund ledger entry
            $this->deleteFundLedgerEntry($contribution);

            // Delete the contribution
            return $contribution->delete();
        });
    }

    /**
     * Get contributions with filters.
     */
    public function getContributions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Contribution::with(['member', 'contributionPlan', 'collector']);

        // Apply filters
        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['period_from'])) {
            $query->where('period_start', '>=', $filters['period_from']);
        }

        if (isset($filters['period_to'])) {
            $query->where('period_end', '<=', $filters['period_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('payment_date', 'desc')->paginate($perPage);
    }

    /**
     * Get member's contribution history.
     */
    public function getMemberContributions(Member $member, int $perPage = 15): LengthAwarePaginator
    {
        return $member->contributions()
            ->with(['contributionPlan', 'collector'])
            ->orderBy('payment_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get overdue contributions.
     */
    public function getOverdueContributions(int $perPage = 15): LengthAwarePaginator
    {
        return Contribution::overdue()
            ->with(['member', 'contributionPlan'])
            ->orderBy('period_end', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get contribution statistics.
     */
    public function getContributionStats(array $filters = []): array
    {
        $query = Contribution::query();

        // Apply same filters as getContributions
        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        $totalContributions = $query->count();
        $totalAmount = $query->sum('amount');
        $totalFines = $query->sum('fine_amount');
        $paidContributions = $query->where('status', 'paid')->count();
        $pendingContributions = $query->where('status', 'pending')->count();
        $overdueContributions = $query->where('status', 'overdue')->count();

        return [
            'total_contributions' => $totalContributions,
            'total_amount' => $totalAmount,
            'total_fines' => $totalFines,
            'total_with_fines' => $totalAmount + $totalFines,
            'paid_contributions' => $paidContributions,
            'pending_contributions' => $pendingContributions,
            'overdue_contributions' => $overdueContributions,
            'average_contribution' => $totalContributions > 0 ? $totalAmount / $totalContributions : 0,
        ];
    }

    /**
     * Mark contributions as overdue.
     */
    public function markOverdueContributions(): int
    {
        $overdueCount = Contribution::where('status', 'pending')
            ->where('period_end', '<', now())
            ->update([
                'status' => 'overdue',
                'fine_amount' => DB::raw('amount * 0.5'), // 50% fine
            ]);

        return $overdueCount;
    }

    /**
     * Generate contribution report.
     */
    public function generateReport(array $filters = []): Collection
    {
        $query = Contribution::with(['member', 'contributionPlan', 'collector']);

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

        return $query->orderBy('payment_date', 'desc')->get();
    }

    /**
     * Submit a member contribution with receipt upload.
     */
    public function submitMemberContribution(array $data, $receiptFile): Contribution
    {
        return DB::transaction(function () use ($data, $receiptFile) {
            // Store the receipt file
            $receiptPath = $receiptFile->store('contribution-receipts', 'public');

            // Add receipt path and uploaded_by to data
            $data['receipt_path'] = $receiptPath;
            $data['uploaded_by'] = auth()->id();
            $data['status'] = 'pending';
            $data['collected_by'] = null; // Will be set when verified

            // Create the contribution
            $contribution = Contribution::create($data);

            // Generate receipt number
            $contribution->update([
                'receipt_number' => Contribution::generateReceiptNumber(),
            ]);

            // Send notification to staff with verification permissions
            $this->notifyStaffOfNewContribution($contribution);

            return $contribution;
        });
    }

    /**
     * Verify a member-submitted contribution.
     */
    public function verifyContribution(Contribution $contribution, bool $approved, ?string $notes = null): bool
    {
        if ($contribution->status !== 'pending') {
            throw new \Exception('Only pending contributions can be verified');
        }

        if (! $contribution->is_member_submitted) {
            throw new \Exception('Only member-submitted contributions can be verified');
        }

        return DB::transaction(function () use ($contribution, $approved, $notes) {
            $newStatus = $approved ? 'paid' : 'cancelled';

            $contribution->update([
                'status' => $newStatus,
                'collected_by' => auth()->id(),
                'verification_notes' => $notes,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);

            // Create fund ledger entry only if approved
            if ($approved) {
                $this->createFundLedgerEntry($contribution);
            }

            // Send notification to member about verification result
            $this->notifyMemberOfVerification($contribution, $approved, $notes);

            return true;
        });
    }

    /**
     * Get contributions pending verification.
     */
    public function getPendingVerifications(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Contribution::pendingVerification()
            ->with(['member', 'contributionPlan', 'uploader']);

        // Apply filters
        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'asc')->paginate($perPage);
    }

    /**
     * Get member's pending contributions.
     */
    public function getMemberPendingContributions(Member $member): Collection
    {
        return $member->contributions()
            ->where('status', 'pending')
            ->where('uploaded_by', $member->user_id)
            ->with(['contributionPlan'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete a member-submitted contribution (only if pending).
     */
    public function deleteMemberContribution(Contribution $contribution): bool
    {
        if ($contribution->status !== 'pending') {
            throw new \Exception('Only pending contributions can be deleted');
        }

        if (! $contribution->is_member_submitted) {
            throw new \Exception('Only member-submitted contributions can be deleted');
        }

        if ($contribution->uploaded_by !== auth()->id()) {
            throw new \Exception('You can only delete your own contributions');
        }

        return DB::transaction(function () use ($contribution) {
            // Delete the receipt file
            if ($contribution->receipt_path) {
                Storage::disk('public')->delete($contribution->receipt_path);
            }

            // Delete the contribution
            return $contribution->delete();
        });
    }

    /**
     * Create fund ledger entry for contribution.
     */
    protected function createFundLedgerEntry(Contribution $contribution): FundLedger
    {
        return FundLedger::create([
            'type' => 'inflow',
            'member_id' => $contribution->member_id,
            'source' => 'contribution',
            'amount' => $contribution->total_amount,
            'description' => "Contribution for {$contribution->contributionPlan->label} plan - Receipt: {$contribution->receipt_number}",
            'transaction_date' => $contribution->payment_date,
            'reference' => $contribution->receipt_number,
            'created_by' => $contribution->collected_by ?? $contribution->verified_by ?? auth()->id(),
        ]);
    }

    /**
     * Update fund ledger entry for contribution.
     */
    protected function updateFundLedgerEntry(Contribution $contribution, float $oldAmount, float $oldFineAmount): void
    {
        $ledgerEntry = FundLedger::where('reference', $contribution->receipt_number)
            ->where('source', 'contribution')
            ->first();

        if ($ledgerEntry) {
            $oldTotal = $oldAmount + $oldFineAmount;
            $newTotal = $contribution->total_amount;
            $difference = $newTotal - $oldTotal;

            if ($difference != 0) {
                // Create adjustment entry
                FundLedger::create([
                    'type' => $difference > 0 ? 'inflow' : 'outflow',
                    'member_id' => $contribution->member_id,
                    'source' => 'contribution_adjustment',
                    'amount' => abs($difference),
                    'description' => "Adjustment for contribution {$contribution->receipt_number}",
                    'transaction_date' => now()->toDateString(),
                    'reference' => $contribution->receipt_number,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Delete fund ledger entry for contribution.
     */
    protected function deleteFundLedgerEntry(Contribution $contribution): void
    {
        FundLedger::where('reference', $contribution->receipt_number)
            ->where('source', 'contribution')
            ->delete();
    }

    /**
     * Validate contribution data.
     */
    public function validateContributionData(array $data): array
    {
        $rules = [
            'member_id' => 'required|exists:members,id',
            'contribution_plan_id' => 'required|exists:contribution_plans,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,bank_deposit,mobile_money',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'required|date|before_or_equal:today',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'status' => 'required|in:paid,pending,overdue,cancelled',
            'collected_by' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ];

        return validator($data, $rules)->validate();
    }

    /**
     * Notify staff members about new contribution submission.
     */
    protected function notifyStaffOfNewContribution(Contribution $contribution): void
    {
        // Get all users with confirm_contributions permission
        $staffUsers = \App\Models\User::whereHas('role.permissions', function ($query) {
            $query->where('name', 'confirm_contributions');
        })->get();

        // Send notification to all staff
        Notification::send($staffUsers, new ContributionSubmitted($contribution));
    }

    /**
     * Notify member about contribution verification result.
     */
    protected function notifyMemberOfVerification(Contribution $contribution, bool $approved, ?string $notes): void
    {
        // Get the member's user account
        $memberUser = $contribution->member->user;

        if ($memberUser) {
            $memberUser->notify(new ContributionVerified($contribution, $approved, $notes));
        }
    }
}
