<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequirementAssignmentStoreRequest;
use App\Http\Requests\RequirementAssignmentUpdateRequest;
use App\Models\RequirementAssignment;
use Illuminate\Http\JsonResponse;

class RequirementAssignmentsController extends ApiController
{
    public function index(int $projectId): JsonResponse
    {
        $items = RequirementAssignment::with(['developer', 'requirement'])
            ->whereHas('requirement', fn ($q) => $q->where('project_id', $projectId))
            ->orderByDesc('assigned_at')
            ->get();

        return $this->success(['items' => $items->toArray()]);
    }

    public function store(RequirementAssignmentStoreRequest $request, int $projectId): JsonResponse
    {
        $data = $request->validated();
        $requirementProjectId = $data['requirement_id']
            ? \App\Models\Requirement::where('id', $data['requirement_id'])->value('project_id')
            : null;

        if ($requirementProjectId && (int) $requirementProjectId !== (int) $projectId) {
            return $this->failure([['code' => 'PROJECT_MISMATCH', 'message' => 'Requirement not in project']], 422);
        }

        $data['assigned_at'] = now();

        $assignment = RequirementAssignment::create($data);

        return $this->success(['item' => $assignment->load(['developer', 'requirement'])->toArray()]);
    }

    public function update(RequirementAssignmentUpdateRequest $request, int $projectId, int $id): JsonResponse
    {
        $assignment = RequirementAssignment::whereHas('requirement', fn ($q) => $q->where('project_id', $projectId))
            ->find($id);

        if (!$assignment) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Assignment not found']], 404);
        }

        $assignment->update($request->validated());

        return $this->success(['item' => $assignment->fresh()->toArray()]);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        $assignment = RequirementAssignment::whereHas('requirement', fn ($q) => $q->where('project_id', $projectId))
            ->find($id);

        if (!$assignment) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Assignment not found']], 404);
        }

        $assignment->delete();
        return $this->success(['deleted' => true]);
    }
}
