<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\DataItem;
use App\Models\Delivery;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TokenRequest;
use App\Models\TokenWallet;
use App\Models\ValidationReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Services\AiOrchestratorService;

class ValidationReportService
{
    public function __construct(
        private AiOrchestratorService $ai,
        private WorkflowWarningService $warnings
    ) {
    }

    public function generateReport(int $projectId, bool $includeAiSummary = false): ValidationReport
    {
        $project = Project::with('phases')->findOrFail($projectId);

        $phaseSummary = $project->phases->map(function ($p) {
            return [
                'phase_key' => $p->phase_key,
                'status' => $p->status,
                'planned_start_date' => optional($p->planned_start_date)->toDateString(),
                'planned_end_date' => optional($p->planned_end_date)->toDateString(),
                'actual_start_date' => optional($p->actual_start_date)->toDateString(),
                'actual_end_date' => optional($p->actual_end_date)->toDateString(),
            ];
        })->values()->all();

        $requirements = Requirement::where('project_id', $projectId)->get();
        $reqTotalsByStatus = $requirements->groupBy('status')->map->count()->toArray();
        $reqTotalsByPriority = $requirements->groupBy('priority')->map->count()->toArray();
        $changeRequests = $requirements->where('is_change_request', true)->count();
        $deliveredReqs = $requirements->filter(fn ($r) => !empty($r->delivered_at))->map(function ($r) {
            return [
                'req_code' => $r->req_code,
                'title' => $r->title,
                'delivered_at' => optional($r->delivered_at)->toDateString(),
            ];
        })->values()->all();

        $dataItems = DataItem::where('project_id', $projectId)->get();
        $dataTotalsByStatus = $dataItems->groupBy('status')->map->count()->toArray();
        $dataPastDue = $dataItems->filter(function ($item) {
            return $item->status === 'PENDING' && $item->due_date && $item->due_date->isPast();
        })->map(function ($item) {
            return [
                'name' => $item->name,
                'due_date' => optional($item->due_date)->toDateString(),
                'owner' => $item->owner,
            ];
        })->values()->all();

        $masterChanges = DB::table('master_data_changes')
            ->where('project_id', $projectId)
            ->get();
        $masterCounts = $masterChanges->groupBy('change_type')->map->count()->toArray();
        $masterList = $masterChanges->map(function ($m) {
            return [
                'object_name' => $m->object_name,
                'field_name' => $m->field_name,
                'change_type' => $m->change_type,
                'implemented_at' => $m->implemented_at,
                'version_tag' => $m->version_tag,
            ];
        })->values()->all();

        $bugs = Bug::where('project_id', $projectId)->get();
        $bugsByStatus = $bugs->groupBy('status')->map->count()->toArray();
        $bugsBySeverity = $bugs->groupBy('severity')->map->count()->toArray();
        $blockingBugs = $bugs->filter(function ($b) {
            return in_array($b->severity, ['CRITICAL', 'HIGH']) && in_array($b->status, ['OPEN', 'IN_PROGRESS']);
        })->map(function ($b) {
            return [
                'title' => $b->title,
                'severity' => $b->severity,
                'status' => $b->status,
                'id' => $b->id,
            ];
        })->values()->all();

        $testRunCount = TestRun::where('project_id', $projectId)->count();
        $testResults = TestResult::whereHas('testRun', fn ($q) => $q->where('project_id', $projectId))->get();
        $testCounts = $testResults->groupBy('status')->map->count()->toArray();

        $coverage = $this->computeCoverage($projectId, $requirements->count());

        $delivery = Delivery::where('project_id', $projectId)->latest('delivery_date')->first();
        $wallet = TokenWallet::where('project_id', $projectId)->first();
        $openTokenRequests = TokenRequest::where('project_id', $projectId)
            ->whereNotIn('status', ['DONE', 'REJECTED'])
            ->get();

        $warnings = $this->warnings->getWarnings($projectId);

        $report = [
            'generated_at' => now()->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
            ],
            'phases' => $phaseSummary,
            'requirements' => [
                'totals_by_status' => $reqTotalsByStatus,
                'totals_by_priority' => $reqTotalsByPriority,
                'change_requests' => $changeRequests,
                'delivered' => $deliveredReqs,
            ],
            'data_collection' => [
                'totals_by_status' => $dataTotalsByStatus,
                'pending_past_due' => $dataPastDue,
            ],
            'master_data' => [
                'counts_by_change_type' => $masterCounts,
                'items' => $masterList,
            ],
            'bugs' => [
                'counts_by_status' => $bugsByStatus,
                'counts_by_severity' => $bugsBySeverity,
                'open_critical_high' => $blockingBugs,
            ],
            'testing' => [
                'test_run_count' => $testRunCount,
                'result_counts' => $testCounts,
                'coverage_percent' => $coverage,
            ],
            'delivery' => [
                'latest_delivery_date' => $delivery?->delivery_date?->toDateString(),
                'signoff_notes' => $delivery?->signoff_notes,
            ],
            'tokens' => [
                'wallet' => $wallet ? [
                    'total_tokens' => $wallet->total_tokens,
                    'used_tokens' => $wallet->used_tokens,
                ] : null,
                'open_requests' => $openTokenRequests->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'title' => $r->title,
                        'status' => $r->status,
                        'tokens_estimated' => $r->tokens_estimated,
                    ];
                })->values()->all(),
            ],
            'warnings' => $warnings,
        ];

        if ($includeAiSummary) {
            try {
                $report['executive_summary'] = $this->buildSummary($report);
            } catch (\Throwable $e) {
                $report['executive_summary'] = [
                    'summary' => 'Summary unavailable: ' . $e->getMessage(),
                    'risks' => [],
                    'next_steps' => [],
                ];
            }
        }

        $html = View::make('validation_reports.report', ['report' => $report])->render();

        $record = ValidationReport::create([
            'project_id' => $projectId,
            'generated_at' => now(),
            'report_json' => $report,
            'report_html' => $html,
        ]);

        return $record;
    }

    private function computeCoverage(int $projectId, int $requirementCount): float
    {
        if ($requirementCount === 0) {
            return 0.0;
        }

        $withPass = Requirement::where('project_id', $projectId)
            ->whereHas('testCases.results', function ($q) {
                $q->where('status', 'PASS');
            })
            ->count();

        return round(($withPass / $requirementCount) * 100, 2);
    }

    private function buildSummary(array $report): array
    {
        $out = $this->ai->run('VALIDATION_EXEC_SUMMARY', ['report' => $report]);

        return [
            'summary' => trim((string) ($out['summary'] ?? '')),
            'risks' => is_array($out['risks'] ?? null) ? $out['risks'] : [],
            'next_steps' => is_array($out['next_steps'] ?? null) ? $out['next_steps'] : [],
        ];
    }
}
