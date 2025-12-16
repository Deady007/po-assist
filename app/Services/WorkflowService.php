<?php

namespace App\Services;

use App\Models\ModuleTemplate;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkflowService
{
    public function __construct(private AuditLogger $audit) {}

    public function initModules(Project $project): array
    {
        $templates = ModuleTemplate::where('is_active', true)->orderBy('order_no')->get();
        $created = 0;
        $existing = 0;

        DB::transaction(function () use ($templates, $project, &$created, &$existing) {
            foreach ($templates as $tmpl) {
                $exists = ProjectModule::where('project_id', $project->id)
                    ->where(function ($q) use ($tmpl) {
                        $q->where('template_id', $tmpl->id)
                            ->orWhere('name', $tmpl->name);
                    })
                    ->exists();

                if ($exists) {
                    $existing++;
                    continue;
                }

                $module = ProjectModule::create([
                    'project_id' => $project->id,
                    'template_id' => $tmpl->id,
                    'name' => $tmpl->name,
                    'order_no' => $tmpl->order_no,
                    'status' => 'NOT_STARTED',
                    'is_active' => true,
                ]);
                $created++;
                $this->audit->logModel($module, AuditLogger::ACTION_CREATE);
                $this->audit->log('ProjectModule', (string) $module->id, AuditLogger::ACTION_INIT);
            }
        });

        $this->audit->log('ProjectModule', (string) $project->id, AuditLogger::ACTION_INIT);

        return [
            'created_count' => $created,
            'existing_count' => $existing,
        ];
    }

    public function listModules(int $projectId): Collection
    {
        $modules = ProjectModule::with(['owner', 'tasks'])
            ->where('project_id', $projectId)
            ->orderBy('order_no')
            ->get();

        $modules->transform(function (ProjectModule $module) {
            $total = $module->tasks->count();
            $done = $module->tasks->where('status', 'DONE')->count();
            $overdue = $module->tasks->where('status', '!=', 'DONE')
                ->whereNotNull('due_date')
                ->filter(fn ($t) => $t->due_date < now()->startOfDay())
                ->count();

            $module->setAttribute('total_tasks', $total);
            $module->setAttribute('done_tasks', $done);
            $module->setAttribute('overdue_tasks', $overdue);

            return $module;
        });

        return $modules;
    }

    public function createModule(Project $project, array $data): ProjectModule
    {
        $order = $data['order_no'] ?? ($project->modules()->max('order_no') + 1);
        $status = strtoupper($data['status'] ?? 'NOT_STARTED');

        if ($status === 'BLOCKED' && empty($data['blocker_reason'])) {
            throw new RuntimeException('Blocker reason is required when blocking a module.');
        }

        $module = ProjectModule::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'module_name' => $data['module_name'] ?? ($data['name'] ?? 'Default Module Name'),
            'order_no' => $order ?: 1,
            'status' => $status,
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'is_active' => true,
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['created_by'] ?? null,
        ]);

        $this->audit->logModel($module, AuditLogger::ACTION_CREATE);
        if ($status === 'BLOCKED') {
            $this->audit->log('ProjectModule', (string) $module->id, AuditLogger::ACTION_BLOCK);
        }

        return $module;
    }

    public function updateModule(ProjectModule $module, array $data): array
    {
        $warnings = [];

        $status = null;

        if (isset($data['status'])) {
            $status = strtoupper($data['status']);

            if ($status === 'BLOCKED' && empty($data['blocker_reason'] ?? $module->blocker_reason)) {
                throw new RuntimeException('Blocker reason is required when blocking a module.');
            }

            if ($status === 'DONE') {
                $openTasks = Task::where('project_module_id', $module->id)->where('status', '!=', 'DONE')->count();
                if ($openTasks > 0) {
                    throw new RuntimeException('All tasks must be DONE before marking module as DONE.');
                }
            }

            if ($status === 'NOT_STARTED' && Task::where('project_module_id', $module->id)->count() > 0) {
                $warnings[] = 'Module has tasks but is being set to NOT_STARTED.';
            }
        }

        $module->update([
            'name' => $data['name'] ?? $module->name,
            'module_name' => $data['module_name'] ?? $module->module_name ?? $module->name,
            'order_no' => $data['order_no'] ?? $module->order_no,
            'status' => $status ?? $module->status,
            'start_date' => $data['start_date'] ?? $module->start_date,
            'due_date' => $data['due_date'] ?? $module->due_date,
            'owner_user_id' => $data['owner_user_id'] ?? $module->owner_user_id,
            'blocker_reason' => $data['blocker_reason'] ?? $module->blocker_reason,
        ]);

        $this->audit->logModel($module, AuditLogger::ACTION_UPDATE);
        if ($status === 'BLOCKED') {
            $this->audit->log('ProjectModule', (string) $module->id, AuditLogger::ACTION_BLOCK);
        }

        return $warnings;
    }

    public function toggleModule(ProjectModule $module): ProjectModule
    {
        $module->is_active = !$module->is_active;
        $module->save();

        $action = $module->is_active ? AuditLogger::ACTION_ACTIVATE : AuditLogger::ACTION_DEACTIVATE;
        $this->audit->logModel($module, $action);

        return $module;
    }

    public function createTask(ProjectModule $module, array $data): Task
    {
        $status = strtoupper($data['status'] ?? 'TODO');
        if ($status === 'BLOCKED' && empty($data['blocker_reason'])) {
            throw new RuntimeException('Blocker reason is required when blocking a task.');
        }
        if ($module->status === 'DONE' && $status !== 'DONE') {
            throw new RuntimeException('Module is DONE; add only DONE tasks or reopen the module first.');
        }

        $task = Task::create([
            'project_module_id' => $module->id,
            'project_id' => $module->project_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
            'status' => $status,
            'priority' => strtoupper($data['priority'] ?? 'MEDIUM'),
            'due_date' => $data['due_date'] ?? null,
            'blocker_reason' => $data['blocker_reason'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['created_by'] ?? null,
        ]);

        $this->audit->logModel($task, AuditLogger::ACTION_CREATE);

        return $task;
    }

    public function updateTask(Task $task, array $data, string $role, ?int $userId): Task
    {
        if ($role === 'Developer' && $task->assignee_user_id !== $userId) {
            throw new RuntimeException('You cannot edit tasks not assigned to you.');
        }

        $newStatus = strtoupper($data['status'] ?? $task->status);
        if ($task->module?->status === 'DONE' && $newStatus !== 'DONE') {
            throw new RuntimeException('Module is DONE; tasks must remain DONE unless module is reopened.');
        }

        if ($newStatus === 'BLOCKED' && empty($data['blocker_reason'] ?? $task->blocker_reason)) {
            throw new RuntimeException('Blocker reason is required when blocking a task.');
        }

        if ($role === 'Developer') {
            $allowed = [
                'status' => $newStatus,
                'blocker_reason' => $data['blocker_reason'] ?? $task->blocker_reason,
            ];
            $task->update($allowed);
        } else {
            $task->update([
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'assignee_user_id' => $data['assignee_user_id'] ?? $task->assignee_user_id,
                'status' => $newStatus,
                'priority' => strtoupper($data['priority'] ?? $task->priority),
                'due_date' => $data['due_date'] ?? $task->due_date,
                'blocker_reason' => $data['blocker_reason'] ?? $task->blocker_reason,
            ]);
        }

        $this->audit->logModel($task, AuditLogger::ACTION_UPDATE);
        if ($newStatus === 'BLOCKED') {
            $this->audit->log('Task', (string) $task->id, AuditLogger::ACTION_BLOCK);
        }

        return $task->fresh(['assignee', 'project', 'module']);
    }

    public function deleteTask(Task $task): void
    {
        $taskId = $task->id;
        $task->delete();
        $this->audit->log('Task', (string) $taskId, AuditLogger::ACTION_DELETE);
    }
}
