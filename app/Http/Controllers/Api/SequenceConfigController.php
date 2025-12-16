<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\SequenceConfig;
use App\Modules\Configuration\Http\Requests\SequenceConfigRequest;
use App\Services\AuditLogger;

class SequenceConfigController extends ApiController
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        $configs = SequenceConfig::orderBy('model_name')->get();
        return $this->success(['sequences' => $configs]);
    }

    public function store(SequenceConfigRequest $request)
    {
        $config = SequenceConfig::create($request->validated());
        $this->audit->logModel($config, AuditLogger::ACTION_CREATE);

        return $this->success(['sequence' => $config], status: 201);
    }

    public function update(SequenceConfigRequest $request, int $sequence)
    {
        $config = SequenceConfig::findOrFail($sequence);
        $config->update($request->validated());
        $this->audit->logModel($config, AuditLogger::ACTION_UPDATE);

        return $this->success(['sequence' => $config]);
    }
}
