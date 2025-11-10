<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'loan_type',
        'item_description',
        'amount',
        'repayment_mode',
        'installment_amount',
        'repayment_period',
        'start_date',
        'security_description',
        'guarantor_name',
        'guarantor_contact',
        'status',
        'approved_by',
        'approval_date',
        'disbursement_date',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'start_date' => 'date',
        'approval_date' => 'date',
        'disbursement_date' => 'date',
    ];

    /**
     * Get the member that owns the loan.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user who approved the loan.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the loan repayments.
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /**
     * Get the loan approvals.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'entity_id')->where('entity_type', 'Loan');
    }

    /**
     * Get the loan type label.
     */
    public function getLoanTypeLabelAttribute(): string
    {
        return match ($this->loan_type) {
            'cash' => 'Cash Loan',
            'item' => 'Item Loan',
            default => ucfirst($this->loan_type),
        };
    }

    /**
     * Get the repayment mode label.
     */
    public function getRepaymentModeLabelAttribute(): string
    {
        return match ($this->repayment_mode) {
            'installments' => 'Installments',
            'full' => 'Full Payment',
            default => ucfirst($this->repayment_mode),
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'disbursed' => 'Disbursed',
            'repaid' => 'Repaid',
            'defaulted' => 'Defaulted',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'disbursed' => 'green',
            'repaid' => 'green',
            'defaulted' => 'red',
            default => 'gray',
        };
    }

    /**
     * Calculate the total amount repaid.
     */
    public function getTotalRepaidAttribute(): float
    {
        return $this->repayments()->sum('amount');
    }

    /**
     * Calculate the outstanding balance.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->amount - $this->total_repaid;
    }

    /**
     * Check if the loan is fully repaid.
     */
    public function getIsFullyRepaidAttribute(): bool
    {
        return $this->outstanding_balance <= 0;
    }

    /**
     * Check if the loan is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'disbursed') {
            return false;
        }

        $expectedEndDate = $this->start_date->addMonths($this->getRepaymentPeriodInMonths());

        return now()->isAfter($expectedEndDate) && ! $this->is_fully_repaid;
    }

    /**
     * Get repayment period in months.
     */
    public function getRepaymentPeriodInMonths(): int
    {
        // Extract number from period string like "6 months", "12 months"
        preg_match('/(\d+)/', $this->repayment_period, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 6;
    }

    /**
     * Calculate expected monthly installment.
     */
    public function calculateMonthlyInstallment(): float
    {
        if ($this->repayment_mode === 'full') {
            return $this->amount;
        }

        $months = $this->getRepaymentPeriodInMonths();

        return $this->amount / $months;
    }

    /**
     * Check if member is eligible for loan.
     */
    public function checkEligibility(): array
    {
        $member = $this->member;
        $issues = [];

        // Check if member is active
        if ($member->status !== 'active') {
            $issues[] = 'Member must be active';
        }

        // Check contribution history (12 months requirement)
        $twelveMonthsAgo = now()->subMonths(12);
        $contributionsCount = $member->contributions()
            ->where('status', 'paid')
            ->where('payment_date', '>=', $twelveMonthsAgo)
            ->count();

        if ($contributionsCount < 12) {
            $issues[] = 'Member must have at least 12 months of contributions';
        }

        // Check for existing active loans
        $activeLoans = $member->loans()
            ->whereIn('status', ['approved', 'disbursed'])
            ->count();

        if ($activeLoans > 0) {
            $issues[] = 'Member has existing active loans';
        }

        return [
            'eligible' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Scope for pending loans.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved loans.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for disbursed loans.
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 'disbursed');
    }

    /**
     * Scope for repaid loans.
     */
    public function scopeRepaid($query)
    {
        return $query->where('status', 'repaid');
    }

    /**
     * Scope for defaulted loans.
     */
    public function scopeDefaulted($query)
    {
        return $query->where('status', 'defaulted');
    }

    /**
     * Scope for cash loans.
     */
    public function scopeCashLoans($query)
    {
        return $query->where('loan_type', 'cash');
    }

    /**
     * Scope for item loans.
     */
    public function scopeItemLoans($query)
    {
        return $query->where('loan_type', 'item');
    }

    /**
     * Scope for overdue loans.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'disbursed')
            ->whereHas('repayments', function ($q) {
                $q->whereRaw('DATE_ADD(start_date, INTERVAL ? MONTH) < NOW()', [$this->getRepaymentPeriodInMonths()]);
            });
    }

    /**
     * Boot method to auto-calculate installment amount.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($loan) {
            if ($loan->repayment_mode === 'installments' && ! $loan->installment_amount) {
                $loan->installment_amount = $loan->calculateMonthlyInstallment();
            }
        });
    }
}
