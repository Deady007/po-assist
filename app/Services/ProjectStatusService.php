<?php

namespace App\Services;

use App\Models\ProjectStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProjectStatusService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data): ProjectStatus
    {
        $status = DB::transaction(function () use ($data) {
            $model = ProjectStatus::create($data);

            if ($data['is_default'] ?? false) {
                $this->setDefault($model);
            }

            return $model;
        });

        $this->audit->logModel($status, AuditLogger::ACTION_CREATE);

        return $status;
    }

    public function update(ProjectStatus $status, array $data): ProjectStatus
    {
        $model = DB::transaction(function () use ($status, $data) {
            $status->update($data);

            if ($data['is_default'] ?? false) {
                $this->setDefault($status);
            }

            return $status;
        });

        $this->audit->logModel($model, AuditLogger::ACTION_UPDATE);

        return $model;
    }

    public function setDefault(ProjectStatus $status): ProjectStatus
    {
        DB::transaction(function () use ($status) {
            ProjectStatus::where('id', '!=', $status->id)->update(['is_default' => false]);
            $status->is_default = true;
            $status->save();
        });

        $this->audit->logModel($status, AuditLogger::ACTION_UPDATE);

        return $status;
    }

    public function toggle(ProjectStatus $status): ProjectStatus
    {
        $status->is_active = !$status->is_active;
        $status->save();

        $action = $status->is_active ? AuditLogger::ACTION_ACTIVATE : AuditLogger::ACTION_DEACTIVATE;
        $this->audit->logModel($status, $action);

        return $status;
    }

    public function defaultId(): int
    {
        $default = ProjectStatus::where('is_default', true)->where('is_active', true)->first();
        if (!$default) {
            throw new RuntimeException('Default project status missing');
        }

        return $default->id;
    }
}
