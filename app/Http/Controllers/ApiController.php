<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(array $data = [], array $warnings = [], int $status = 200): JsonResponse
    {
        return response()->json(ApiResponse::success($data, $warnings), $status);
    }

    protected function failure(array $errors, int $status = 400, array $warnings = []): JsonResponse
    {
        return response()->json(ApiResponse::failure($errors, $warnings), $status);
    }
}
