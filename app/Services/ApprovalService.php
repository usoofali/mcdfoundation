<?php

namespace App\Services;

use App\Models\Approval;
use Illuminate\Database\Eloquent\Collection;

class ApprovalService
{
    /**
     * Create approval records for an entity.
     */
    public function createApprovalChain(string $entityType, int $entityId, int $startLevel = 1): Collection
    {
        $approvals = collect();

        // Create approval records for each level (1, 2, 3)
        for ($level = $startLevel; $level <= 3; $level++) {
            $approval = Approval::create([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'approved_by' => auth()->id(),
                'role' => auth()->user()->role->name ?? 'Unknown',
                'approval_level' => $level,
                'status' => 'pending',
            ]);

            $approvals->push($approval);
        }

        return $approvals;
    }

    /**
     * Get pending approvals for a specific user role and level.
     */
    public function getPendingApprovalsForUser(int $userId, ?int $level = null): Collection
    {
        $query = Approval::with(['entity', 'approver'])
            ->where('status', 'pending');

        if ($level) {
            $query->where('approval_level', $level);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Get pending approvals by entity type.
     */
    public function getPendingApprovalsByType(string $entityType, ?int $level = null): Collection
    {
        $query = Approval::with(['entity', 'approver'])
            ->where('entity_type', $entityType)
            ->where('status', 'pending');

        if ($level) {
            $query->where('approval_level', $level);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Get approval history for an entity.
     */
    public function getApprovalHistory(string $entityType, int $entityId): Collection
    {
        return Approval::with(['approver'])
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('approval_level', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Check if an entity has all required approvals.
     */
    public function hasAllApprovals(string $entityType, int $entityId): bool
    {
        $requiredLevels = [1, 2, 3];
        $approvedLevels = Approval::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'approved')
            ->pluck('approval_level')
            ->toArray();

        return count(array_intersect($requiredLevels, $approvedLevels)) === count($requiredLevels);
    }

    /**
     * Get the next approval level for an entity.
     */
    public function getNextApprovalLevel(string $entityType, int $entityId): ?int
    {
        $lastApprovedLevel = Approval::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'approved')
            ->max('approval_level');

        return $lastApprovedLevel ? $lastApprovedLevel + 1 : 1;
    }

    /**
     * Get approval statistics.
     */
    public function getApprovalStats(array $filters = []): array
    {
        $query = Approval::query();

        // Apply filters
        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $totalApprovals = $query->count();
        $pendingApprovals = $query->where('status', 'pending')->count();
        $approvedItems = $query->where('status', 'approved')->count();
        $rejectedItems = $query->where('status', 'rejected')->count();

        // Breakdown by level
        $byLevel = $query->selectRaw('approval_level, status, COUNT(*) as count')
            ->groupBy('approval_level', 'status')
            ->get()
            ->groupBy('approval_level');

        return [
            'total_approvals' => $totalApprovals,
            'pending_approvals' => $pendingApprovals,
            'approved_items' => $approvedItems,
            'rejected_items' => $rejectedItems,
            'approval_rate' => $totalApprovals > 0 ? ($approvedItems / $totalApprovals) * 100 : 0,
            'by_level' => $byLevel,
        ];
    }

    /**
     * Get dashboard data for coordinators.
     */
    public function getCoordinatorDashboard(int $level): array
    {
        $pendingApprovals = $this->getPendingApprovalsByLevel($level);

        // Group by entity type
        $byType = $pendingApprovals->groupBy('entity_type');

        return [
            'pending_count' => $pendingApprovals->count(),
            'by_type' => $byType->map(function ($items) {
                return $items->count();
            }),
            'recent_approvals' => $this->getRecentApprovals($level, 5),
        ];
    }

    /**
     * Get pending approvals for a specific level.
     */
    protected function getPendingApprovalsByLevel(int $level): Collection
    {
        return Approval::with(['entity', 'approver'])
            ->where('approval_level', $level)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get recent approvals for a specific level.
     */
    protected function getRecentApprovals(int $level, int $limit = 10): Collection
    {
        return Approval::with(['entity', 'approver'])
            ->where('approval_level', $level)
            ->where('status', '!=', 'pending')
            ->orderBy('approved_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk approve items.
     */
    public function bulkApprove(array $approvalIds, ?string $remarks = null): int
    {
        $count = 0;

        foreach ($approvalIds as $approvalId) {
            $approval = Approval::find($approvalId);

            if ($approval && $approval->status === 'pending') {
                $approval->approve($remarks);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Bulk reject items.
     */
    public function bulkReject(array $approvalIds, ?string $remarks = null): int
    {
        $count = 0;

        foreach ($approvalIds as $approvalId) {
            $approval = Approval::find($approvalId);

            if ($approval && $approval->status === 'pending') {
                $approval->reject($remarks);
                $count++;
            }
        }

        return $count;
    }
}
