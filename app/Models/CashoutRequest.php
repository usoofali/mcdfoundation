<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashoutRequest extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'requested_amount',
        'approved_amount',
        'status',
        'account_number',
        'account_name',
        'bank_name',
        'reason',
        'verified_by',
        'verified_at',
        'verification_notes',
        'approved_by',
        'approved_at',
        'approval_notes',
        'disbursed_by',
        'disbursed_at',
        'disbursement_reference',
        'rejection_reason',
        'rejected_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the member that owns the cashout request.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user who verified the request.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user who approved the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who disbursed the request.
     */
    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for verified requests.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope for approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for disbursed requests.
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 'disbursed');
    }

    /**
     * Mark request as verified.
     */
    public function markAsVerified(User $user, ?string $notes = null): bool
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending requests can be verified');
        }

        return $this->update([
            'status' => 'verified',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Mark request as approved.
     */
    public function markAsApproved(User $user, float $amount, ?string $notes = null): bool
    {
        if ($this->status !== 'verified') {
            throw new \Exception('Only verified requests can be approved');
        }

        return $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approved_amount' => $amount,
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Mark request as disbursed.
     */
    public function markAsDisbursed(User $user, string $reference): bool
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Only approved requests can be disbursed');
        }

        return $this->update([
            'status' => 'disbursed',
            'disbursed_by' => $user->id,
            'disbursed_at' => now(),
            'disbursement_reference' => $reference,
        ]);
    }

    /**
     * Reject the request.
     */
    public function reject(string $reason): bool
    {
        if (in_array($this->status, ['disbursed', 'rejected'])) {
            throw new \Exception('Cannot reject disbursed or already rejected requests');
        }

        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'verified' => 'Verified',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'disbursed' => 'Disbursed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'verified' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'disbursed' => 'green',
            default => 'gray',
        };
    }
}
