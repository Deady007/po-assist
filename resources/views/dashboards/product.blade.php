@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Product dashboard</h1>
      <p class="muted">Live KPIs from projects, modules, and tasks.</p>
    </div>
  </div>

  <div class="grid two">
    <div class="card stacked">
      <div class="section-title">KPI overview</div>
      <div class="grid two">
        <div>
          <div class="muted">Active projects</div>
          <div style="font-size:26px; font-weight:800;">{{ $metrics['active_projects_count'] }}</div>
        </div>
        <div>
          <div class="muted">Projects due next 7 days</div>
          <div style="font-size:26px; font-weight:800;">{{ $metrics['projects_due_7d_count'] }}</div>
        </div>
        <div>
          <div class="muted">Blocked modules</div>
          <div style="font-size:26px; font-weight:800;">{{ $metrics['blocked_modules_count'] }}</div>
        </div>
        <div>
          <div class="muted">Overdue tasks</div>
          <div style="font-size:26px; font-weight:800;">{{ $metrics['overdue_tasks_count'] }}</div>
        </div>
        <div>
          <div class="muted">Tasks due today</div>
          <div style="font-size:26px; font-weight:800;">{{ $metrics['tasks_due_today_count'] }}</div>
        </div>
        <div>
          <div class="muted">My overdue tasks</div>
          <div style="font-size:26px; font-weight:800;">{{ $metrics['my_overdue_tasks_count'] }}</div>
        </div>
        <div>
          <div class="muted">My tasks due next 7 days</div>
          <div style="font-size:26px; font-weight:800;">{{ $metrics['my_tasks_due_7d_count'] }}</div>
        </div>
      </div>
    </div>
    <div class="card stacked">
      <div class="section-title">Guidance</div>
      <p class="muted">Prioritize clearing blockers and overdue tasks. Phase 5 will add workload AI; this is data-only for now.</p>
      <a class="btn secondary" href="{{ route('dashboard.tasks') }}">Open task dashboard</a>
    </div>
  </div>

  <div class="grid two" style="margin-top:16px;">
    <div class="card stacked">
      <div class="section-title">Top overdue tasks</div>
      <div class="stacked">
        @forelse($overdueTasks as $task)
          <div style="padding:8px 0; border-bottom:1px solid var(--border);">
            <div style="font-weight:700;">{{ $task->title }}</div>
            <div class="muted">Project: {{ $task->project?->name ?? 'N/A' }} | Module: {{ $task->module?->name ?? 'N/A' }}</div>
            <div class="muted">Assignee: {{ $task->assignee?->name ?? 'Unassigned' }} | Due: {{ optional($task->due_date)->toDateString() ?? 'N/A' }}</div>
          </div>
        @empty
          <div class="muted">No overdue tasks.</div>
        @endforelse
      </div>
    </div>
    <div class="card stacked">
      <div class="section-title">Blocked modules</div>
      <div class="stacked">
        @forelse($blockedModules as $module)
          <div style="padding:8px 0; border-bottom:1px solid var(--border);">
            <div style="font-weight:700;">{{ $module->name }}</div>
            <div class="muted">Project: {{ $module->project?->name ?? 'N/A' }} | Due: {{ optional($module->due_date)->toDateString() ?? 'N/A' }}</div>
            <div class="muted">Blocker: {{ $module->blocker_reason ?? 'Not provided' }}</div>
          </div>
        @empty
          <div class="muted">No blocked modules right now.</div>
        @endforelse
      </div>
    </div>
  </div>
@endsection
