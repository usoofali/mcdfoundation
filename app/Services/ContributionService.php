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

            // Link to expected contribution if exists
            if ($contribution->status === 'paid') {
                $expectedService = app(\App\Services\ExpectedContributionService::class);
                $expectedService->linkPayment($contribution);
            }

            return $contribution;
        });
    }

    /**
     * Update an existing contribution.
     */
    public function updateContribution(Contribution $contribution, array $data): bool
    {
        return DB::transaction(function () use ($contribution, $data) {
            $oldAmount = (float) $contribution->amount;
            $oldFineAmount = (float) $contribution->fine_amount;

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
    /**
     * Get contributions with filters.
     */
    public function getContributions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Contribution::with(['member', 'contributionPlan', 'collector'])
            ->whereHas('member', function ($q) {
                $q->forAuthUserLocation();
            });

        // Apply filters
        if (!empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        // Period filters temporarily disabled or removed as columns are gone
        // Could filter by matching ExpectedContribution periods if needed

        if (!empty($filters['search'])) {
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
     * Get overdue contributions.
     * NOW relies on ExpectedContribution service or model directly, 
     * but if this method MUST exist on Contribution service, it should fetch ExpectedContributions that are overdue.
     * However, Contribution model represents *payments*. Payments are rarely 'overdue' - EXPECTATIONS are.
     * This method seems to have been fetching Actual Contributions with status 'overdue', which might be a legacy concept if we move to Expected.
     * For now, we will update it to query based on status only, without sorting by period_end (unless joined).
     */
    public function getOverdueContributions(int $perPage = 15): LengthAwarePaginator
    {
        return Contribution::overdue()
            ->with(['member', 'contributionPlan'])
            ->orderBy('payment_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get contribution statistics.
     */
    public function getContributionStats(array $filters = []): array
    {
        $query = Contribution::query()
            ->whereHas('member', function ($q) {
                $q->forAuthUserLocation();
            });

        if (!empty($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        return [
            'total_amount' => $query->where('status', 'paid')->sum('amount'),
            'total_fines' => $query->where('status', 'paid')->sum('fine_amount'),
            'pending_count' => $query->where('status', 'pending')->count(),
            'paid_count' => $query->where('status', 'paid')->count(),
            'member_submitted_count' => $query->whereNotNull('receipt_path')->where('status', 'pending')->count(),
        ];
    }

    /**
     * Submit a contribution by a member.
     */
    public function submitMemberContribution(array $data, $receiptFile = null, array $expectedContributionIds = []): Contribution
    {
        return DB::transaction(function () use ($data, $receiptFile, $expectedContributionIds) {
            $data['status'] = 'pending';
            $data['uploaded_by'] = auth()->id();

            // Handle receipt upload
            if ($receiptFile) {
                $path = $receiptFile->store('receipts', 'public');
                $data['receipt_path'] = $path;
            }

            $contribution = Contribution::create($data);

            // Link to expected contributions
            if (!empty($expectedContributionIds)) {
                \App\Models\ExpectedContribution::whereIn('id', $expectedContributionIds)
                    ->update(['actual_contribution_id' => $contribution->id]);
            }

            $this->notifyStaffOfNewContribution($contribution);

            return $contribution;
        });
    }

    /**
     * Mark contributions as overdue and apply fines based on system settings.
     * NOTE: This logic seems flawed if 'Contribution' represents a PAYMENT.
     * Payments are created only when paid (or pending approval). 
     * 'Overdue' status usually belongs to ExpectedContribution.
     * The previous implementation was: Contribution::where('status', 'pending')->where('period_end', '<', now()).
     * If a Contribution is 'pending', it means a payment attempts was submitted but not verified.
     * Marking a *pending payment verification* as 'overdue' because the period passed is weird.
     * It should probably just stay pending until verified or rejected.
     * 
     * We will COMMENT OUT/REMOVE this logic as fine calculation is now handled by ExpectedContributionService.
     */
    public function markOverdueContributions(): int
    {
        // This functionality has moved to ExpectedContributionService
        // Pending contributions (payments) should not be marked overdue just because time passed.
        // They are waiting for verification.
        return 0;
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
            // period_start and period_end removed
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

    /**
     * Get pending verifications with filters.
     */
    public function getPendingVerifications(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Contribution::with(['member', 'contributionPlan', 'uploader'])
            ->pendingVerification()
            ->whereHas('member', function ($q) {
                $q->forAuthUserLocation();
            });

        // Apply filters
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
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
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Verify a contribution (approve or reject).
     */
    public function verifyContribution(Contribution $contribution, bool $approved, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($contribution, $approved, $notes) {
            $updateData = [
                'status' => $approved ? 'paid' : 'cancelled',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
                'verification_notes' => $notes,
            ];

            $contribution->update($updateData);

            if ($approved) {
                // Create fund ledger entry
                $this->createFundLedgerEntry($contribution);

                // Link to expected contribution
                $expectedService = app(\App\Services\ExpectedContributionService::class);
                $expectedService->linkPayment($contribution);
            }

            // Notify member
            $this->notifyMemberOfVerification($contribution, $approved, $notes);

            return true;
        });
    }

    /**
     * Create a fund ledger entry for a contribution.
     */
    protected function createFundLedgerEntry(Contribution $contribution): void
    {
        FundLedger::create([
            'type' => 'inflow',
            'member_id' => $contribution->member_id,
            'source' => 'contribution',
            'amount' => $contribution->amount + $contribution->fine_amount,
            'description' => "Contribution payment - {$contribution->receipt_number}",
            'transaction_date' => $contribution->payment_date,
            'reference' => $contribution->receipt_number,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update a fund ledger entry when contribution amount changes.
     */
    protected function updateFundLedgerEntry(Contribution $contribution, float $oldAmount, float $oldFineAmount): void
    {
        $ledgerEntry = FundLedger::where('reference', $contribution->receipt_number)
            ->where('source', 'contribution')
            ->first();

        if ($ledgerEntry) {
            $newAmount = $contribution->amount + $contribution->fine_amount;
            $ledgerEntry->update([
                'amount' => $newAmount,
                'description' => "Contribution payment - {$contribution->receipt_number} (Updated)",
            ]);
        }
    }

    /**
     * Delete a fund ledger entry when contribution is deleted.
     */
    protected function deleteFundLedgerEntry(Contribution $contribution): void
    {
        FundLedger::where('reference', $contribution->receipt_number)
            ->where('source', 'contribution')
            ->delete();
    }
}
