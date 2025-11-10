<?php

namespace App\Services;

use App\Models\FundLedger;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FundLedgerService
{
    /**
     * Record a fund ledger entry.
     */
    public function recordEntry(array $data): FundLedger
    {
        return FundLedger::create($data);
    }

    /**
     * Record inflow entry.
     */
    public function recordInflow(
        string $source,
        float $amount,
        ?int $memberId = null,
        ?string $description = null,
        ?string $reference = null,
        ?string $transactionDate = null
    ): FundLedger {
        return $this->recordEntry([
            'type' => 'inflow',
            'member_id' => $memberId,
            'source' => $source,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => $transactionDate ?? now()->toDateString(),
            'reference' => $reference,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Record outflow entry.
     */
    public function recordOutflow(
        string $source,
        float $amount,
        ?int $memberId = null,
        ?string $description = null,
        ?string $reference = null,
        ?string $transactionDate = null
    ): FundLedger {
        return $this->recordEntry([
            'type' => 'outflow',
            'member_id' => $memberId,
            'source' => $source,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => $transactionDate ?? now()->toDateString(),
            'reference' => $reference,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Get fund ledger entries with filters.
     */
    public function getLedgerEntries(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = FundLedger::with(['member', 'creator']);

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('transaction_date', 'desc')->paginate($perPage);
    }

    /**
     * Get current fund balance.
     */
    public function getCurrentBalance(): float
    {
        return FundLedger::getCurrentBalance();
    }

    /**
     * Get balance as of a specific date.
     */
    public function getBalanceAsOf(string $date): float
    {
        return FundLedger::getBalanceAsOf($date);
    }

    /**
     * Get fund statistics.
     */
    public function getFundStats(array $filters = []): array
    {
        $query = FundLedger::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        $totalInflows = $query->inflows()->sum('amount');
        $totalOutflows = $query->outflows()->sum('amount');
        $currentBalance = $totalInflows - $totalOutflows;

        // Get breakdown by source
        $inflowsBySource = $query->inflows()
            ->selectRaw('source, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->groupBy('source')
            ->get();

        $outflowsBySource = $query->outflows()
            ->selectRaw('source, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->groupBy('source')
            ->get();

        return [
            'current_balance' => $currentBalance,
            'total_inflows' => $totalInflows,
            'total_outflows' => $totalOutflows,
            'inflows_by_source' => $inflowsBySource,
            'outflows_by_source' => $outflowsBySource,
            'net_flow' => $totalInflows - $totalOutflows,
        ];
    }

    /**
     * Get monthly fund summary.
     */
    public function getMonthlySummary(int $year, int $month): Collection
    {
        return FundLedger::getMonthlySummary($year, $month);
    }

    /**
     * Get fund balance history.
     */
    public function getBalanceHistory(string $startDate, string $endDate, string $groupBy = 'day'): Collection
    {
        return FundLedger::getBalanceHistory($startDate, $endDate, $groupBy);
    }

    /**
     * Get member's fund transactions.
     */
    public function getMemberTransactions(Member $member, int $perPage = 15): LengthAwarePaginator
    {
        return $member->fundLedgerEntries()
            ->with(['creator'])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent fund activities.
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        return FundLedger::with(['member', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate fund report.
     */
    public function generateReport(array $filters = []): Collection
    {
        $query = FundLedger::with(['member', 'creator']);

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('transaction_date', 'desc')->get();
    }

    /**
     * Validate fund ledger data.
     */
    public function validateLedgerData(array $data): array
    {
        $rules = [
            'type' => 'required|in:inflow,outflow',
            'member_id' => 'nullable|exists:members,id',
            'source' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
        ];

        return validator($data, $rules)->validate();
    }
}
