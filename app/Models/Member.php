<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'registration_no',
        'full_name',
        'family_name',
        'date_of_birth',
        'marital_status',
        'nin',
        'occupation',
        'workplace',
        'address',
        'hometown',
        'lga_id',
        'state_id',
        'country',
        'healthcare_provider_id',
        'health_status',
        'contribution_plan_id',
        'registration_date',
        'status',
        'eligibility_start_date',
        'last_cashout_date',
        'cashout_count',
        'created_by',
        'is_complete',
        'photo_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'registration_date' => 'date',
            'eligibility_start_date' => 'date',
            'last_cashout_date' => 'datetime',
            'is_complete' => 'boolean',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function healthcareProvider(): BelongsTo
    {
        return $this->belongsTo(HealthcareProvider::class);
    }

    public function contributionPlan(): BelongsTo
    {
        return $this->belongsTo(ContributionPlan::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function fundLedgerEntries(): HasMany
    {
        return $this->hasMany(FundLedger::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanRepayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function healthClaims(): HasMany
    {
        return $this->hasMany(HealthClaim::class);
    }

    public function programEnrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function cashoutRequests(): HasMany
    {
        return $this->hasMany(CashoutRequest::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEligible($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('eligibility_start_date')
            ->where('eligibility_start_date', '<=', now());
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePreRegistered($query)
    {
        return $query->where('status', 'pre_registered');
    }

    public function scopeIncomplete($query)
    {
        return $query->where('is_complete', false);
    }

    public function scopeComplete($query)
    {
        return $query->where('is_complete', true);
    }

    // Accessors
    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }

    public function getIsEligibleForHealthAttribute()
    {
        return $this->eligibility_start_date !== null && $this->eligibility_start_date <= now();
    }

    // Methods
    public function generateRegistrationNumber(): string
    {
        $lastMember = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastMember ? (int) substr($lastMember->registration_no, 5) + 1 : 1;

        return 'MCDF/' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function calculateEligibilityStartDate(): ?Carbon
    {
        if (!$this->is_complete || $this->status !== 'active') {
            return null;
        }

        // 60 days from registration date
        $sixtyDaysFromRegistration = $this->registration_date->addDays(60);

        // Check if member has 5 months of contributions
        $fiveMonthsAgo = now()->subMonths(5);
        $contributionsCount = $this->contributions()
            ->where('status', 'paid')
            ->where('payment_date', '>=', $fiveMonthsAgo)
            ->count();

        if ($contributionsCount >= 5) {
            return $sixtyDaysFromRegistration;
        }

        return null;
    }

    public function updateEligibilityStatus(): void
    {
        $eligibilityStartDate = $this->calculateEligibilityStartDate();

        $this->update([
            'eligibility_start_date' => $eligibilityStartDate,
        ]);
    }

    public function completeRegistration(array $data): void
    {
        $this->update([
            'is_complete' => true,
            'status' => 'pending',
            ...$data,
        ]);

        $this->updateEligibilityStatus();
    }

    public function approve(): void
    {
        $this->update(['status' => 'active']);
        $this->updateEligibilityStatus();
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Check eligibility for specific claim type.
     */
    public function checkHealthEligibility(string $claimType = 'outpatient'): array
    {
        $issues = [];

        // Check if member is active
        if ($this->status !== 'active') {
            $issues[] = 'Member must be active';
        }

        // Check registration period (60 days minimum)
        $registrationDate = $this->registration_date ?? $this->created_at;
        $daysSinceRegistration = $registrationDate->diffInDays(now());

        if ($daysSinceRegistration < 60) {
            $issues[] = 'Member must be registered for at least 60 days';
        }

        // Check contribution requirements based on claim type
        $contributionRequirement = $this->getContributionRequirementForClaimType($claimType);
        $contributionCount = $this->contributions()
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
            'claim_type' => $claimType,
        ];
    }

    /**
     * Get contribution requirement based on claim type.
     */
    protected function getContributionRequirementForClaimType(string $claimType): int
    {
        return match ($claimType) {
            'outpatient' => 1, // Any contribution
            'inpatient', 'surgery', 'maternity' => 5, // 5 months contributions
            default => 1,
        };
    }

    /**
     * Get eligibility status for display.
     */
    public function getEligibilityStatusAttribute(): array
    {
        $outpatientEligibility = $this->checkHealthEligibility('outpatient');
        $inpatientEligibility = $this->checkHealthEligibility('inpatient');

        return [
            'outpatient' => $outpatientEligibility,
            'inpatient' => $inpatientEligibility,
            'eligibility_start_date' => $this->calculateEligibilityStartDate(),
        ];
    }

    /**
     * Get total contributions amount.
     */
    public function getTotalContributionsAttribute(): float
    {
        return (float) $this->contributions()
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Get total fines paid.
     */
    public function getTotalFinesPaidAttribute(): float
    {
        return (float) $this->contributions()
            ->where('status', 'paid')
            ->sum('fine_amount');
    }

    /**
     * Get cashout eligible amount (contributions + fines).
     */
    public function getCashoutEligibleAmountAttribute(): float
    {
        return $this->total_contributions + $this->total_fines_paid;
    }

    /**
     * Check if member has a pending cashout request.
     */
    public function getHasPendingCashoutAttribute(): bool
    {
        return $this->cashoutRequests()
            ->whereIn('status', ['pending', 'verified', 'approved'])
            ->exists();
    }

    public function terminate(): void
    {
        $this->update(['status' => 'terminated']);
    }

    // Boot method for auto-generating registration number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($member) {
            if (empty($member->registration_no)) {
                $member->registration_no = $member->generateRegistrationNumber();
            }
        });
    }
}
