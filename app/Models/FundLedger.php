<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundLedger extends Model
{
    use Auditable, HasFactory;

    protected $table = 'fund_ledger';

    protected $fillable = [
        'type',
        'member_id',
        'source',
        'amount',
        'description',
        'transaction_date',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Get the member associated with this ledger entry.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user who created this ledger entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'inflow' => 'Inflow',
            'outflow' => 'Outflow',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the source label.
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'contribution' => 'Member Contribution',
            'loan_repayment' => 'Loan Repayment',
            'donation' => 'Donation',
            'claim_payment' => 'Health Claim Payment',
            'loan_disbursement' => 'Loan Disbursement',
            'fine_collection' => 'Late Payment Fine',
            'refund' => 'Refund',
            default => ucfirst(str_replace('_', ' ', $this->source)),
        };
    }

    /**
     * Get the amount with sign based on type.
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->type === 'inflow' ? $this->amount : -$this->amount;
    }

    /**
     * Scope for inflows.
     */
    public function scopeInflows($query)
    {
        return $query->where('type', 'inflow');
    }

    /**
     * Scope for outflows.
     */
    public function scopeOutflows($query)
    {
        return $query->where('type', 'outflow');
    }

    /**
     * Scope for specific source.
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope for member.
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Get the current fund balance.
     */
    public static function getCurrentBalance(): float
    {
        $inflows = static::inflows()->sum('amount');
        $outflows = static::outflows()->sum('amount');

        return $inflows - $outflows;
    }

    /**
     * Get the balance as of a specific date.
     */
    public static function getBalanceAsOf($date): float
    {
        $inflows = static::inflows()
            ->where('transaction_date', '<=', $date)
            ->sum('amount');

        $outflows = static::outflows()
            ->where('transaction_date', '<=', $date)
            ->sum('amount');

        return $inflows - $outflows;
    }

    /**
     * Get monthly summary.
     */
    public static function getMonthlySummary($year, $month)
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        return static::byDateRange($startDate, $endDate)
            ->selectRaw('type, source, SUM(amount) as total_amount, COUNT(*) as transaction_count')
            ->groupBy('type', 'source')
            ->orderBy('type')
            ->orderBy('source')
            ->get();
    }

    /**
     * Get fund balance history.
     */
    public static function getBalanceHistory($startDate, $endDate, $groupBy = 'day')
    {
        $groupByFormat = match ($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d',
        };

        return static::byDateRange($startDate, $endDate)
            ->selectRaw("
                DATE_FORMAT(transaction_date, '{$groupByFormat}') as period,
                SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END) as total_inflows,
                SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END) as total_outflows,
                SUM(CASE WHEN type = 'inflow' THEN amount ELSE -amount END) as net_amount
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}
