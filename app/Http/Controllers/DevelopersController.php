<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeveloperStoreRequest;
use App\Models\Developer;
use Illuminate\Http\JsonResponse;

class DevelopersController extends ApiController
{
    public function index(): JsonResponse
    {
        $items = Developer::orderBy('name')->get();
        return $this->success(['items' => $items->toArray()]);
    }

    public function store(DeveloperStoreRequest $request): JsonResponse
    {
        $dev = Developer::create($request->validated());
        return $this->success(['item' => $dev->toArray()]);
    }
}
