<?php

namespace App\Http\Controllers;

use App\Http\Requests\TesterStoreRequest;
use App\Models\Tester;
use Illuminate\Http\JsonResponse;

class TestersController extends ApiController
{
    public function index(): JsonResponse
    {
        $items = Tester::orderBy('name')->get();
        return $this->success(['items' => $items->toArray()]);
    }

    public function store(TesterStoreRequest $request): JsonResponse
    {
        $tester = Tester::create($request->validated());
        return $this->success(['item' => $tester->toArray()]);
    }
}
