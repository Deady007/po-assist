<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\EmailArtifact;
use App\Services\EmailGenerationService;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function productUpdateForm(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $selectedProjectId = $request->query('project_id');

        return view('emails.product_update_form', compact('projects', 'selectedProjectId'));
    }

    public function productUpdateGenerate(Request $request, EmailGenerationService $svc)
    {
        $data = $request->validate([
            'project_id'   => 'required|integer|exists:projects,id',
            'tone'         => 'sometimes|in:formal,executive,neutral',
            'date'         => 'sometimes|string',
            'completed'    => 'sometimes|string|nullable',
            'in_progress'  => 'sometimes|string|nullable',
            'risks'        => 'sometimes|string|nullable',
            'review_topics'=> 'sometimes|string|nullable',
            'highlights'   => 'sometimes|string|nullable',
        ]);

        $project = Project::findOrFail($data['project_id']);

        // What we store (audit/history)
        $inputForStorage = $data;
        $inputForStorage['project_name'] = $project->name;

        // What we pass to prompt
        $promptInputs = [
            'tone'         => $data['tone'] ?? 'formal',
            'project_name' => $project->name,
            'date'         => $data['date'] ?? now()->toDateString(),
            'completed'    => $data['completed'] ?? '',
            'in_progress'  => $data['in_progress'] ?? '',
            'risks'        => $data['risks'] ?? '',
            'review_topics'=> $data['review_topics'] ?? '',
            'highlights'   => $data['highlights'] ?? '',
        ];

        $artifact = $svc->generateProductUpdateAndStore(
            $project->id,
            $promptInputs['tone'],
            $inputForStorage,
            $promptInputs
        );

        return view('emails.result', [
            'artifact' => $artifact,
            'project'  => $project,
            'heading'  => 'Product Update Email',
            'typeLabel'=> 'PRODUCT_UPDATE',
        ]);
    }

    public function history()
    {
        $items = EmailArtifact::with('project')
            ->orderByDesc('created_at')
            ->limit(120)
            ->get();

        $grouped = $items->groupBy(function ($it) {
            return $it->project?->name ?? 'Unassigned';
        })->map(function ($projectGroup) {
            return $projectGroup->groupBy('type')->map(function ($typeGroup) {
                return $typeGroup->sortByDesc('created_at');
            });
        });

        return view('history.index', [
            'grouped' => $grouped,
        ]);
    }

    public function historyShow(int $id)
    {
        $artifact = EmailArtifact::with('project')->findOrFail($id);
        return view('history.show', compact('artifact'));
    }
}
