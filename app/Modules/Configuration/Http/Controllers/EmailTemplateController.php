<?php

namespace App\Modules\Configuration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = EmailTemplate::orderBy('code')->get();
        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.config.email_templates', compact('templates', 'clients', 'projects'));
    }

    public function store(): RedirectResponse
    {
        $data = $this->validatedData();
        $this->ensureUnique($data);

        EmailTemplate::create($data + [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.config.email_templates.index')->with('status', 'Template created');
    }

    public function update(int $template): RedirectResponse
    {
        $model = EmailTemplate::findOrFail($template);
        $data = $this->validatedData($template);
        $this->ensureUnique($data, $template);

        $model->update($data + [
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.config.email_templates.index')->with('status', 'Template updated');
    }

    public function destroy(int $template): RedirectResponse
    {
        $model = EmailTemplate::findOrFail($template);
        $model->delete();

        return redirect()->route('admin.config.email_templates.index')->with('status', 'Template deleted');
    }

    private function validatedData(?int $templateId = null): array
    {
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'scope_type' => 'required|in:global,client,project',
            'scope_id' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        if ($data['scope_type'] === 'client') {
            request()->validate(['scope_id' => 'required|exists:clients,id']);
        }

        if ($data['scope_type'] === 'project') {
            request()->validate(['scope_id' => 'required|exists:projects,id']);
        }

        $data['scope_id'] = $data['scope_type'] === 'global' ? null : $data['scope_id'];

        return $data;
    }

    private function ensureUnique(array $data, ?int $ignoreId = null): void
    {
        $exists = EmailTemplate::where('code', $data['code'])
            ->where('scope_type', $data['scope_type'])
            ->where('scope_id', $data['scope_id'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            abort(422, 'A template with the same code and scope already exists.');
        }
    }
}
