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
        $clients = $this->clientOptions();
        $statuses = ProjectStatus::orderBy('order_no')->get();
        $users = User::orderBy('name')->get();

        return view('admin.projects.create', compact('clients', 'statuses', 'users'));
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
        // Include soft-deleted clients so existing project links still appear in the dropdown.
        $clients = $this->clientOptions();
        $statuses = ProjectStatus::orderBy('order_no')->get();
        $users = User::orderBy('name')->get();

        return view('admin.projects.edit', [
            'project' => $model,
            'clients' => $clients,
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

    public function workflow(int $project): View
    {
        $projectModel = Project::findOrFail($project);
        $modules = $this->workflow->listModules($projectModel->id);
        $users = User::orderBy('name')->get();
        $role = auth()->user()?->role?->name ?? '';

        return view('admin.projects.workflow', [
            'project' => $projectModel,
            'modules' => $modules,
            'users' => $users,
            'role' => $role,
            'moduleStatuses' => ['NOT_STARTED', 'IN_PROGRESS', 'BLOCKED', 'DONE'],
            'taskStatuses' => ['TODO', 'IN_PROGRESS', 'BLOCKED', 'DONE'],
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
}
