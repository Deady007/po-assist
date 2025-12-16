<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\Project;
use App\Models\ProjectTeam;
use App\Services\AuditLogger;
use App\Services\ProjectService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use RuntimeException;

class ProjectController extends ApiController
{
    public function __construct(private ProjectService $projects, private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $query = Project::with(['client', 'status', 'owner']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        foreach (['client_id', 'status_id', 'owner_user_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($from = $request->query('due_from')) {
            $query->whereDate('due_date', '>=', $from);
        }
        if ($to = $request->query('due_to')) {
            $query->whereDate('due_date', '<=', $to);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', strtolower($priority));
        }

        $sort = $request->query('sort', 'due_date');
        $dir = $request->query('dir', 'asc');
        $query->orderBy($sort, $dir);

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $paginator = $query->paginate($perPage);

        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(ProjectStoreRequest $request)
    {
        try {
            $project = $this->projects->create($request->validated() + ['created_by' => $request->user()?->id]);
        } catch (RuntimeException $e) {
            return $this->failure([['code' => 'STATUS_INACTIVE', 'message' => $e->getMessage()]], 400);
        } catch (QueryException $e) {
            return $this->failure([['code' => 'DUPLICATE_CODE', 'message' => 'Project code already exists']], 409);
        }

        return $this->success(['project' => $project], status: 201);
    }

    public function show(int $project)
    {
        $model = Project::with(['client', 'status', 'owner', 'team.user'])->findOrFail($project);
        return $this->success(['project' => $model]);
    }

    public function update(ProjectUpdateRequest $request, int $project)
    {
        $model = Project::findOrFail($project);
        try {
            $updated = $this->projects->update($model, $request->validated() + ['created_by' => $request->user()?->id]);
        } catch (RuntimeException $e) {
            return $this->failure([['code' => 'STATUS_INACTIVE', 'message' => $e->getMessage()]], 400);
        } catch (QueryException $e) {
            return $this->failure([['code' => 'DUPLICATE_CODE', 'message' => 'Project code already exists']], 409);
        }

        return $this->success(['project' => $updated]);
    }

    public function activate(int $project)
    {
        $model = Project::findOrFail($project);
        $updated = $this->projects->toggle($model);

        return $this->success(['project' => $updated]);
    }

    public function changeStatus(Request $request, int $project)
    {
        $request->validate(['status_id' => 'required|exists:project_statuses,id']);
        $model = Project::findOrFail($project);

        try {
            $updated = $this->projects->changeStatus($model, (int) $request->input('status_id'));
        } catch (RuntimeException $e) {
            return $this->failure([['code' => 'STATUS_INACTIVE', 'message' => $e->getMessage()]], 400);
        }

        return $this->success(['project' => $updated]);
    }

    public function destroy(int $project)
    {
        $model = Project::findOrFail($project);
        $model->delete();

        $this->audit->logModel($model, AuditLogger::ACTION_DELETE);

        return $this->success(['message' => 'Project removed']);
    }

    public function team(int $project)
    {
        $team = ProjectTeam::with('user')->where('project_id', $project)->get();
        return $this->success(['team' => $team]);
    }

    public function upsertTeam(Request $request, int $project)
    {
        $data = $request->validate([
            'members' => 'required|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.role_in_project' => 'nullable|string|max:255',
        ]);

        $model = Project::findOrFail($project);
        $this->projects->syncTeam($model, array_map(function ($row) use ($request) {
            return $row + ['created_by' => $request->user()?->id];
        }, $data['members']));

        $team = ProjectTeam::with('user')->where('project_id', $project)->get();

        return $this->success(['team' => $team]);
    }

    public function removeTeamMember(int $project, int $user_id)
    {
        ProjectTeam::where('project_id', $project)->where('user_id', $user_id)->delete();
        return $this->success(['message' => 'Member removed']);
    }
}
