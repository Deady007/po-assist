<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('name')->get();
        return view('dashboard', compact('projects'));
    }

    public function product()
    {
        $today = now()->startOfDay();
        $next7 = now()->addDays(7)->endOfDay();
        $userId = auth()->id();

        $metrics = [
            'active_projects_count' => Project::where('is_active', true)->count(),
            'projects_due_7d_count' => Project::where('is_active', true)->whereBetween('due_date', [$today, $next7])->count(),
            'blocked_modules_count' => ProjectModule::where('is_active', true)->where('status', 'BLOCKED')->count(),
            'overdue_tasks_count' => Task::where('status', '!=', 'DONE')->whereDate('due_date', '<', $today)->count(),
            'tasks_due_today_count' => Task::whereDate('due_date', $today)->where('status', '!=', 'DONE')->count(),
            'my_overdue_tasks_count' => Task::where('assignee_user_id', $userId)->where('status', '!=', 'DONE')->whereDate('due_date', '<', $today)->count(),
            'my_tasks_due_7d_count' => Task::where('assignee_user_id', $userId)->where('status', '!=', 'DONE')->whereBetween('due_date', [$today, $next7])->count(),
        ];

        $overdueTasks = Task::with(['project', 'module', 'assignee'])
            ->where('status', '!=', 'DONE')
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $blockedModules = ProjectModule::with('project')
            ->where('is_active', true)
            ->where('status', 'BLOCKED')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return view('dashboards.product', compact('metrics', 'overdueTasks', 'blockedModules'));
    }

    public function tasks()
    {
        $userId = auth()->id();
        $role = auth()->user()?->role?->name ?? '';

        $filters = [
            'status' => request('status'),
            'project_id' => request('project_id'),
            'module_id' => request('module_id'),
            'due_from' => request('due_from'),
            'due_to' => request('due_to'),
            'overdue_only' => request()->boolean('overdue_only'),
        ];

        $applyFilters = function ($query) use ($filters) {
            if ($filters['project_id']) {
                $query->where('project_id', $filters['project_id']);
            }
            if ($filters['module_id']) {
                $query->where('project_module_id', $filters['module_id']);
            }
            if ($filters['status']) {
                $query->where('status', strtoupper($filters['status']));
            }
            if ($filters['overdue_only']) {
                $query->where('status', '!=', 'DONE')->whereDate('due_date', '<', now()->startOfDay());
            }
            if ($filters['due_from']) {
                $query->whereDate('due_date', '>=', $filters['due_from']);
            }
            if ($filters['due_to']) {
                $query->whereDate('due_date', '<=', $filters['due_to']);
            }
        };

        $myQuery = Task::with(['project', 'module'])
            ->where('assignee_user_id', $userId);

        $applyFilters($myQuery);

        $myTasks = $myQuery->orderBy('due_date')->paginate(15)->withQueryString();

        $teamTasks = null;
        $teamSummary = collect();
        if (in_array($role, ['Admin', 'PM'], true)) {
            $teamQuery = Task::with(['project', 'module', 'assignee']);
            $applyFilters($teamQuery);
            $teamTasks = $teamQuery->orderBy('due_date')->paginate(15)->withQueryString();

            $summaryQuery = Task::selectRaw('status, count(*) as count');
            $applyFilters($summaryQuery);
            $teamSummary = $summaryQuery->groupBy('status')->get();
        }

        $projects = Project::orderBy('name')->get();
        $modules = $filters['project_id']
            ? ProjectModule::where('project_id', $filters['project_id'])->orderBy('order_no')->get()
            : collect();

        return view('dashboards.tasks', compact('myTasks', 'teamTasks', 'teamSummary', 'projects', 'modules', 'filters', 'role'));
    }

    public function workload()
    {
        return view('dashboards.workload');
    }
}
