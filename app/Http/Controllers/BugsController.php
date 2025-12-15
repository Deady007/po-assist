<?php

namespace App\Http\Controllers;

use App\Http\Requests\BugStoreRequest;
use App\Http\Requests\BugUpdateRequest;
use App\Models\Bug;
use Illuminate\Http\JsonResponse;

class BugsController extends ApiController
{
    public function index(int $projectId): JsonResponse
    {
        $items = Bug::with(['requirement', 'assignedTo', 'resolvedBy'])
            ->where('project_id', $projectId)
            ->orderByDesc('opened_at')
            ->get();

        return $this->success(['items' => $items->toArray()]);
    }

    public function store(BugStoreRequest $request, int $projectId): JsonResponse
    {
        $data = $request->validated();
        $data['project_id'] = $projectId;
        $data['opened_at'] = now();

        if (!empty($data['requirement_id'])) {
            $reqProjectId = \App\Models\Requirement::where('id', $data['requirement_id'])->value('project_id');
            if ($reqProjectId && (int) $reqProjectId !== (int) $projectId) {
                return $this->failure([['code' => 'PROJECT_MISMATCH', 'message' => 'Requirement not in project']], 422);
            }
        }

        $bug = Bug::create($data);

        return $this->success(['item' => $bug->toArray()]);
    }

    public function update(BugUpdateRequest $request, int $projectId, int $id): JsonResponse
    {
        $bug = Bug::where('project_id', $projectId)->find($id);
        if (!$bug) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Bug not found']], 404);
        }

        $data = $request->validated();
        if (isset($data['status'])) {
            if ($data['status'] === 'FIXED' && !$bug->fixed_at) {
                $data['fixed_at'] = now();
            }
            if ($data['status'] === 'CLOSED' && !$bug->closed_at) {
                $data['closed_at'] = now();
            }
        }

        $bug->update($data);

        return $this->success(['item' => $bug->fresh()->toArray()]);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        $bug = Bug::where('project_id', $projectId)->find($id);
        if (!$bug) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Bug not found']], 404);
        }

        $bug->delete();
        return $this->success(['deleted' => true]);
    }
}
