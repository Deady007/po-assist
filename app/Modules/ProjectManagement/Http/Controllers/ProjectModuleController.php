<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Modules\ProjectManagement\Http\Requests\ProjectModuleRequest;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ProjectModuleController extends Controller
{
    public function __construct(private WorkflowService $workflow)
    {
    }

    public function init(int $project): RedirectResponse
    {
        $model = Project::findOrFail($project);
        $result = $this->workflow->initModules($model);

        $message = "Modules initialized. Created {$result['created_count']}, skipped {$result['existing_count']}.";

        return redirect()->back()->with('status', $message);
    }

    public function store(ProjectModuleRequest $request, int $project): RedirectResponse
    {
        $data = $request->validated();
        $projectModel = Project::findOrFail($project);

        $this->workflow->createModule($projectModel, $data + ['created_by' => auth()->id()]);

        return redirect()->route('admin.projects.developer_assign', $project)->with('status', 'Module added');
    }

    public function update(ProjectModuleRequest $request, int $project, int $module): RedirectResponse
    {
        $data = $request->validated();

        $model = ProjectModule::where('project_id', $project)->findOrFail($module);
        try {
            $warnings = $this->workflow->updateModule($model, $data);
        } catch (RuntimeException $e) {
            return redirect()->back()->withErrors([$e->getMessage()])->withInput();
        }

        $message = 'Module updated';
        if (!empty($warnings)) {
            $message .= ' (warnings: ' . implode('; ', $warnings) . ')';
        }

        return redirect()->route('admin.projects.developer_assign', $project)->with('status', $message);
    }

    public function destroy(int $project, int $module): RedirectResponse
    {
        $model = ProjectModule::where('project_id', $project)->findOrFail($module);
        $model->delete();

        return redirect()->route('admin.projects.developer_assign', $project)->with('status', 'Module removed');
    }
}
