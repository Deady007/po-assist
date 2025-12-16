<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\Task;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use RuntimeException;

class TaskController extends ApiController
{
    public function __construct(private WorkflowService $workflow) {}

    public function index(Request $request)
    {
        $query = Task::with(['project', 'module', 'assignee']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->query('project_id'));
        }
        if ($request->filled('module_id')) {
            $query->where('project_module_id', $request->query('module_id'));
        }
        if ($request->filled('assignee_user_id')) {
            $query->where('assignee_user_id', $request->query('assignee_user_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }
        if ($request->boolean('overdue_only')) {
            $query->whereDate('due_date', '<', now()->startOfDay())->where('status', '!=', 'DONE');
        }
        if ($request->filled('due_from')) {
            $query->whereDate('due_date', '>=', $request->query('due_from'));
        }
        if ($request->filled('due_to')) {
            $query->whereDate('due_date', '<=', $request->query('due_to'));
        }
        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $sort = $request->query('sort', 'due_date');
        $dir = $request->query('dir', 'asc');
        $query->orderBy($sort, $dir);

        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $paginator = $query->paginate($perPage);

        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(TaskStoreRequest $request, int $projectId, int $moduleId)
    {
        $project = Project::findOrFail($projectId);
        $module = ProjectModule::where('project_id', $project->id)->findOrFail($moduleId);

        try {
            $task = $this->workflow->createTask($module, $request->validated() + ['created_by' => $request->user()?->id]);
        } catch (RuntimeException $e) {
            return $this->failure([['code' => 'TASK_INVALID', 'message' => $e->getMessage()]], 400);
        }

        return $this->success(['task' => $task->fresh(['assignee'])], status: 201);
    }

    public function show(int $task)
    {
        $model = Task::with(['project', 'module', 'assignee'])->findOrFail($task);
        return $this->success(['task' => $model]);
    }

    public function update(TaskUpdateRequest $request, int $task)
    {
        $model = Task::findOrFail($task);
        $role = $request->user()?->role?->name ?? '';
        $userId = $request->user()?->id;

        try {
            $updated = $this->workflow->updateTask($model, $request->validated(), $role, $userId);
        } catch (RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'assigned') ? 403 : 400;
            return $this->failure([['code' => 'TASK_INVALID', 'message' => $e->getMessage()]], $code);
        }

        return $this->success(['task' => $updated]);
    }

    public function destroy(int $task)
    {
        $model = Task::findOrFail($task);
        $this->workflow->deleteTask($model);

        return $this->success(['message' => 'Task deleted']);
    }
}
