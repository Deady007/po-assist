<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\DataItem;
use App\Models\Developer;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\RequirementAssignment;
use App\Models\RfpDocument;
use App\Models\TestCase;
use App\Models\TestRun;
use App\Models\Tester;
use App\Models\TokenRequest;
use App\Models\TokenWallet;
use App\Models\ValidationReport;
use App\Services\WorkflowWarningService;

class ModulePagesController extends Controller
{
    public function __construct(private WorkflowWarningService $warnings)
    {
    }

    public function requirements(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $requirements = Requirement::where('project_id', $projectId)->orderBy('id')->get();
        $rfps = RfpDocument::where('project_id', $projectId)->get();
        $warnings = $this->warnings->getWarnings($projectId);

        return view('modules.requirements', compact('project', 'requirements', 'rfps', 'warnings'));
    }

    public function dataItems(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $items = DataItem::where('project_id', $projectId)->orderBy('due_date')->get();
        $warnings = $this->warnings->getWarnings($projectId);

        return view('modules.data_items', compact('project', 'items', 'warnings'));
    }

    public function masterData(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $changes = \DB::table('master_data_changes')->where('project_id', $projectId)->orderBy('id')->get();
        $warnings = $this->warnings->getWarnings($projectId);

        return view('modules.master_data', compact('project', 'changes', 'warnings'));
    }

    public function developers()
    {
        $developers = Developer::orderBy('name')->get();
        return view('modules.developers', compact('developers'));
    }

    public function assignments(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $assignments = RequirementAssignment::with(['developer', 'requirement'])
            ->whereHas('requirement', fn ($q) => $q->where('project_id', $projectId))
            ->orderByDesc('assigned_at')
            ->get();
        $requirements = Requirement::where('project_id', $projectId)->get();
        $developers = Developer::orderBy('name')->get();
        $warnings = $this->warnings->getWarnings($projectId);

        return view('modules.assignments', compact('project', 'assignments', 'requirements', 'developers', 'warnings'));
    }

    public function bugs(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $bugs = Bug::where('project_id', $projectId)->orderByDesc('opened_at')->get();
        $requirements = Requirement::where('project_id', $projectId)->get();
        $developers = Developer::orderBy('name')->get();
        $warnings = $this->warnings->getWarnings($projectId);

        return view('modules.bugs', compact('project', 'bugs', 'requirements', 'developers', 'warnings'));
    }

    public function testing(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $testCases = TestCase::where('project_id', $projectId)->get();
        $testRuns = TestRun::with('tester')->where('project_id', $projectId)->orderByDesc('run_date')->get();
        $testers = Tester::orderBy('name')->get();
        $warnings = $this->warnings->getWarnings($projectId);

        return view('modules.testing', compact('project', 'testCases', 'testRuns', 'testers', 'warnings'));
    }

    public function tokens(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $wallet = TokenWallet::where('project_id', $projectId)->first();
        $requests = TokenRequest::where('project_id', $projectId)->orderByDesc('id')->get();

        return view('modules.tokens', compact('project', 'wallet', 'requests'));
    }

    public function validationReports(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $reports = ValidationReport::where('project_id', $projectId)->orderByDesc('generated_at')->get();
        $warnings = $this->warnings->getWarnings($projectId);

        return view('modules.validation_reports', compact('project', 'reports', 'warnings'));
    }
}
