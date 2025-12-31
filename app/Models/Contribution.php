<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Carbon\Carbon $payment_date
 * @property \Carbon\Carbon|null $verified_at
 */
class Contribution extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'contribution_plan_id',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_date',
        'status',
        'collected_by',
        'fine_amount',
        'receipt_number',
        'receipt_path',
        'uploaded_by',
        'verification_notes',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the member that owns the contribution.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the contribution plan.
     */
    public function contributionPlan(): BelongsTo
    {
        return $this->belongsTo(ContributionPlan::class);
    }

    /**
     * Get the user who collected the contribution.
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * Get the user who uploaded the receipt.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the expected contributions linked to this payment.
     */
    public function expectedContributions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExpectedContribution::class, 'actual_contribution_id');
    }

    /**
     * Get the user who verified the contribution.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the total amount including fine.
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + $this->fine_amount;
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'transfer' => 'Bank Transfer',
            'bank_deposit' => 'Bank Deposit',
            'mobile_money' => 'Mobile Money',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Get the period coverage string (e.g., "01 Jan, 2024 - 31 Jan, 2024")
     */
    public function getPeriodCoverageAttribute(): string
    {
        $expected = $this->expectedContributions;

        if ($expected->isEmpty()) {
            return '-';
        }

        $minStart = $expected->min('period_start');
        $maxEnd = $expected->max('period_end');

        if (!$minStart || !$maxEnd) {
            return '-';
        }

        return $minStart->format('d M, Y') . ' - ' . $maxEnd->format('d M, Y');
    }

    /**
     * Get the status label with color.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Paid',
            'pending' => 'Pending',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'green',
            'pending' => 'yellow',
            'overdue' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Scope for paid contributions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for pending contributions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for overdue contributions.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Scope for contributions by member.
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope for contributions by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope for contributions with fines.
     */
    public function scopeWithFines($query)
    {
        return $query->where('fine_amount', '>', 0);
    }

    /**
     * Scope for contributions pending verification.
     */
    public function scopePendingVerification($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('receipt_path');
    }

    /**
     * Get the receipt URL.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_path) {
            return null;
        }

        return asset('storage/' . $this->receipt_path);
    }

    /**
     * Check if contribution has receipt uploaded.
     */
    public function getHasReceiptAttribute(): bool
    {
        return !empty($this->receipt_path);
    }

    /**
     * Check if contribution is member-submitted.
     */
    public function getIsMemberSubmittedAttribute(): bool
    {
        return !empty($this->uploaded_by) && empty($this->collected_by);
    }

    /**
     * Generate unique receipt number.
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'RCP';
        $year = date('Y');
        $month = date('m');

        // Get the last receipt number for this month
        $lastReceipt = static::where('receipt_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to auto-generate receipt number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contribution) {
            if (empty($contribution->receipt_number)) {
                $contribution->receipt_number = static::generateReceiptNumber();
            }
        });
    }
}
