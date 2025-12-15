<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokenRequestStoreRequest;
use App\Http\Requests\TokenRequestTransitionRequest;
use App\Http\Requests\TokenRequestUpdateRequest;
use App\Models\TokenRequest;
use App\Models\TokenWallet;
use Illuminate\Http\JsonResponse;

class TokenRequestsController extends ApiController
{
    public function index(int $projectId): JsonResponse
    {
        $items = TokenRequest::where('project_id', $projectId)->orderByDesc('id')->get();
        return $this->success(['items' => $items->toArray()]);
    }

    public function store(TokenRequestStoreRequest $request, int $projectId): JsonResponse
    {
        $data = $request->validated();
        $data['project_id'] = $projectId;
        $item = TokenRequest::create($data);
        return $this->success(['item' => $item->toArray()]);
    }

    public function update(TokenRequestUpdateRequest $request, int $projectId, int $id): JsonResponse
    {
        $item = TokenRequest::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Token request not found']], 404);
        }

        $item->update($request->validated());
        return $this->success(['item' => $item->fresh()->toArray()]);
    }

    public function destroy(int $projectId, int $id): JsonResponse
    {
        $item = TokenRequest::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Token request not found']], 404);
        }
        $item->delete();
        return $this->success(['deleted' => true]);
    }

    public function transition(TokenRequestTransitionRequest $request, int $projectId, int $id): JsonResponse
    {
        $item = TokenRequest::where('project_id', $projectId)->find($id);
        if (!$item) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Token request not found']], 404);
        }

        $action = $request->input('action');
        $updates = [];

        if ($action === 'APPROVE') {
            $updates['status'] = 'APPROVED';
            $updates['approved_at'] = now();
        } elseif ($action === 'REJECT') {
            $updates['status'] = 'REJECTED';
        } elseif ($action === 'DONE') {
            $updates['status'] = 'DONE';
            $updates['done_at'] = now();
            $wallet = TokenWallet::firstOrCreate(
                ['project_id' => $projectId],
                ['total_tokens' => 0, 'used_tokens' => 0]
            );
            $wallet->update([
                'used_tokens' => ($wallet->used_tokens + $item->tokens_estimated),
            ]);
        }

        $item->update($updates);

        return $this->success(['item' => $item->fresh()->toArray()]);
    }
}
