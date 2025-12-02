<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'eligibility_rules',
        'capacity',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'eligibility_rules' => 'array',
    ];

    /**
     * Get the user who created this program.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all enrollments for this program.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    /**
     * Get all members enrolled in this program.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'program_enrollments')
            ->withPivot('enrolled_at', 'completed_at', 'status', 'certificate_issued', 'remarks')
            ->withTimestamps();
    }

    /**
     * Scope for active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for upcoming programs.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    /**
     * Scope for ongoing programs.
     */
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Get the count of enrolled members.
     */
    public function getEnrolledCountAttribute(): int
    {
        return $this->enrollments()->where('status', 'enrolled')->count();
    }

    /**
     * Get the number of available slots.
     */
    public function getAvailableSlotsAttribute(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->enrolled_count);
    }

    /**
     * Check if program is at capacity.
     */
    public function getIsAtCapacityAttribute(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        return $this->enrolled_count >= $this->capacity;
    }
}
