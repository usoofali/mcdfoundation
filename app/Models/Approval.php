<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'approved_by',
        'role',
        'approval_level',
        'status',
        'remarks',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Get the entity that this approval belongs to.
     */
    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the user who made the approval.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
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
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get the approval level label.
     */
    public function getApprovalLevelLabelAttribute(): string
    {
        return match ($this->approval_level) {
            1 => 'LG Coordinator',
            2 => 'State Coordinator',
            3 => 'Project Coordinator',
            default => "Level {$this->approval_level}",
        };
    }

    /**
     * Scope for pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved items.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected items.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for specific approval level.
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    /**
     * Scope for specific entity type.
     */
    public function scopeByEntityType($query, $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope for specific role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Approve the item.
     */
    public function approve(?string $remarks = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'remarks' => $remarks,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the item.
     */
    public function reject(?string $remarks = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'remarks' => $remarks,
            'approved_at' => now(),
        ]);
    }
}
