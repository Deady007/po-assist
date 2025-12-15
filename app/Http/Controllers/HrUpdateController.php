<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\EmailGenerationService;
use Illuminate\Http\Request;

class HrUpdateController extends Controller
{
    public function create(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $selectedProjectId = $request->query('project_id');

        return view('emails.hr_update_form', compact('projects', 'selectedProjectId'));
    }

    public function store(Request $request, EmailGenerationService $svc)
    {
        $data = $request->validate([
            'project_id'             => 'required|integer|exists:projects,id',
            'tone'                   => 'sometimes|in:formal,executive,neutral',
            'date'                   => 'sometimes|string',
            'projects_summary'       => 'sometimes|string|nullable',
            'status_per_project'     => 'sometimes|string|nullable',
            'people_or_timeline_risks'=> 'sometimes|string|nullable',
            'tomorrow_plan'          => 'sometimes|string|nullable',
        ]);

        $project = Project::findOrFail($data['project_id']);

        $inputForStorage = $data;
        $inputForStorage['project_name'] = $project->name;

        $promptInputs = [
            'tone'                    => $data['tone'] ?? 'neutral',
            'date'                    => $data['date'] ?? now()->toDateString(),
            'project_name'            => $project->name,
            'projects_summary'        => $data['projects_summary'] ?? '',
            'status_per_project'      => $data['status_per_project'] ?? '',
            'people_or_timeline_risks'=> $data['people_or_timeline_risks'] ?? '',
            'tomorrow_plan'           => $data['tomorrow_plan'] ?? '',
        ];

        $artifact = $svc->generateHrUpdate(
            $project->id,
            $promptInputs['tone'],
            $inputForStorage,
            $promptInputs
        );

        return view('emails.result', [
            'artifact' => $artifact,
            'project'  => $project,
            'heading'  => 'HR End-of-Day Update',
            'typeLabel'=> 'HR_UPDATE',
        ]);
    }
}
