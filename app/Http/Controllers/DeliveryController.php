<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryStoreRequest;
use App\Models\Delivery;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class DeliveryController extends ApiController
{
    public function show(int $projectId): JsonResponse
    {
        $delivery = Delivery::where('project_id', $projectId)->latest('delivery_date')->first();
        return $this->success(['item' => $delivery]);
    }

    public function store(DeliveryStoreRequest $request, int $projectId): JsonResponse
    {
        if (!Project::find($projectId)) {
            return $this->failure([['code' => 'PROJECT_NOT_FOUND', 'message' => 'Project not found']], 404);
        }

        $data = $request->validated();
        $data['project_id'] = $projectId;

        $existing = Delivery::where('project_id', $projectId)->first();
        if ($existing) {
            $existing->update($data);
            $delivery = $existing->fresh();
        } else {
            $delivery = Delivery::create($data);
        }

        return $this->success(['item' => $delivery->toArray()]);
    }
}
