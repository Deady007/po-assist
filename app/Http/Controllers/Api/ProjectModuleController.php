<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ProjectModuleStoreRequest;
use App\Http\Requests\ProjectModuleUpdateRequest;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use RuntimeException;

class ProjectModuleController extends ApiController
{
    public function __construct(private WorkflowService $workflow) {}

    public function init(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $result = $this->workflow->initModules($project);

        $modules = $this->workflow->listModules($projectId);

        return $this->success([
            'modules' => $modules,
            'created_count' => $result['created_count'],
            'existing_count' => $result['existing_count'],
        ]);
    }

    public function index(int $projectId)
    {
        Project::findOrFail($projectId);
        $modules = $this->workflow->listModules($projectId);

        return $this->success(['modules' => $modules]);
    }

    public function store(ProjectModuleStoreRequest $request, int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $module = $this->workflow->createModule($project, $request->validated() + ['created_by' => $request->user()?->id]);

        return $this->success(['module' => $module], status: 201);
    }

    public function update(ProjectModuleUpdateRequest $request, int $module)
    {
        $model = ProjectModule::findOrFail($module);
        try {
            $warnings = $this->workflow->updateModule($model, $request->validated());
        } catch (RuntimeException $e) {
            return $this->failure([['code' => 'MODULE_INVALID', 'message' => $e->getMessage()]], 400);
        }

        return $this->success(['module' => $model->fresh(['owner', 'tasks'])], $warnings);
    }

    public function activate(int $module)
    {
        $model = ProjectModule::findOrFail($module);
        $updated = $this->workflow->toggleModule($model);

        return $this->success(['module' => $updated]);
    }
}
