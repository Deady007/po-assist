<?php

namespace App\Http\Controllers;

use App\Models\EmailArtifact;
use App\Models\Project;
use App\Services\EmailGenerationService;
use Illuminate\Http\Request;

class MomController extends Controller
{
    public function draftForm(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $selectedProjectId = $request->query('project_id');

        return view('emails.mom.stepper', [
            'projects'           => $projects,
            'selectedProjectId'  => $selectedProjectId,
            'draft'              => null,
            'refined'            => null,
            'final'              => null,
            'activeStep'         => 1,
        ]);
    }

    public function draftGenerate(Request $request, EmailGenerationService $svc)
    {
        $data = $request->validate([
            'project_id'          => 'required|integer|exists:projects,id',
            'tone'                => 'sometimes|in:formal,executive,neutral',
            'meeting_title'       => 'sometimes|string|nullable',
            'meeting_datetime'    => 'required|string',
            'attendees'           => 'sometimes|string|nullable',
            'agenda'              => 'sometimes|string|nullable',
            'notes_or_transcript' => 'required|string',
        ]);

        $project = Project::findOrFail($data['project_id']);

        $inputForStorage = $data;
        $inputForStorage['project_name'] = $project->name;

        $promptInputs = [
            'tone'                => $data['tone'] ?? 'neutral',
            'project_name'        => $project->name,
            'meeting_title'       => $data['meeting_title'] ?? 'Project Sync',
            'meeting_datetime'    => $data['meeting_datetime'],
            'attendees'           => $data['attendees'] ?? '',
            'agenda'              => $data['agenda'] ?? '',
            'notes_or_transcript' => $data['notes_or_transcript'],
        ];

        $draft = $svc->generateMomDraft(
            $project->id,
            $inputForStorage,
            $promptInputs,
            $data['tone']
        );

        return redirect()->route('emails.mom.refine.form', ['draft' => $draft->id]);
    }

    public function refineForm(int $draft, Request $request)
    {
        $draftArtifact = EmailArtifact::with('project')->findOrFail($draft);
        abort_unless($draftArtifact->type === 'MOM_DRAFT', 404);

        $existingRefined = EmailArtifact::where('type', 'MOM_REFINED')
            ->where('input_json->draft_artifact_id', $draftArtifact->id)
            ->latest()
            ->first();

        $existingFinal = null;
        if ($existingRefined) {
            $existingFinal = EmailArtifact::where('type', 'MOM_FINAL')
                ->where('input_json->refined_artifact_id', $existingRefined->id)
                ->latest()
                ->first();
        }

        $projects = Project::orderBy('name')->get();

        return view('emails.mom.stepper', [
            'projects'          => $projects,
            'selectedProjectId' => $draftArtifact->project_id,
            'draft'             => $draftArtifact,
            'refined'           => $existingRefined,
            'final'             => $existingFinal,
            'activeStep'        => $existingRefined ? 3 : 2,
        ]);
    }

    public function refineGenerate(int $draft, Request $request, EmailGenerationService $svc)
    {
        $draftArtifact = EmailArtifact::with('project')->findOrFail($draft);
        abort_unless($draftArtifact->type === 'MOM_DRAFT', 404);

        $data = $request->validate([
            'tone'                    => 'sometimes|in:formal,executive,neutral',
            'raw_mom'                 => 'required|string',
            'product_update_context'  => 'nullable|string',
        ]);

        $inputForStorage = $data;
        $inputForStorage['draft_artifact_id'] = $draftArtifact->id;
        $inputForStorage['project_name'] = $draftArtifact->project?->name;

        $promptInputs = [
            'tone'                   => $data['tone'] ?? 'formal',
            'raw_mom'                => $data['raw_mom'],
            'product_update_context' => $data['product_update_context'] ?? '',
        ];

        $refined = $svc->refineMom(
            $draftArtifact->project_id,
            $data['tone'],
            $inputForStorage,
            $promptInputs
        );

        return redirect()->route('emails.mom.final.form', ['refined' => $refined->id]);
    }

    public function finalForm(int $refined, Request $request)
    {
        $refinedArtifact = EmailArtifact::with('project')->findOrFail($refined);
        abort_unless($refinedArtifact->type === 'MOM_REFINED', 404);

        $draftId = $refinedArtifact->input_json['draft_artifact_id'] ?? null;
        $draftArtifact = $draftId ? EmailArtifact::find($draftId) : null;

        $finalId = $request->query('final_id');
        $finalArtifact = null;

        if ($finalId) {
            $finalArtifact = EmailArtifact::find($finalId);
        }
        if ($finalArtifact && $finalArtifact->type !== 'MOM_FINAL') {
            $finalArtifact = null;
        }

        if (!$finalArtifact) {
            $finalArtifact = EmailArtifact::where('type', 'MOM_FINAL')
                ->where('input_json->refined_artifact_id', $refinedArtifact->id)
                ->latest()
                ->first();
        }

        $projects = Project::orderBy('name')->get();

        return view('emails.mom.stepper', [
            'projects'          => $projects,
            'selectedProjectId' => $refinedArtifact->project_id,
            'draft'             => $draftArtifact,
            'refined'           => $refinedArtifact,
            'final'             => $finalArtifact,
            'activeStep'        => $finalArtifact ? 3 : 3,
        ]);
    }

    public function finalGenerate(int $refined, Request $request, EmailGenerationService $svc)
    {
        $refinedArtifact = EmailArtifact::with('project')->findOrFail($refined);
        abort_unless($refinedArtifact->type === 'MOM_REFINED', 404);

        $data = $request->validate([
            'tone'          => 'sometimes|in:formal,executive,neutral',
            'meeting_title' => 'sometimes|string|nullable',
            'date'          => 'sometimes|string',
        ]);

        $inputForStorage = $data;
        $inputForStorage['refined_artifact_id'] = $refinedArtifact->id;
        $inputForStorage['draft_artifact_id'] = $refinedArtifact->input_json['draft_artifact_id'] ?? null;

        $promptInputs = [
            'tone'         => $data['tone'] ?? 'formal',
            'meeting_title'=> $data['meeting_title'] ?? 'Meeting',
            'date'         => $data['date'] ?? now()->toDateString(),
            'refined_mom'  => $refinedArtifact->body_text,
        ];

        $final = $svc->generateMomFinalEmail(
            $refinedArtifact->project_id,
            $data['tone'],
            $inputForStorage,
            $promptInputs
        );

        return redirect()->route('emails.mom.final.form', [
            'refined'  => $refinedArtifact->id,
            'final_id' => $final->id,
        ]);
    }
}
