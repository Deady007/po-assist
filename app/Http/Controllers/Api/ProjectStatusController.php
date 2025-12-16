<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ProjectStatusRequest;
use App\Models\ProjectStatus;
use App\Services\ProjectStatusService;
use Illuminate\Http\Request;

class ProjectStatusController extends ApiController
{
    public function __construct(private ProjectStatusService $service) {}

    public function index()
    {
        $statuses = ProjectStatus::orderBy('order_no')->get();
        return $this->success(['items' => $statuses]);
    }

    public function store(ProjectStatusRequest $request)
    {
        $status = $this->service->create($request->validated());
        return $this->success(['status' => $status], status: 201);
    }

    public function update(ProjectStatusRequest $request, int $project_status)
    {
        $status = ProjectStatus::findOrFail($project_status);
        $updated = $this->service->update($status, $request->validated());
        return $this->success(['status' => $updated]);
    }

    public function setDefault(int $project_status)
    {
        $status = ProjectStatus::findOrFail($project_status);
        $updated = $this->service->setDefault($status);

        return $this->success(['status' => $updated]);
    }

    public function activate(int $project_status)
    {
        $status = ProjectStatus::findOrFail($project_status);
        $updated = $this->service->toggle($status);

        return $this->success(['status' => $updated]);
    }
}
