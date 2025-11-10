<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dependent extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'name',
        'date_of_birth',
        'relationship',
        'document_path',
        'eligible',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'eligible' => 'boolean',
    ];

    /**
     * Get the member that owns the dependent.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the dependent's age in years.
     */
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    /**
     * Get the relationship label.
     */
    public function getRelationshipLabelAttribute(): string
    {
        return match ($this->relationship) {
            'spouse' => 'Spouse',
            'child' => 'Child',
            'parent' => 'Parent',
            'sibling' => 'Sibling',
            'other' => 'Other',
            default => ucfirst($this->relationship),
        };
    }

    /**
     * Check if dependent is eligible for health benefits.
     * Children ≤15 years are automatically eligible.
     * Spouses are eligible if member is eligible.
     * Others depend on specific rules.
     */
    public function calculateEligibility(): bool
    {
        // Children under 15 are automatically eligible
        if ($this->relationship === 'child' && $this->age <= 15) {
            return true;
        }

        // Spouses are eligible if the member is eligible
        if ($this->relationship === 'spouse' && $this->member->is_eligible_for_health) {
            return true;
        }

        // Parents and siblings have specific eligibility rules
        if (in_array($this->relationship, ['parent', 'sibling', 'other'])) {
            // For now, they are eligible if member is eligible
            // This can be customized based on specific business rules
            return $this->member->is_eligible_for_health;
        }

        return false;
    }

    /**
     * Boot method to auto-calculate eligibility when dependent is created or updated.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($dependent) {
            $dependent->eligible = $dependent->calculateEligibility();
        });
    }

    /**
     * Scope for eligible dependents.
     */
    public function scopeEligible($query)
    {
        return $query->where('eligible', true);
    }

    /**
     * Scope for dependents by relationship.
     */
    public function scopeByRelationship($query, string $relationship)
    {
        return $query->where('relationship', $relationship);
    }

    /**
     * Scope for children.
     */
    public function scopeChildren($query)
    {
        return $query->byRelationship('child');
    }

    /**
     * Scope for spouses.
     */
    public function scopeSpouses($query)
    {
        return $query->byRelationship('spouse');
    }
}
