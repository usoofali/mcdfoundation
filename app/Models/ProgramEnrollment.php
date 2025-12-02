<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'program_id',
        'enrolled_at',
        'completed_at',
        'status',
        'certificate_issued',
        'remarks',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'completed_at' => 'date',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Get the member for this enrollment.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the program for this enrollment.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Mark enrollment as completed.
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now()->toDateString(),
        ]);
    }

    /**
     * Issue certificate for this enrollment.
     */
    public function issueCertificate(): bool
    {
        if ($this->status !== 'completed') {
            throw new \Exception('Certificate can only be issued for completed enrollments');
        }

        return $this->update([
            'certificate_issued' => true,
        ]);
    }

    /**
     * Withdraw from program.
     */
    public function withdraw(?string $reason = null): bool
    {
        return $this->update([
            'status' => 'withdrawn',
            'remarks' => $reason,
        ]);
    }

    /**
     * Scope for enrolled status.
     */
    public function scopeEnrolled($query)
    {
        return $query->where('status', 'enrolled');
    }

    /**
     * Scope for completed status.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for withdrawn status.
     */
    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }
}
