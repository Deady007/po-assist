<?php

namespace App\Modules\Configuration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SequenceConfig;
use App\Modules\Configuration\Http\Requests\SequenceConfigRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SequenceConfigController extends Controller
{
    public function index(): View
    {
        $sequences = SequenceConfig::orderBy('model_name')->get();
        return view('admin.config.sequences', compact('sequences'));
    }

    public function store(SequenceConfigRequest $request): RedirectResponse
    {
        $data = $request->validated();
        SequenceConfig::create([
            'model_name' => $data['model_name'],
            'prefix' => $data['prefix'] ?? null,
            'padding' => $data['padding'],
            'start_from' => $data['start_from'],
            'reset_policy' => $data['reset_policy'],
            'format_template' => $data['format_template'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.config.sequences.index')->with('status', 'Sequence created');
    }

    public function update(SequenceConfigRequest $request, int $sequence): RedirectResponse
    {
        $model = SequenceConfig::findOrFail($sequence);
        $data = $request->validated();

        $model->update([
            'model_name' => $data['model_name'],
            'prefix' => $data['prefix'] ?? null,
            'padding' => $data['padding'],
            'start_from' => $data['start_from'],
            'reset_policy' => $data['reset_policy'],
            'format_template' => $data['format_template'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.config.sequences.index')->with('status', 'Sequence updated');
    }
}
