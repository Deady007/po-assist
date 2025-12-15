<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestResultBulkRequest;
use App\Http\Requests\TestRunStoreRequest;
use App\Models\Bug;
use App\Models\Requirement;
use App\Models\TestCase;
use App\Models\TestResult;
use App\Models\TestRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TestRunsController extends ApiController
{
    public function index(int $projectId): JsonResponse
    {
        $runs = TestRun::with('tester')
            ->where('project_id', $projectId)
            ->orderByDesc('run_date')
            ->get();

        return $this->success(['items' => $runs->toArray()]);
    }

    public function store(TestRunStoreRequest $request, int $projectId): JsonResponse
    {
        $data = $request->validated();
        $data['project_id'] = $projectId;
        $run = TestRun::create($data);

        return $this->success(['item' => $run->toArray()]);
    }

    public function show(int $projectId, int $runId): JsonResponse
    {
        $run = TestRun::with(['tester', 'results.testCase'])
            ->where('project_id', $projectId)
            ->find($runId);

        if (!$run) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Test run not found']], 404);
        }

        return $this->success(['item' => $run->toArray()]);
    }

    public function storeResults(TestResultBulkRequest $request, int $projectId, int $runId): JsonResponse
    {
        $run = TestRun::where('project_id', $projectId)->find($runId);
        if (!$run) {
            return $this->failure([['code' => 'NOT_FOUND', 'message' => 'Test run not found']], 404);
        }

        $resultsPayload = $request->validated()['results'];

        $saved = [];

        DB::transaction(function () use (&$saved, $resultsPayload, $run, $projectId) {
            foreach ($resultsPayload as $row) {
                $testCase = TestCase::where('project_id', $projectId)->find($row['test_case_id']);
                if (!$testCase) {
                    throw new \RuntimeException("Test case {$row['test_case_id']} not found in project.");
                }

                $result = TestResult::updateOrCreate(
                    [
                        'test_run_id' => $run->id,
                        'test_case_id' => $row['test_case_id'],
                    ],
                    [
                        'status' => $row['status'],
                        'remarks' => $row['remarks'] ?? null,
                    ]
                );

                // Optional bug creation on fail.
                if ($row['status'] === 'FAIL' && !empty($row['create_bug']) && $testCase->requirement_id) {
                    $bug = Bug::create([
                        'project_id' => $projectId,
                        'requirement_id' => $testCase->requirement_id,
                        'title' => 'Test failure: ' . $testCase->title,
                        'severity' => $row['bug_severity'] ?? 'MEDIUM',
                        'status' => 'OPEN',
                        'opened_at' => now(),
                    ]);
                    $result->update(['defect_bug_id' => $bug->id]);
                }

                $saved[] = $result->toArray();
            }
        });

        return $this->success(['items' => $saved]);
    }

    public function coverage(int $projectId): JsonResponse
    {
        $totalReq = Requirement::where('project_id', $projectId)->count();
        $withPass = Requirement::where('project_id', $projectId)
            ->whereHas('testCases.results', function ($q) {
                $q->where('status', 'PASS');
            })
            ->count();

        $coverage = $totalReq ? round(($withPass / $totalReq) * 100, 2) : 0;

        return $this->success([
            'total_requirements' => $totalReq,
            'with_pass' => $withPass,
            'coverage_percent' => $coverage,
        ]);
    }
}
