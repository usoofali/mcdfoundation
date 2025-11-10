<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(string $action, string $entityType, int $entityId, ?array $beforeData = null, ?array $afterData = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_data' => $beforeData,
            'after_data' => $afterData,
        ]);
    }

    public function getEntityAuditLog(string $entityType, int $entityId): Collection
    {
        return AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserAuditLog(int $userId, int $limit = 50): Collection
    {
        return AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentActivity(int $limit = 20): Collection
    {
        return AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
