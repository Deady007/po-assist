<?php

namespace App\Http\Controllers;

use App\Services\WorkflowWarningService;
use Illuminate\Http\JsonResponse;

class WarningsController extends ApiController
{
    public function __construct(private WorkflowWarningService $warnings)
    {
    }

    public function projectWarnings(int $projectId): JsonResponse
    {
        $list = $this->warnings->getWarnings($projectId);
        return $this->success(['items' => $list]);
    }
}
