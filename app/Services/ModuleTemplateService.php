<?php

namespace App\Services;

use App\Models\ModuleTemplate;
use Illuminate\Support\Facades\DB;

class ModuleTemplateService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data): ModuleTemplate
    {
        $template = ModuleTemplate::create($data);
        $this->audit->logModel($template, AuditLogger::ACTION_CREATE);

        return $template;
    }

    public function update(ModuleTemplate $template, array $data): ModuleTemplate
    {
        $template->update($data);
        $this->audit->logModel($template, AuditLogger::ACTION_UPDATE);

        return $template;
    }

    public function toggle(ModuleTemplate $template): ModuleTemplate
    {
        $template->is_active = !$template->is_active;
        $template->save();

        $action = $template->is_active ? AuditLogger::ACTION_ACTIVATE : AuditLogger::ACTION_DEACTIVATE;
        $this->audit->logModel($template, $action);

        return $template;
    }
}
