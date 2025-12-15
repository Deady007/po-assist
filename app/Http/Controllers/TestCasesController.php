<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCaseStoreRequest;
use App\Http\Requests\TestCaseUpdateRequest;
use App\Models\TestCase;
use Illuminate\Http\JsonResponse;

class TestCasesController extends ApiController
{
    public function index(int $projectId): JsonResponse
    {
        $items = TestCase::with('requirement')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get();

        return $this->success(['items' => $items->toArray()]);
    }

    public function store(TestCaseStoreRequest $request, int $projectId): JsonResponse
    {
        $data = $request->validated();
        $data['project_id'] = $projectId;
        $item = TestCase::create($data);

        return $this->success(['item' => $item->toArray()]);
    }

    public function update(TestCaseUpdateRequest $request, int $projectId, int $id): JsonResponse
    {
        $item = TestCase::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Test case not found']], 404);
        }

        $item->update($request->validated());

        return $this->success(['item' => $item->fresh()->toArray()]);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        $item = TestCase::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Test case not found']], 404);
        }
        $item->delete();
        return $this->success(['deleted' => true]);
    }
}
