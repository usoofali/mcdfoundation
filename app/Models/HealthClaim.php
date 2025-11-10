<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthClaim extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'healthcare_provider_id',
        'claim_type',
        'billed_amount',
        'coverage_percent',
        'covered_amount',
        'copay_amount',
        'claim_date',
        'status',
        'approved_by',
        'paid_by',
        'paid_date',
        'remarks',
        'claim_number',
    ];

    protected $casts = [
        'billed_amount' => 'decimal:2',
        'coverage_percent' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'copay_amount' => 'decimal:2',
        'claim_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Get the member that owns the claim.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the healthcare provider for the claim.
     */
    public function healthcareProvider(): BelongsTo
    {
        return $this->belongsTo(HealthcareProvider::class);
    }

    /**
     * Get the user who approved the claim.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who paid the claim.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the claim type label.
     */
    public function getClaimTypeLabelAttribute(): string
    {
        return match ($this->claim_type) {
            'outpatient' => 'Outpatient',
            'inpatient' => 'Inpatient',
            'surgery' => 'Surgery',
            'maternity' => 'Maternity',
            default => ucfirst($this->claim_type),
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'paid' => 'Paid',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'yellow',
            'approved' => 'blue',
            'rejected' => 'red',
            'paid' => 'green',
            default => 'gray',
        };
    }

    /**
     * Calculate coverage amount based on billed amount and coverage percentage.
     */
    public function calculateCoverageAmount(): float
    {
        return $this->billed_amount * ($this->coverage_percent / 100);
    }

    /**
     * Calculate copay amount (remaining amount after coverage).
     */
    public function calculateCopayAmount(): float
    {
        return $this->billed_amount - $this->calculateCoverageAmount();
    }

    /**
     * Check if member is eligible for this claim type.
     */
    public function checkEligibility(): array
    {
        $member = $this->member;
        $issues = [];

        // Check if member is active
        if ($member->status !== 'active') {
            $issues[] = 'Member must be active';
        }

        // Check registration period (60 days minimum)
        $registrationDate = $member->registration_date ?? $member->created_at;
        $daysSinceRegistration = $registrationDate->diffInDays(now());

        if ($daysSinceRegistration < 60) {
            $issues[] = 'Member must be registered for at least 60 days';
        }

        // Check contribution requirements based on claim type
        $contributionRequirement = $this->getContributionRequirement();
        $contributionCount = $member->contributions()
            ->where('status', 'paid')
            ->count();

        if ($contributionCount < $contributionRequirement) {
            $issues[] = "Member must have at least {$contributionRequirement} months of contributions";
        }

        return [
            'eligible' => empty($issues),
            'issues' => $issues,
            'days_since_registration' => $daysSinceRegistration,
            'contribution_count' => $contributionCount,
            'required_contributions' => $contributionRequirement,
        ];
    }

    /**
     * Get contribution requirement based on claim type.
     */
    protected function getContributionRequirement(): int
    {
        return match ($this->claim_type) {
            'outpatient' => 1, // Any contribution
            'inpatient', 'surgery', 'maternity' => 5, // 5 months contributions
            default => 1,
        };
    }

    /**
     * Generate unique claim number.
     */
    public static function generateClaimNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastClaim = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->latest()
            ->first();

        $sequence = $lastClaim ? (int) substr($lastClaim->claim_number, -4) + 1 : 1;

        return 'CLM'.$year.$month.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for submitted claims.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for approved claims.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for paid claims.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for rejected claims.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for specific claim type.
     */
    public function scopeByClaimType($query, $type)
    {
        return $query->where('claim_type', $type);
    }

    /**
     * Scope for claims by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('claim_date', [$startDate, $endDate]);
    }

    /**
     * Boot method to auto-calculate amounts and generate claim number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = self::generateClaimNumber();
            }

            // Auto-calculate coverage and copay amounts
            $claim->covered_amount = $claim->calculateCoverageAmount();
            $claim->copay_amount = $claim->calculateCopayAmount();
        });

        static::updating(function ($claim) {
            // Recalculate amounts if billed amount or coverage percent changed
            if ($claim->isDirty(['billed_amount', 'coverage_percent'])) {
                $claim->covered_amount = $claim->calculateCoverageAmount();
                $claim->copay_amount = $claim->calculateCopayAmount();
            }
        });
    }
}
