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
            'tone'         => 'required|in:formal,executive,neutral',
            'date'         => 'required|string',
            'completed'    => 'required|string',
            'in_progress'  => 'required|string',
            'risks'        => 'required|string',
            'review_topics'=> 'required|string',
        ]);

        $project = Project::findOrFail($data['project_id']);

        // What we store (audit/history)
        $inputForStorage = $data;
        $inputForStorage['project_name'] = $project->name;

        // What we pass to prompt
        $promptInputs = [
            'tone'         => $data['tone'],
            'project_name' => $project->name,
            'date'         => $data['date'],
            'completed'    => $data['completed'],
            'in_progress'  => $data['in_progress'],
            'risks'        => $data['risks'],
            'review_topics'=> $data['review_topics'],
        ];

        $artifact = $svc->generateProductUpdateAndStore(
            $project->id,
            $data['tone'],
            $inputForStorage,
            $promptInputs
        );

        return view('emails.result', compact('artifact', 'project'));
    }

    public function history()
    {
        $items = EmailArtifact::with('project')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('history.index', compact('items'));
    }

    public function historyShow(int $id)
    {
        $artifact = EmailArtifact::with('project')->findOrFail($id);
        return view('history.show', compact('artifact'));
    }
}
