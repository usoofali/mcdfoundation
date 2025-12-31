<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Carbon\Carbon $due_date
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon $period_end
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $overdue_at
 */
class ExpectedContribution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'contribution_plan_id',
        'actual_contribution_id',
        'expected_amount',
        'fine_amount',
        'due_date',
        'period_start',
        'period_end',
        'status',
        'notes',
        'paid_at',
        'overdue_at',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'overdue_at' => 'datetime',
    ];

    /**
     * Get the member that owns the expected contribution.
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
     * Get the actual contribution if paid.
     */
    public function actualContribution(): BelongsTo
    {
        return $this->belongsTo(Contribution::class, 'actual_contribution_id');
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
     * Scope for paid contributions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for unpaid contributions (pending or overdue).
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'overdue']);
    }

    /**
     * Scope for contributions that do NOT have a pending payment awaiting verification.
     */
    public function scopeNotAwaitingVerification($query)
    {
        return $query->whereDoesntHave('actualContribution', function ($q) {
            $q->where('status', 'pending');
        });
    }

    /**
     * Scope for contributions that DO have a pending payment awaiting verification.
     */
    public function scopeAwaitingVerification($query)
    {
        return $query->whereHas('actualContribution', function ($q) {
            $q->where('status', 'pending');
        });
    }

    /**
     * Scope for contributions due soon.
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<=', now()->addDays($days))
            ->where('due_date', '>=', now());
    }

    /**
     * Check if contribution is due.
     */
    public function getIsDueAttribute(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'paid';
    }

    /**
     * Check if contribution is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'overdue';
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDueAttribute(): int
    {
        if ($this->due_date->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }

    /**
     * Get days overdue.
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->due_date->isPast()) {
            return 0;
        }
        return $this->due_date->diffInDays(now());
    }

    /**
     * Get total amount including fine.
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->expected_amount + $this->fine_amount;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Paid',
            'pending' => 'Pending',
            'overdue' => 'Overdue',
            'waived' => 'Waived',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'green',
            'pending' => $this->days_until_due <= 7 ? 'orange' : 'yellow',
            'overdue' => 'red',
            'waived' => 'gray',
            default => 'gray',
        };
    }
}
