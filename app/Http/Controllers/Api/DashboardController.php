<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function product(Request $request)
    {
        $today = now()->startOfDay();
        $next7 = now()->addDays(7)->endOfDay();
        $userId = $request->user()?->id;

        $data = [
            'active_projects_count' => Project::where('is_active', true)->count(),
            'projects_due_7d_count' => Project::where('is_active', true)->whereBetween('due_date', [$today, $next7])->count(),
            'blocked_modules_count' => ProjectModule::where('is_active', true)->where('status', 'BLOCKED')->count(),
            'overdue_tasks_count' => Task::where('status', '!=', 'DONE')->whereDate('due_date', '<', $today)->count(),
            'tasks_due_today_count' => Task::whereDate('due_date', $today)->where('status', '!=', 'DONE')->count(),
            'my_overdue_tasks_count' => Task::where('assignee_user_id', $userId)->where('status', '!=', 'DONE')->whereDate('due_date', '<', $today)->count(),
            'my_tasks_due_7d_count' => Task::where('assignee_user_id', $userId)->where('status', '!=', 'DONE')->whereBetween('due_date', [$today, $next7])->count(),
        ];

        return $this->success($data);
    }

    public function tasks(Request $request)
    {
        $user = $request->user();
        $role = $user?->role?->name ?? '';
        $sort = $request->query('sort', 'due_date');
        $dir = $request->query('dir', 'asc');
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $query = Task::with(['project', 'module', 'assignee'])
            ->where('assignee_user_id', $user?->id);

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }

        if ($request->boolean('overdue_only')) {
            $query->where('status', '!=', 'DONE')
                ->whereDate('due_date', '<', now()->startOfDay());
        }

        $paginator = $query->orderBy($sort, $dir)->paginate($perPage);

        $teamSummary = [];
        if (in_array($role, ['Admin', 'PM'], true)) {
            $teamSummary = Task::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get();
        }

        return $this->success([
            'my_tasks' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'team_tasks' => $teamSummary,
        ]);
    }
}
