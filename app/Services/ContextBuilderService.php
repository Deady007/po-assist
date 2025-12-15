<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\EmailArtifact;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\RequirementAssignment;
use App\Models\TestResult;
use App\Models\ValidationReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContextBuilderService
{
    public function __construct(private WorkflowWarningService $warnings)
    {
    }

    public function productUpdate(int $projectId, array $input = []): array
    {
        $project = Project::with('phases')->findOrFail($projectId);
        $today = Carbon::parse($input['date'] ?? now())->toDateString();

        $recentRequirements = Requirement::where('project_id', $projectId)
            ->whereDate('updated_at', '>=', Carbon::parse($today)->subDay()->toDateString())
            ->limit(20)
            ->get(['req_code', 'title', 'status', 'priority']);

        $recentAssignments = RequirementAssignment::with(['developer', 'requirement'])
            ->whereHas('requirement', fn ($q) => $q->where('project_id', $projectId))
            ->orderByDesc('assigned_at')
            ->limit(10)
            ->get();

        $recentBugs = Bug::where('project_id', $projectId)
            ->orderByDesc('opened_at')
            ->limit(10)
            ->get(['title', 'severity', 'status']);

        $recentTests = TestResult::whereHas('testRun', fn ($q) => $q->where('project_id', $projectId))
            ->orderByDesc('id')
            ->limit(10)
            ->get(['status', 'remarks']);

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'phase' => $project->currentPhase()?->phase_key,
            ],
            'date' => $today,
            'highlights' => $input['highlights'] ?? null,
            'requirements' => $recentRequirements->toArray(),
            'assignments' => $recentAssignments->map(function ($a) {
                return [
                    'requirement' => $a->requirement?->req_code,
                    'developer' => $a->developer?->name,
                    'status' => $a->status,
                    'eta_date' => $a->eta_date,
                ];
            }),
            'bugs' => $recentBugs,
            'tests' => $recentTests,
            'warnings' => $this->warnings->getWarnings($projectId),
        ];
    }

    public function meetingSchedule(int $projectId, array $input): array
    {
        $project = Project::findOrFail($projectId);

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'meeting_datetime' => $input['meeting_datetime'] ?? null,
            'duration' => $input['duration'] ?? '60 minutes',
            'attendees' => $input['attendees'] ?? [],
            'warnings' => $this->warnings->getWarnings($projectId),
            'agenda_seeds' => $this->agendaSeeds($projectId),
        ];
    }

    public function momDraft(int $projectId, array $input): array
    {
        $project = Project::findOrFail($projectId);
        $lastProductUpdate = EmailArtifact::where('project_id', $projectId)
            ->where('type', 'PRODUCT_UPDATE')
            ->orderByDesc('id')->first();

        return [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'meeting_title' => $input['meeting_title'] ?? 'Project Sync',
            'meeting_datetime' => $input['meeting_datetime'] ?? now()->toDateTimeString(),
            'attendees' => $input['attendees'] ?? [],
            'notes_or_transcript' => $input['notes_or_transcript'] ?? '',
            'prior_product_update' => $lastProductUpdate?->body_text,
            'open_change_requests' => Requirement::where('project_id', $projectId)->where('is_change_request', true)->get(['req_code', 'title']),
            'warnings' => $this->warnings->getWarnings($projectId),
        ];
    }

    public function momRefine(array $input): array
    {
        return [
            'raw_mom' => $input['raw_mom'] ?? '',
            'product_update_context' => $input['product_update_context'] ?? '',
        ];
    }

    public function hrEod(array $input): array
    {
        $date = Carbon::parse($input['date'] ?? now())->toDateString();
        $projects = Project::with('phases')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'phase' => $p->currentPhase()?->phase_key,
                'warnings' => app(WorkflowWarningService::class)->getWarnings($p->id),
            ];
        });

        return [
            'date' => $date,
            'projects' => $projects,
        ];
    }

    public function validationExecSummary(int $reportId): array
    {
        $report = ValidationReport::findOrFail($reportId);
        return [
            'report' => $report->report_json,
        ];
    }

    public function rfpExtraction(string $text, ?int $projectId = null): array
    {
        $project = $projectId ? Project::find($projectId) : null;
        return [
            'project' => $project ? ['id' => $project->id, 'name' => $project->name] : null,
            'rfp_text' => $text,
        ];
    }

    private function agendaSeeds(int $projectId): array
    {
        $warnings = $this->warnings->getWarnings($projectId);
        $critical = array_filter($warnings, fn ($w) => ($w['severity'] ?? '') === 'HIGH');
        $openBugs = Bug::where('project_id', $projectId)
            ->whereIn('severity', ['CRITICAL', 'HIGH'])
            ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
            ->count();
        return [
            'critical_warnings' => $critical,
            'open_blockers' => $openBugs,
        ];
    }
}
