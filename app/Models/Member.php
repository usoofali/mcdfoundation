<?php

namespace App\Models;

use App\Models\ContributionPlan;
use App\Models\ExpectedContribution;
use App\Models\HealthcareProvider;
use App\Models\Lga;
use App\Models\State;
use App\Models\User;
use App\Services\ExpectedContributionService;
use App\Traits\Auditable;
use App\Traits\HasLocationScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Carbon\Carbon $date_of_birth
 * @property \Carbon\Carbon $registration_date
 * @property \Carbon\Carbon|null $eligibility_start_date
 * @property \Carbon\Carbon|null $last_cashout_date
 * @property int $contribution_plan_id
 */
class Member extends Model
{
    use Auditable, HasFactory, HasLocationScope, SoftDeletes;

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

    public function expectedContributions(): HasMany
    {
        return $this->hasMany(ExpectedContribution::class);
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

        return 'MCDF/' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
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

        // Auto-generate expected contributions
        if ($this->contribution_plan_id) {
            $service = app(ExpectedContributionService::class);
            $service->ensureFutureContributions($this);
        }
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function requestReactivation(): void
    {
        if ($this->status !== 'suspended') {
            throw new \Exception('Only suspended accounts can request reactivation');
        }

        $this->update(['status' => 'pending']);

        \Log::info("Member {$this->id} requested reactivation after cashout");
    }

    /**
     * Check eligibility for specific claim type.
     */
    public function checkHealthEligibility(string $claimType = 'outpatient'): array
    {
        $issues = [];
        $settingService = app(\App\Services\SettingService::class);

        // Check if member is active
        if ($this->status !== 'active') {
            $issues[] = 'Member must be active';
        }

        // Check registration period - USE SETTINGS
        $eligibilityRules = $settingService->get('eligibility_rules', []);
        $minDays = $eligibilityRules['health_access_wait_days'] ?? 60;

        $registrationDate = $this->registration_date ?? $this->created_at;
        $daysSinceRegistration = $registrationDate->diffInDays(now());

        if ($daysSinceRegistration < $minDays) {
            $issues[] = "Member must be registered for at least {$minDays} days";
        }

        // Check contribution requirements - USE SETTINGS
        $healthEligibility = $settingService->get('health_eligibility', []);
        $contributionRequirement = $this->getContributionRequirementForClaimType($claimType, $healthEligibility);

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
    protected function getContributionRequirementForClaimType(string $claimType, array $settings = []): int
    {
        return match ($claimType) {
            'outpatient' => $settings['min_contributions_outpatient'] ?? 1,
            'inpatient' => $settings['min_contributions_inpatient'] ?? 5,
            'surgery' => $settings['min_contributions_surgery'] ?? 5,
            'maternity' => $settings['min_contributions_maternity'] ?? 5,
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
