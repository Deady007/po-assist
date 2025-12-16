<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ModuleTemplateRequest;
use App\Models\ModuleTemplate;
use App\Services\ModuleTemplateService;

class ModuleTemplateController extends ApiController
{
    public function __construct(private ModuleTemplateService $service) {}

    public function index()
    {
        $templates = ModuleTemplate::orderBy('order_no')->get();
        return $this->success(['items' => $templates]);
    }

    public function store(ModuleTemplateRequest $request)
    {
        $template = $this->service->create($request->validated());
        return $this->success(['template' => $template], status: 201);
    }

    public function update(ModuleTemplateRequest $request, int $module_template)
    {
        $model = ModuleTemplate::findOrFail($module_template);
        $updated = $this->service->update($model, $request->validated());

        return $this->success(['template' => $updated]);
    }

    public function activate(int $module_template)
    {
        $model = ModuleTemplate::findOrFail($module_template);
        $updated = $this->service->toggle($model);

        return $this->success(['template' => $updated]);
    }
}
