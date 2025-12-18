<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectTeam;
use App\Models\Requirement;
use App\Models\TestResult;
use App\Models\Delivery;
use App\Models\ValidationReport;
use App\Models\User;
use App\Modules\ProjectManagement\Http\Requests\ProjectStoreRequest;
use App\Modules\ProjectManagement\Http\Requests\ProjectUpdateRequest;
use App\Services\SequenceService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private SequenceService $sequences, private WorkflowService $workflow)
    {
    }

    public function create(): View
    {
        $customers = $this->customerSelectOptions();
        $statuses = ProjectStatus::orderBy('order_no')->get();
        $users = User::orderBy('name')->get();

        return view('admin.projects.create', compact('customers', 'statuses', 'users'));
    }

    public function index(): View
    {
        $query = Project::with(['client', 'status', 'owner']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        foreach (['client_id', 'status_id', 'owner_user_id'] as $field) {
            if (request()->filled($field)) {
                $query->where($field, request($field));
            }
        }

        if (request()->filled('is_active')) {
            $query->where('is_active', request()->boolean('is_active'));
        }

        if ($from = request('due_from')) {
            $query->whereDate('due_date', '>=', $from);
        }
        if ($to = request('due_to')) {
            $query->whereDate('due_date', '<=', $to);
        }

        if ($priority = request('priority')) {
            $query->where('priority', strtolower($priority));
        }

        $projects = $query->orderBy('due_date')->paginate(15)->withQueryString();
        $clients = $this->clientOptions();
        $statuses = ProjectStatus::orderBy('order_no')->get();
        $users = User::orderBy('name')->get();

        return view('admin.projects.index', compact('projects', 'clients', 'statuses', 'users'));
    }

    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $actor = auth()->id();
        $defaultStatus = $data['status_id'] ?? optional(ProjectStatus::where('is_default', true)->first())->id;
        $client = Client::find($data['client_id']);

        DB::transaction(function () use ($data, $actor, $client, $defaultStatus) {
            $project = Project::create([
                'project_code' => $data['project_code'] ?? $this->sequences->next('project'),
                'client_id' => $data['client_id'],
                'client_name' => $client?->name,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status_id' => $defaultStatus,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'priority' => $data['priority'],
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]);

            $this->syncTeam($project, $data['team_members'] ?? [], $actor);
        });

        return redirect()->route('admin.projects.index')->with('status', 'Project created');
    }

    public function edit(int $project): View
    {
        $model = Project::findOrFail($project);
        $customers = $this->customerSelectOptions();
        $statuses = ProjectStatus::orderBy('order_no')->get();
        $users = User::orderBy('name')->get();

        return view('admin.projects.edit', [
            'project' => $model,
            'customers' => $customers,
            'statuses' => $statuses,
            'users' => $users,
            'teamMembers' => $model->team()->with('user')->get(),
        ]);
    }

    public function update(ProjectUpdateRequest $request, int $project): RedirectResponse
    {
        $model = Project::findOrFail($project);
        $data = $request->validated();
        $actor = auth()->id();
        $client = Client::find($data['client_id']);

        DB::transaction(function () use ($model, $data, $actor, $client) {
            $model->update([
                'project_code' => $data['project_code'] ?? $model->project_code ?? $this->sequences->next('project'),
                'client_id' => $data['client_id'],
                'client_name' => $client?->name,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status_id' => $data['status_id'] ?? $model->status_id,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'priority' => $data['priority'],
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'is_active' => $data['is_active'] ?? $model->is_active,
                'updated_by' => $actor,
            ]);

            if (array_key_exists('team_members', $data)) {
                $this->syncTeam($model, $data['team_members'] ?? [], $actor);
            }
        });

        return redirect()->route('admin.projects.show', $project)->with('status', 'Project updated');
    }

    public function workflow(int $project): RedirectResponse
    {
        return redirect()->route('admin.projects.developer_assign', $project);
    }

    public function developerAssign(int $project): View
    {
        $projectModel = Project::with('client')->findOrFail($project);
        $modules = $this->workflow->listModules($projectModel->id);
        $users = User::orderBy('name')->get();
        $role = auth()->user()?->role?->name ?? '';

        $phaseOrder = ['REQUIREMENTS', 'DATA_COLLECTION', 'MASTER_DATA', 'DEVELOPMENT', 'TESTING', 'DELIVERY'];
        $modulesByPhase = $modules->filter(fn ($m) => $m->phase)->keyBy('phase');
        $requirementsCount = Requirement::where('project_id', $projectModel->id)->count();
        $failingTests = TestResult::whereHas('testRun', function ($q) use ($projectModel) {
            $q->where('project_id', $projectModel->id);
        })->whereIn('status', ['FAIL', 'BLOCKED'])->count();

        $phaseCards = [];
        $previousDone = true;
        foreach ($phaseOrder as $index => $phaseKey) {
            $module = $modulesByPhase->get($phaseKey) ?? $modules->firstWhere('order_no', $index + 1);
            $status = $module?->status ?? 'NOT_STARTED';
            $totalTasks = $module?->total_tasks ?? 0;
            $doneTasks = $module?->done_tasks ?? 0;
            $blockedTasks = $module?->blocked_tasks ?? 0;
            $progress = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

            $phaseCards[] = [
                'key' => $phaseKey,
                'label' => str_replace('_', ' ', $phaseKey),
                'module_id' => $module?->id,
                'status' => $status,
                'owner' => $module?->owner?->name,
                'tasks_total' => $totalTasks,
                'tasks_done' => $doneTasks,
                'tasks_blocked' => $blockedTasks,
                'progress' => $progress,
                'blocked_reason' => $module?->blocker_reason,
                'order' => $module?->order_no ?? ($index + 1),
                'can_open' => $previousDone,
            ];

            $previousDone = $status === 'DONE';
        }

        $hasBlocked = collect($phaseCards)->contains(fn ($p) => $p['status'] === 'BLOCKED');
        $hasOverdue = $modules->sum('overdue_tasks') > 0;
        $health = $hasBlocked ? 'red' : ($hasOverdue ? 'amber' : 'green');

        $deliveryDone = Delivery::where('project_id', $projectModel->id)->exists();
        $validationExists = ValidationReport::where('project_id', $projectModel->id)->exists();
        $canDownloadReport = $deliveryDone || $validationExists;

        $projectBadge = 'ACTIVE';
        if (collect($phaseCards)->every(fn ($p) => $p['status'] === 'NOT_STARTED')) {
            $projectBadge = 'NOT_STARTED';
        } elseif (collect($phaseCards)->every(fn ($p) => $p['status'] === 'DONE')) {
            $projectBadge = 'COMPLETED';
        } elseif ($hasBlocked) {
            $projectBadge = 'BLOCKED';
        }

        return view('admin.projects.developer_assign', [
            'project' => $projectModel,
            'modules' => $modules,
            'users' => $users,
            'role' => $role,
            'phaseCards' => $phaseCards,
            'requirementsCount' => $requirementsCount,
            'failingTests' => $failingTests,
            'health' => $health,
            'projectBadge' => $projectBadge,
            'canDownloadReport' => $canDownloadReport,
            'moduleStatuses' => ['NOT_STARTED', 'IN_PROGRESS', 'BLOCKED', 'DONE'],
            'taskStatuses' => ['TODO', 'IN_PROGRESS', 'BLOCKED', 'DONE'],
        ]);
    }

    public function requirementsManagement(int $project): View
    {
        $projectModel = Project::with('client')->findOrFail($project);

        $requirements = [
            [
                'module' => 'Authentication',
                'name' => 'Login (email + OTP)',
                'received_on' => now()->subDays(9)->toDateString(),
                'reference' => 'Client email thread',
                'responsible' => $projectModel->client?->name ? "{$projectModel->client->name} (Client)" : 'Client (TBD)',
                'priority' => 'HIGH',
                'status' => 'NEW',
            ],
            [
                'module' => 'Dashboard',
                'name' => 'Project overview widgets',
                'received_on' => now()->subDays(8)->toDateString(),
                'reference' => 'Kick-off notes',
                'responsible' => 'PM (You)',
                'priority' => 'MEDIUM',
                'status' => 'APPROVED',
            ],
            [
                'module' => 'Developer Assign',
                'name' => 'Assign tasks to developers by module/page',
                'received_on' => now()->subDays(7)->toDateString(),
                'reference' => 'WhatsApp message',
                'responsible' => 'PM (You)',
                'priority' => 'HIGH',
                'status' => 'IN_REVIEW',
            ],
            [
                'module' => 'Testing',
                'name' => 'Create test tasks from page/module description (AI later)',
                'received_on' => now()->subDays(6)->toDateString(),
                'reference' => 'Client call summary',
                'responsible' => 'QA Lead (TBD)',
                'priority' => 'MEDIUM',
                'status' => 'DRAFT',
            ],
            [
                'module' => 'Delivery',
                'name' => 'Go-live checklist + deployment handover',
                'received_on' => now()->subDays(5)->toDateString(),
                'reference' => 'Email: go-live expectations',
                'responsible' => 'PM (You)',
                'priority' => 'HIGH',
                'status' => 'DRAFT',
            ],
        ];

        $requirementsByModule = collect($requirements)->groupBy('module')->toArray();

        $srs = [
            'status' => 'Not generated yet',
            'version' => 'v0.0',
            'last_generated_at' => '—',
        ];

        return view('admin.projects.requirements_management', [
            'project' => $projectModel,
            'requirementsByModule' => $requirementsByModule,
            'srs' => $srs,
        ]);
    }

    public function kickoffCall(int $project): View
    {
        $projectModel = Project::with('client')->findOrFail($project);

        $kickoff = [
            'scheduled_at' => now()->addDays(1)->format('Y-m-d 11:00'),
            'client' => $projectModel->client?->name ?? 'Client (TBD)',
            'responsible' => 'PM (You)',
            'agenda' => [
                'Confirm scope & priorities',
                'Approve requirements list',
                'Define timeline & milestones',
                'Finalize deliverables & communication',
            ],
        ];

        $timeline = [
            ['phase' => 'Requirements freeze', 'from' => now()->addDays(1)->toDateString(), 'to' => now()->addDays(3)->toDateString(), 'owner' => 'PM'],
            ['phase' => 'Development', 'from' => now()->addDays(4)->toDateString(), 'to' => now()->addDays(18)->toDateString(), 'owner' => 'Dev Lead'],
            ['phase' => 'Testing', 'from' => now()->addDays(19)->toDateString(), 'to' => now()->addDays(25)->toDateString(), 'owner' => 'QA Lead'],
            ['phase' => 'Review + Fixes', 'from' => now()->addDays(26)->toDateString(), 'to' => now()->addDays(30)->toDateString(), 'owner' => 'PM'],
            ['phase' => 'Go-live', 'from' => now()->addDays(31)->toDateString(), 'to' => now()->addDays(31)->toDateString(), 'owner' => 'PM'],
        ];

        $approvedScope = [
            ['module' => 'Authentication', 'items' => ['Login (email + OTP)', 'Role-based access']],
            ['module' => 'Dashboard', 'items' => ['Project health', 'Overdue tasks summary']],
            ['module' => 'Developer Assign', 'items' => ['Modules + tasks assignment', 'Due dates + blockers']],
        ];

        $deliverables = [
            ['name' => 'SRS Document (AI later)', 'status' => 'Planned'],
            ['name' => 'RFP / Kick-off Summary Doc', 'status' => 'Draft'],
            ['name' => 'UAT checklist + sign-off', 'status' => 'Planned'],
        ];

        return view('admin.projects.kickoff_call', [
            'project' => $projectModel,
            'kickoff' => $kickoff,
            'timeline' => $timeline,
            'approvedScope' => $approvedScope,
            'deliverables' => $deliverables,
        ]);
    }

    public function dataManagement(int $project): View
    {
        $projectModel = Project::with('client')->findOrFail($project);

        $folders = [
            ['name' => '00 Admin', 'path' => '/00-Admin', 'status' => 'Planned', 'last_activity' => '—'],
            ['name' => '01 Requirements', 'path' => '/01-Requirements', 'status' => 'Planned', 'last_activity' => '—'],
            ['name' => '02 Designs', 'path' => '/02-Designs', 'status' => 'Planned', 'last_activity' => '—'],
            ['name' => '03 Data Collection', 'path' => '/03-Data-Collection', 'status' => 'Planned', 'last_activity' => '—'],
            ['name' => '04 Testing', 'path' => '/04-Testing', 'status' => 'Planned', 'last_activity' => '—'],
            ['name' => '05 Delivery', 'path' => '/05-Delivery', 'status' => 'Planned', 'last_activity' => '—'],
        ];

        $intakeLog = [
            [
                'received_on' => now()->subDays(3)->toDateString(),
                'item' => 'Brand assets (logo + colors)',
                'type' => 'ZIP',
                'from' => $projectModel->client?->name ?? 'Client',
                'status' => 'RECEIVED',
                'notes' => 'Awaiting final logo variant',
            ],
            [
                'received_on' => now()->subDays(2)->toDateString(),
                'item' => 'Sample data sheet',
                'type' => 'XLSX',
                'from' => $projectModel->client?->name ?? 'Client',
                'status' => 'RECEIVED',
                'notes' => 'Needs column mapping confirmation',
            ],
            [
                'received_on' => now()->subDay()->toDateString(),
                'item' => 'Access credentials / staging URL',
                'type' => 'Text',
                'from' => 'Client IT',
                'status' => 'PENDING',
                'notes' => 'Waiting for whitelist',
            ],
        ];

        return view('admin.projects.data_management', [
            'project' => $projectModel,
            'folders' => $folders,
            'intakeLog' => $intakeLog,
        ]);
    }

    public function testingAssign(int $project): View
    {
        $projectModel = Project::with('client')->findOrFail($project);

        $testingBoard = [
            ['module' => 'Authentication', 'page' => 'Login', 'task' => 'OTP invalid/expired scenarios', 'assignee' => 'Tester A', 'status' => 'TODO', 'linked_dev_task' => 'DA-12'],
            ['module' => 'Dashboard', 'page' => 'Overview', 'task' => 'Verify widgets & permissions', 'assignee' => 'Tester B', 'status' => 'IN_PROGRESS', 'linked_dev_task' => 'DA-18'],
            ['module' => 'Developer Assign', 'page' => 'Module list', 'task' => 'Module status rules (DONE guard)', 'assignee' => 'Tester A', 'status' => 'TODO', 'linked_dev_task' => 'DA-21'],
            ['module' => 'Developer Assign', 'page' => 'Tasks', 'task' => 'Blocker reason required when BLOCKED', 'assignee' => 'Tester C', 'status' => 'BLOCKED', 'linked_dev_task' => 'DA-23'],
            ['module' => 'Delivery', 'page' => 'Validation report', 'task' => 'Export/download flow', 'assignee' => 'Tester B', 'status' => 'TODO', 'linked_dev_task' => 'DA-31'],
        ];

        $statusSummary = collect($testingBoard)->countBy('status')->all();

        return view('admin.projects.testing_assign', [
            'project' => $projectModel,
            'testingBoard' => $testingBoard,
            'statusSummary' => $statusSummary,
        ]);
    }

    public function review(int $project): View
    {
        $projectModel = Project::with('client')->findOrFail($project);

        $checklist = [
            ['item' => 'All development tasks completed', 'owner' => 'Dev Lead', 'status' => 'PENDING'],
            ['item' => 'All test cases executed', 'owner' => 'QA Lead', 'status' => 'PENDING'],
            ['item' => 'Critical bugs resolved', 'owner' => 'PM', 'status' => 'PENDING'],
            ['item' => 'User manual drafted', 'owner' => 'PM', 'status' => 'PLANNED'],
            ['item' => 'Client sign-off received', 'owner' => 'Client', 'status' => 'PLANNED'],
        ];

        $docs = [
            ['name' => 'User Manual', 'status' => 'Not generated', 'updated_at' => '—'],
            ['name' => 'Validation Report', 'status' => 'Not generated', 'updated_at' => '—'],
            ['name' => 'Release Notes', 'status' => 'Draft', 'updated_at' => now()->toDateString()],
        ];

        return view('admin.projects.review', [
            'project' => $projectModel,
            'checklist' => $checklist,
            'docs' => $docs,
        ]);
    }

    public function tasksView(int $project): View
    {
        $projectModel = Project::findOrFail($project);
        $modules = $projectModel->modules()->orderBy('order_no')->get();
        $users = User::orderBy('name')->get();
        $role = auth()->user()?->role?->name ?? '';

        $filters = [
            'module_id' => request('module_id'),
            'assignee_user_id' => request('assignee_user_id'),
            'status' => request('status'),
            'overdue_only' => request()->boolean('overdue_only'),
        ];

        $taskQuery = \App\Models\Task::with(['module', 'assignee'])
            ->where('project_id', $projectModel->id);

        if ($filters['module_id']) {
            $taskQuery->where('project_module_id', $filters['module_id']);
        }
        if ($filters['assignee_user_id']) {
            $taskQuery->where('assignee_user_id', $filters['assignee_user_id']);
        }
        if ($filters['status']) {
            $taskQuery->where('status', strtoupper($filters['status']));
        }
        if ($filters['overdue_only']) {
            $taskQuery->where('status', '!=', 'DONE')->whereDate('due_date', '<', now()->startOfDay());
        }

        $tasks = $taskQuery->orderBy('due_date')->paginate(20)->withQueryString();

        return view('admin.projects.tasks', [
            'project' => $projectModel,
            'modules' => $modules,
            'users' => $users,
            'tasks' => $tasks,
            'filters' => $filters,
            'role' => $role,
            'taskStatuses' => ['TODO', 'IN_PROGRESS', 'BLOCKED', 'DONE'],
        ]);
    }

    public function show(int $project): View
    {
        $model = Project::with([
            'client',
            'status',
            'owner',
            'team.user',
        ])->findOrFail($project);

        $clients = $this->clientOptions();
        $statuses = ProjectStatus::orderBy('order_no')->get();
        $users = User::orderBy('name')->get();

        $templates = EmailTemplate::where(function ($q) use ($model) {
            $q->where('scope_type', 'global')
                ->orWhere(function ($q2) use ($model) {
                    $q2->where('scope_type', 'client')->where('scope_id', $model->client_id);
                })
                ->orWhere(function ($q3) use ($model) {
                    $q3->where('scope_type', 'project')->where('scope_id', $model->id);
                });
        })->orderBy('code')->get();

        $emailLogs = EmailLog::with('template')
            ->where('project_id', $project)
            ->orderByDesc('generated_at')
            ->limit(25)
            ->get();

        $role = auth()->user()?->role?->name ?? '';

        return view('admin.projects.show', compact('model', 'clients', 'statuses', 'users', 'templates', 'emailLogs', 'role'));
    }

    public function emails(int $project): View
    {
        $projectModel = Project::findOrFail($project);

        $templates = EmailTemplate::where(function ($q) use ($projectModel) {
            $q->where('scope_type', 'global')
                ->orWhere(function ($q2) use ($projectModel) {
                    $q2->where('scope_type', 'client')->where('scope_id', $projectModel->client_id);
                })
                ->orWhere(function ($q3) use ($projectModel) {
                    $q3->where('scope_type', 'project')->where('scope_id', $projectModel->id);
                });
        })->orderBy('code')->get();

        $emailLogs = EmailLog::with('template')
            ->where('project_id', $projectModel->id)
            ->orderByDesc('generated_at')
            ->limit(25)
            ->get();

        return view('admin.projects.emails', [
            'project' => $projectModel,
            'templates' => $templates,
            'emailLogs' => $emailLogs,
        ]);
    }

    public function destroy(int $project): RedirectResponse
    {
        $model = Project::withCount(['requirements', 'dataItems', 'modules'])->findOrFail($project);
        if ($model->requirements_count > 0 || $model->data_items_count > 0 || $model->modules_count > 0) {
            return redirect()->route('admin.projects.index')->withErrors(['Project has related records and cannot be deleted.']);
        }

        $model->delete();

        return redirect()->route('admin.projects.index')->with('status', 'Project removed');
    }

    public function addTeamMember(int $project): RedirectResponse
    {
        $data = request()->validate([
            'user_id' => 'required|exists:users,id',
            'role_in_project' => 'nullable|string|max:255',
        ]);

        $actor = auth()->id();
        ProjectTeam::updateOrCreate(
            ['project_id' => $project, 'user_id' => $data['user_id']],
            [
                'role_in_project' => $data['role_in_project'] ?? null,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]
        );

        return redirect()->route('admin.projects.show', $project)->with('status', 'Team member saved');
    }

    public function removeTeamMember(int $project, int $teamId): RedirectResponse
    {
        $team = ProjectTeam::where('project_id', $project)->findOrFail($teamId);
        $team->delete();

        return redirect()->route('admin.projects.show', $project)->with('status', 'Team member removed');
    }

    private function syncTeam(Project $project, array $teamMembers, ?int $actor): void
    {
        if (empty($teamMembers)) {
            ProjectTeam::where('project_id', $project->id)->delete();
            return;
        }

        $keep = [];
        foreach ($teamMembers as $member) {
            if (!isset($member['user_id'])) {
                continue;
            }
            $team = ProjectTeam::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $member['user_id']],
                [
                    'role_in_project' => $member['role_in_project'] ?? null,
                    'created_by' => $actor,
                    'updated_by' => $actor,
                ]
            );
            $keep[] = $team->id;
        }

        if ($keep) {
            ProjectTeam::where('project_id', $project->id)->whereNotIn('id', $keep)->delete();
        }
    }

    /**
     * Ensure clients dropdown includes entries mirrored from customers (client/customer are treated the same).
     */
    private function clientOptions()
    {
        $existingByCode = Client::withTrashed()->pluck('id', 'client_code');

        Customer::orderBy('name')->each(function (Customer $customer) use (&$existingByCode) {
            if (!$customer->customer_code) {
                return;
            }

            if ($existingByCode->has($customer->customer_code)) {
                return;
            }

            $client = Client::create([
                'client_code' => $customer->customer_code,
                'name' => $customer->name,
                'industry' => $customer->industry,
                'website' => $customer->website,
                'billing_address' => $customer->billing_address,
                'is_active' => $customer->is_active,
                'created_by' => $customer->created_by,
                'updated_by' => $customer->updated_by,
            ]);

            $existingByCode[$customer->customer_code] = $client->id;
        });

        return Client::withTrashed()->orderBy('name')->get();
    }

    /**
     * Provide a "Customer" dropdown while still storing `projects.client_id`.
     * Customers are mirrored into `clients` by `clientOptions()`, and the select value remains the Client ID.
     *
     * @return \Illuminate\Support\Collection<int, array{client_id:int,name:string,code:string,is_active:bool}>
     */
    private function customerSelectOptions()
    {
        $clients = $this->clientOptions();
        $clientsByCode = $clients->keyBy('client_code');

        $customers = Customer::orderBy('name')->get();
        $options = $customers->map(function (Customer $customer) use ($clientsByCode) {
            $client = $clientsByCode->get($customer->customer_code);
            return [
                'client_id' => $client?->id,
                'name' => $customer->name,
                'code' => $customer->customer_code,
                'is_active' => (bool) $customer->is_active,
            ];
        })->filter(fn ($row) => !empty($row['client_id']));

        // Include any legacy Client entries that don't have a Customer mirror yet.
        $legacy = $clients->filter(function (Client $client) use ($customers) {
            return !$customers->contains('customer_code', $client->client_code);
        })->map(function (Client $client) {
            return [
                'client_id' => $client->id,
                'name' => $client->name,
                'code' => $client->client_code,
                'is_active' => (bool) $client->is_active,
            ];
        });

        return $options
            ->merge($legacy)
            ->unique('client_id')
            ->values();
    }
}
