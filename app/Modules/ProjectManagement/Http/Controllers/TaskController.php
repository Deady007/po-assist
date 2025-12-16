<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProjectModule;
use App\Models\Task;
use App\Modules\ProjectManagement\Http\Requests\TaskRequest;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class TaskController extends Controller
{
    public function __construct(private WorkflowService $workflow)
    {
    }

    public function store(TaskRequest $request, int $project, int $module): RedirectResponse
    {
        $data = $request->validated();

        $moduleModel = ProjectModule::where('project_id', $project)->findOrFail($module);

        try {
            $this->workflow->createTask($moduleModel, $data + ['created_by' => auth()->id()]);
        } catch (RuntimeException $e) {
            return redirect()->back()->withErrors([$e->getMessage()])->withInput();
        }

        return redirect()->route('admin.projects.workflow', $project)->with('status', 'Task added');
    }

    public function update(TaskRequest $request, int $project, int $module, int $task): RedirectResponse
    {
        $data = $request->validated();

        $moduleModel = ProjectModule::where('project_id', $project)->findOrFail($module);
        $model = Task::where('project_module_id', $moduleModel->id)->findOrFail($task);

        $role = auth()->user()?->role?->name ?? '';
        try {
            $this->workflow->updateTask($model, $data, $role, auth()->id());
        } catch (RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'assigned') ? 403 : 400;
            $redirect = redirect()->back();

            if ($code === 403) {
                return $redirect->withErrors(['You cannot edit tasks not assigned to you.']);
            }

            return $redirect->withErrors([$e->getMessage()])->withInput();
        }

        return redirect()->route('admin.projects.workflow', $project)->with('status', 'Task updated');
    }

    public function destroy(int $project, int $module, int $task): RedirectResponse
    {
        $moduleModel = ProjectModule::where('project_id', $project)->findOrFail($module);
        $model = Task::where('project_module_id', $moduleModel->id)->findOrFail($task);
        $this->workflow->deleteTask($model);

        return redirect()->route('admin.projects.workflow', $project)->with('status', 'Task removed');
    }
}
