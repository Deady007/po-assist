<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\EmailGenerationService;
use Illuminate\Http\Request;

class MeetingScheduleController extends Controller
{
    public function create(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $selectedProjectId = $request->query('project_id');

        return view('emails.meeting_schedule_form', compact('projects', 'selectedProjectId'));
    }

    public function store(Request $request, EmailGenerationService $svc)
    {
        $data = $request->validate([
            'project_id'              => 'required|integer|exists:projects,id',
            'tone'                    => 'sometimes|in:formal,executive,neutral',
            'meeting_title'           => 'sometimes|string|nullable',
            'meeting_datetime'        => 'required|string',
            'duration'                => 'sometimes|string|nullable',
            'attendees'               => 'sometimes|string|nullable',
            'agenda_topics'           => 'sometimes|string|nullable',
            'meeting_location_or_link'=> 'sometimes|string|nullable',
        ]);

        $project = Project::findOrFail($data['project_id']);

        $inputForStorage = $data;
        $inputForStorage['project_name'] = $project->name;

        $promptInputs = [
            'tone'                     => $data['tone'] ?? 'formal',
            'project_name'             => $project->name,
            'meeting_title'            => $data['meeting_title'] ?? null,
            'meeting_datetime'         => $data['meeting_datetime'],
            'duration'                 => $data['duration'] ?? '60 minutes',
            'attendees'                => $data['attendees'] ?? '',
            'agenda_topics'            => $data['agenda_topics'] ?? '',
            'meeting_location_or_link' => $data['meeting_location_or_link'] ?? '',
        ];

        $artifact = $svc->generateMeetingSchedule(
            $project->id,
            $promptInputs['tone'],
            $inputForStorage,
            $promptInputs
        );

        return view('emails.result', [
            'artifact' => $artifact,
            'project'  => $project,
            'heading'  => 'Meeting Schedule Email',
            'typeLabel'=> 'MEETING_SCHEDULE',
        ]);
    }
}
