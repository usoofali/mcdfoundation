<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::logAudit('created', $model);
        });

        static::updated(function (Model $model) {
            static::logAudit('updated', $model);
        });

        static::deleted(function (Model $model) {
            static::logAudit('deleted', $model);
        });
    }

    protected static function logAudit(string $action, Model $model): void
    {
        $beforeData = null;
        $afterData = null;

        if ($action === 'updated') {
            $beforeData = $model->getOriginal();
            $afterData = $model->getChanges();
        } elseif ($action === 'created') {
            $afterData = $model->getAttributes();
        } elseif ($action === 'deleted') {
            $beforeData = $model->getAttributes();
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => get_class($model),
            'entity_id' => $model->getKey(),
            'before_data' => $beforeData,
            'after_data' => $afterData,
        ]);
    }
}
