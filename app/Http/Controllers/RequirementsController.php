<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequirementStoreRequest;
use App\Http\Requests\RequirementUpdateRequest;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\RequirementVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RequirementsController extends ApiController
{
    public function index(int $projectId): JsonResponse
    {
        $project = Project::find($projectId);
        if (!$project) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found'] ], 404);
        }

        $requirements = Requirement::with(['versions', 'assignments.developer'])
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get();

        return $this->success(['items' => $requirements->toArray()]);
    }

    public function store(RequirementStoreRequest $request, int $projectId): JsonResponse
    {
        $project = Project::find($projectId);
        if (!$project) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found'] ], 404);
        }

        $data = $request->validated();
        $data['project_id'] = $projectId;
        $data['req_code'] = $this->generateReqCode($projectId);

        $currentPhase = $project->currentPhase();
        if ($currentPhase && $currentPhase->phase_key !== 'REQUIREMENTS') {
            $data['is_change_request'] = true;
            $data['source_type'] = 'CHANGE_REQUEST';
        }

        $requirement = Requirement::create($data);

        return $this->success(['item' => $requirement->toArray()]);
    }

    public function show(int $projectId, int $requirementId): JsonResponse
    {
        $requirement = Requirement::where('project_id', $projectId)
            ->with(['versions', 'assignments.developer'])
            ->find($requirementId);

        if (!$requirement) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Requirement not found']], 404);
        }

        return $this->success(['item' => $requirement->toArray()]);
    }

    public function update(RequirementUpdateRequest $request, int $projectId, int $requirementId): JsonResponse
    {
        $requirement = Requirement::where('project_id', $projectId)->find($requirementId);
        if (!$requirement) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Requirement not found']], 404);
        }

        $data = $request->validated();

        DB::transaction(function () use (&$requirement, $data) {
            $requirement->update($data);

            $nextVersion = ($requirement->versions()->max('version_no') ?? 0) + 1;
            RequirementVersion::create([
                'requirement_id' => $requirement->id,
                'version_no' => $nextVersion,
                'payload_json' => $requirement->fresh()->toArray(),
            ]);
        });

        return $this->success(['item' => $requirement->fresh()->toArray()]);
    }

    public function destroy(int $projectId, int $requirementId): JsonResponse
    {
        $requirement = Requirement::where('project_id', $projectId)->find($requirementId);
        if (!$requirement) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Requirement not found']], 404);
        }

        $requirement->delete();

        return $this->success(['deleted' => true]);
    }

    private function generateReqCode(int $projectId): string
    {
        $latest = Requirement::where('project_id', $projectId)
            ->orderByDesc('id')
            ->value('req_code');

        $next = 1;
        if ($latest && preg_match('/REQ-(\\d{4})/', $latest, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return 'REQ-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
