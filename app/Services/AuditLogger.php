<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_ACTIVATE = 'ACTIVATE';
    public const ACTION_DEACTIVATE = 'DEACTIVATE';
    public const ACTION_INIT = 'INIT';
    public const ACTION_BLOCK = 'BLOCK';

    public function log(string $entityType, string $entityId, string $action, ?array $diff = null): void
    {
        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'diff_json' => $diff,
            'created_at' => now(),
        ]);
    }

    public function logModel(Model $model, string $action): void
    {
        $this->log(
            class_basename($model),
            (string) $model->getKey(),
            $action,
            $model->getChanges()
        );
    }
}
