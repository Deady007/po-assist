<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\DataItem;
use App\Models\DriveFolder;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Requirement;
use App\Models\RequirementAssignment;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TokenRequest;
use Carbon\Carbon;

class WorkflowWarningService
{
    public const PHASE_ORDER = [
        'REQUIREMENTS' => 1,
        'DATA_COLLECTION' => 2,
        'MASTER_DATA_SETUP' => 3,
        'DEVELOPMENT' => 4,
        'TESTING' => 5,
        'DELIVERY' => 6,
    ];

    public function __construct(private ProjectDriveProvisioner $provisioner)
    {
    }

    public function getWarnings(int $projectId): array
    {
        $project = Project::with('phases')->findOrFail($projectId);
        $warnings = [];
        $today = Carbon::today();
        $currentPhase = $project->currentPhase();
        $currentKey = $currentPhase?->phase_key;

        // Phase overdue check.
        foreach ($project->phases as $phase) {
            if ($phase->planned_end_date && $phase->planned_end_date->lt($today) && $phase->status !== 'COMPLETED') {
                $warnings[] = $this->warn('PHASE_OVERDUE', "Phase {$phase->phase_key} is overdue (planned end {$phase->planned_end_date->toDateString()}).", 'HIGH');
            }
        }

        // Requirements: missing in requirements phase.
        if ($currentKey === 'REQUIREMENTS') {
            $reqCount = Requirement::where('project_id', $projectId)->count();
            if ($reqCount === 0) {
                $warnings[] = $this->warn('REQS_MISSING', 'No requirements captured yet while in REQUIREMENTS phase.', 'MEDIUM');
            }
        }

        // Requirements: approved without assignment in development.
        if ($this->isPhaseAtLeast($currentKey, 'DEVELOPMENT')) {
            $unassignedApproved = Requirement::where('project_id', $projectId)
                ->where('status', 'APPROVED')
                ->whereDoesntHave('assignments')
                ->count();
            if ($unassignedApproved > 0) {
                $warnings[] = $this->warn('REQS_UNASSIGNED', "{$unassignedApproved} approved requirement(s) lack developer assignment.", 'HIGH');
            }
        }

        // Change requests after requirements.
        if ($this->isPhaseBeyond($currentKey, 'REQUIREMENTS')) {
            $crCount = Requirement::where('project_id', $projectId)->where('is_change_request', true)->count();
            if ($crCount > 3) {
                $warnings[] = $this->warn('CR_EXCESS', "{$crCount} change requests recorded after REQUIREMENTS phase.", 'MEDIUM');
            }
        }

        // Data collection overdue items.
        if ($this->isPhaseAtLeast($currentKey, 'DATA_COLLECTION')) {
            $pastDue = DataItem::where('project_id', $projectId)
                ->where('status', 'PENDING')
                ->whereDate('due_date', '<', $today)
                ->count();
            if ($pastDue > 0) {
                $warnings[] = $this->warn('DATA_PAST_DUE', "{$pastDue} data item(s) pending past due date.", 'HIGH');
            }
        }

        // Bugs blocking testing/delivery.
        if ($this->isPhaseAtLeast($currentKey, 'TESTING')) {
            $blockingBugs = Bug::where('project_id', $projectId)
                ->whereIn('severity', ['CRITICAL', 'HIGH'])
                ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                ->count();
            if ($blockingBugs > 0) {
                $warnings[] = $this->warn('BUGS_BLOCKING', "{$blockingBugs} critical/high bugs remain open.", 'HIGH');
            }
        }

        // No test runs when in testing.
        if ($currentKey === 'TESTING') {
            $runCount = TestRun::where('project_id', $projectId)->count();
            if ($runCount === 0) {
                $warnings[] = $this->warn('NO_TEST_RUNS', 'No test runs executed in TESTING phase.', 'MEDIUM');
            }
        }

        // Fail/blocked results when delivering.
        if ($this->isPhaseAtLeast($currentKey, 'DELIVERY')) {
            $failedResults = TestResult::whereHas('testRun', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            })->whereIn('status', ['FAIL', 'BLOCKED'])->count();
            if ($failedResults > 0) {
                $warnings[] = $this->warn('TESTS_FAILING', "{$failedResults} test results are FAIL/BLOCKED while delivering.", 'HIGH');
            }
        }

        // Delivery but open token requests.
        $hasDelivery = $project->deliveries()->exists();
        if ($hasDelivery) {
            $openTokens = TokenRequest::where('project_id', $projectId)
                ->whereNotIn('status', ['DONE', 'REJECTED'])
                ->count();
            if ($openTokens > 0) {
                $warnings[] = $this->warn('TOKENS_OPEN', "{$openTokens} token request(s) still open after delivery.", 'MEDIUM');
            }
        }

        // Missing drive folders.
        $expectedPhases = array_merge(
            array_keys(ProjectDriveProvisioner::PHASE_FOLDER_MAP),
            [ProjectDriveProvisioner::ROOT_PHASE_KEY]
        );
        $existing = DriveFolder::where('project_id', $projectId)
            ->whereIn('phase_key', $expectedPhases)
            ->count();
        if ($existing < count($expectedPhases)) {
            $warnings[] = $this->warn('DRIVE_NOT_PROVISIONED', 'Drive folders not fully provisioned for this project.', 'MEDIUM');
        }

        return $warnings;
    }

    private function isPhaseAtLeast(?string $current, string $check): bool
    {
        if (!$current) {
            return false;
        }
        return ($this->phaseIndex($current) >= $this->phaseIndex($check));
    }

    private function isPhaseBeyond(?string $current, string $check): bool
    {
        if (!$current) {
            return false;
        }
        return ($this->phaseIndex($current) > $this->phaseIndex($check));
    }

    private function phaseIndex(string $phaseKey): int
    {
        return self::PHASE_ORDER[$phaseKey] ?? 0;
    }

    private function warn(string $code, string $message, string $severity = 'MEDIUM'): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'severity' => $severity,
        ];
    }
}
