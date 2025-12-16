<?php

namespace App\Modules\Configuration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Modules\Configuration\Http\Requests\ProjectStatusRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectStatusController extends Controller
{
    public function index(): View
    {
        $statuses = ProjectStatus::orderBy('order_no')->get();
        return view('admin.config.statuses', compact('statuses'));
    }

    public function store(ProjectStatusRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $status = ProjectStatus::create([
            'name' => $data['name'],
            'order_no' => $data['order_no'],
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($status->is_default) {
            ProjectStatus::where('id', '!=', $status->id)->update(['is_default' => false]);
        }

        return redirect()->route('admin.config.statuses.index')->with('status', 'Status created');
    }

    public function update(ProjectStatusRequest $request, int $status): RedirectResponse
    {
        $model = ProjectStatus::findOrFail($status);
        $data = $request->validated();

        $model->update([
            'name' => $data['name'],
            'order_no' => $data['order_no'],
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? $model->is_active,
            'updated_by' => auth()->id(),
        ]);

        if ($model->is_default) {
            ProjectStatus::where('id', '!=', $model->id)->update(['is_default' => false]);
        }

        return redirect()->route('admin.config.statuses.index')->with('status', 'Status updated');
    }

    public function destroy(int $status): RedirectResponse
    {
        $model = ProjectStatus::findOrFail($status);
        $projectCount = Project::where('status_id', $model->id)->count();

        if ($projectCount > 0) {
            return redirect()->route('admin.config.statuses.index')->withErrors(['Cannot delete status in use.']);
        }

        $model->delete();

        return redirect()->route('admin.config.statuses.index')->with('status', 'Status deleted');
    }
}
