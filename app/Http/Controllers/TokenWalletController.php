<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokenWalletUpdateRequest;
use App\Models\TokenWallet;
use Illuminate\Http\JsonResponse;

class TokenWalletController extends ApiController
{
    public function show(int $projectId): JsonResponse
    {
        $wallet = TokenWallet::where('project_id', $projectId)->first();
        return $this->success(['item' => $wallet]);
    }

    public function update(TokenWalletUpdateRequest $request, int $projectId): JsonResponse
    {
        $data = $request->validated();
        $data['project_id'] = $projectId;

        $wallet = TokenWallet::updateOrCreate(
            ['project_id' => $projectId],
            [
                'total_tokens' => $data['total_tokens'],
                'used_tokens' => $data['used_tokens'] ?? 0,
            ]
        );

        return $this->success(['item' => $wallet->toArray()]);
    }
}
