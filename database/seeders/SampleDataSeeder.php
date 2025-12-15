<?php

namespace Database\Seeders;

use App\Models\Bug;
use App\Models\DataItem;
use App\Models\Developer;
use App\Models\MasterDataChange;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\RequirementAssignment;
use App\Models\RequirementVersion;
use App\Models\TestCase;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\Tester;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $project = Project::first();

        if (!$project) {
            $this->call(WorkflowSeeder::class);
            $project = Project::first();
        }

        $requirements = [
            [
                'req_code' => 'REQ-0001',
                'title' => 'User authentication setup',
                'description' => 'Implement basic login/logout flows for internal users.',
                'source_type' => 'RFP',
                'priority' => 'HIGH',
                'status' => 'APPROVED',
                'is_change_request' => false,
                'approved_at' => Carbon::now()->subDays(10),
            ],
            [
                'req_code' => 'REQ-0002',
                'title' => 'Data import pipeline',
                'description' => 'Ingest CSV exports from client ERP into staging tables.',
                'source_type' => 'MEETING',
                'priority' => 'CRITICAL',
                'status' => 'IN_DEV',
                'is_change_request' => false,
                'approved_at' => Carbon::now()->subDays(7),
            ],
            [
                'req_code' => 'REQ-0003',
                'title' => 'Reporting dashboard',
                'description' => 'Provide KPI dashboard with filters and exports.',
                'source_type' => 'RFP',
                'priority' => 'HIGH',
                'status' => 'IN_TEST',
                'is_change_request' => false,
                'approved_at' => Carbon::now()->subDays(5),
            ],
            [
                'req_code' => 'REQ-0004',
                'title' => 'Audit trail logging',
                'description' => 'Track all configuration changes with before/after snapshots.',
                'source_type' => 'CHANGE_REQUEST',
                'priority' => 'MEDIUM',
                'status' => 'PROPOSED',
                'is_change_request' => true,
            ],
            [
                'req_code' => 'REQ-0005',
                'title' => 'Master data rename',
                'description' => 'Rename legacy fields to new taxonomy per client feedback.',
                'source_type' => 'EMAIL',
                'priority' => 'MEDIUM',
                'status' => 'APPROVED',
                'is_change_request' => true,
                'approved_at' => Carbon::now()->subDays(3),
            ],
        ];

        $requirementModels = [];

        foreach ($requirements as $data) {
            $requirement = Requirement::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'req_code' => $data['req_code'],
                ],
                $data
            );

            RequirementVersion::firstOrCreate(
                [
                    'requirement_id' => $requirement->id,
                    'version_no' => 1,
                ],
                [
                    'payload_json' => [
                        'title' => $requirement->title,
                        'description' => $requirement->description,
                        'priority' => $requirement->priority,
                        'status' => $requirement->status,
                        'source_type' => $requirement->source_type,
                    ],
                ]
            );

            $requirementModels[$requirement->req_code] = $requirement;
        }

        $dataItems = [
            [
                'name' => 'ERP Master CSV',
                'category' => 'MASTER_DATA',
                'expected_format' => 'CSV',
                'owner' => 'CLIENT',
                'due_date' => Carbon::now()->addDays(3),
                'status' => 'PENDING',
            ],
            [
                'name' => 'Reporting mockups',
                'category' => 'REPORT',
                'expected_format' => 'PDF',
                'owner' => 'CLIENT',
                'due_date' => Carbon::now()->addDays(5),
                'status' => 'RECEIVED',
                'received_at' => Carbon::now()->subDay(),
            ],
            [
                'name' => 'Staging DB credentials',
                'category' => 'CREDENTIALS',
                'expected_format' => 'text',
                'owner' => 'INTERNAL',
                'status' => 'VALIDATED',
                'received_at' => Carbon::now()->subDays(2),
            ],
        ];

        foreach ($dataItems as $item) {
            DataItem::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'name' => $item['name'],
                ],
                $item + ['project_id' => $project->id]
            );
        }

        $changes = [
            [
                'object_name' => 'customers',
                'field_name' => 'customer_category',
                'change_type' => 'ADD_FIELD',
                'description' => 'Add new customer category field with lookup.',
                'requirement' => 'REQ-0005',
            ],
            [
                'object_name' => 'orders',
                'field_name' => 'order_status',
                'change_type' => 'RULE_CHANGE',
                'description' => 'Adjust status rules to include Delivered/Returned states.',
                'requirement' => 'REQ-0002',
            ],
        ];

        foreach ($changes as $change) {
            MasterDataChange::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'object_name' => $change['object_name'],
                    'field_name' => $change['field_name'],
                ],
                [
                    'requirement_id' => $requirementModels[$change['requirement']]->id ?? null,
                    'change_type' => $change['change_type'],
                    'description' => $change['description'],
                ]
            );
        }

        $developer = Developer::first();
        $secondaryDeveloper = Developer::skip(1)->first();

        if ($developer && isset($requirementModels['REQ-0002'])) {
            RequirementAssignment::firstOrCreate(
                [
                    'requirement_id' => $requirementModels['REQ-0002']->id,
                    'developer_id' => $developer->id,
                ],
                [
                    'assigned_at' => Carbon::now()->subDays(4),
                    'status' => 'IN_PROGRESS',
                    'eta_date' => Carbon::now()->addDays(2),
                ]
            );
        }

        if ($secondaryDeveloper && isset($requirementModels['REQ-0003'])) {
            RequirementAssignment::firstOrCreate(
                [
                    'requirement_id' => $requirementModels['REQ-0003']->id,
                    'developer_id' => $secondaryDeveloper->id,
                ],
                [
                    'assigned_at' => Carbon::now()->subDays(2),
                    'status' => 'ASSIGNED',
                ]
            );
        }

        $bugs = [
            [
                'title' => 'Report export fails for large date range',
                'description' => 'Timeout when exporting CSV larger than 10k rows.',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'requirement' => 'REQ-0003',
                'assigned_to' => $developer,
            ],
            [
                'title' => 'Incorrect mapping for customer category',
                'description' => 'New category not persisted in staging table.',
                'severity' => 'MEDIUM',
                'status' => 'IN_PROGRESS',
                'requirement' => 'REQ-0005',
                'assigned_to' => $secondaryDeveloper,
            ],
        ];

        $bugModels = [];

        foreach ($bugs as $bug) {
            $bugModels[] = Bug::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'title' => $bug['title'],
                ],
                [
                    'requirement_id' => $requirementModels[$bug['requirement']]->id ?? null,
                    'description' => $bug['description'],
                    'severity' => $bug['severity'],
                    'status' => $bug['status'],
                    'opened_at' => Carbon::now()->subDay(),
                    'assigned_to_developer_id' => $bug['assigned_to']->id ?? null,
                ]
            );
        }

        $tester = Tester::first();

        $testCases = [
            [
                'title' => 'Validate login flow',
                'requirement' => 'REQ-0001',
                'steps' => "1. Open login page\n2. Enter valid credentials\n3. Submit form",
                'expected_result' => 'User is redirected to dashboard with session created.',
                'created_from' => 'MANUAL',
            ],
            [
                'title' => 'Generate dashboard export',
                'requirement' => 'REQ-0003',
                'steps' => "1. Navigate to reports\n2. Set date range 30 days\n3. Export CSV",
                'expected_result' => 'CSV download succeeds within 10 seconds.',
                'created_from' => 'MANUAL',
            ],
        ];

        $testCaseModels = [];

        foreach ($testCases as $case) {
            $testCaseModels[] = TestCase::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'title' => $case['title'],
                ],
                [
                    'requirement_id' => $requirementModels[$case['requirement']]->id ?? null,
                    'steps' => $case['steps'],
                    'expected_result' => $case['expected_result'],
                    'created_from' => $case['created_from'],
                ]
            );
        }

        if ($tester && $testCaseModels) {
            $testRun = TestRun::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'run_date' => Carbon::now()->toDateString(),
                ],
                [
                    'tester_id' => $tester->id,
                    'notes' => 'Initial regression suite',
                ]
            );

            foreach ($testCaseModels as $index => $testCase) {
                $status = $index === 0 ? 'PASS' : 'FAIL';

                TestResult::firstOrCreate(
                    [
                        'test_run_id' => $testRun->id,
                        'test_case_id' => $testCase->id,
                    ],
                    [
                        'status' => $status,
                        'remarks' => $status === 'PASS' ? 'Working as expected' : 'Export timed out',
                        'defect_bug_id' => $status === 'FAIL' && isset($bugModels[0]) ? $bugModels[0]->id : null,
                    ]
                );
            }
        }
    }
}
