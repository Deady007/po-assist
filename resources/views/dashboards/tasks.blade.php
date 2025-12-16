@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Task dashboard</h1>
      <p class="muted">Cross-project view. Filters apply to both my tasks and team tasks (if visible).</p>
    </div>
  </div>

  <form method="GET" class="card grid two" style="align-items:flex-end;">
    <div>
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        @foreach(['TODO','IN_PROGRESS','BLOCKED','DONE'] as $status)
          <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_',' ', $status) }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label>Project</label>
      <select name="project_id">
        <option value="">All</option>
        @foreach($projects as $project)
          <option value="{{ $project->id }}" @selected($filters['project_id'] == $project->id)>{{ $project->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label>Module</label>
      <select name="module_id">
        <option value="">All</option>
        @foreach($modules as $module)
          <option value="{{ $module->id }}" @selected($filters['module_id'] == $module->id)>{{ $module->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="grid two" style="gap:8px;">
      <div>
        <label>Due from</label>
        <input type="date" name="due_from" value="{{ $filters['due_from'] }}">
      </div>
      <div>
        <label>Due to</label>
        <input type="date" name="due_to" value="{{ $filters['due_to'] }}">
      </div>
    </div>
    <div>
      <label>Overdue only</label>
      <select name="overdue_only">
        <option value="0" @selected(!$filters['overdue_only'])>No</option>
        <option value="1" @selected($filters['overdue_only'])>Yes</option>
      </select>
    </div>
    <div style="align-self:flex-end;">
      <button class="btn secondary" type="submit">Apply filters</button>
    </div>
  </form>

  <div class="card" style="margin-top:16px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
      <div>
        <h3 style="margin:0;">My tasks ({{ $myTasks->total() }})</h3>
        <p class="muted">Assignments for {{ auth()->user()->name ?? 'you' }}.</p>
      </div>
    </div>
    <div class="stacked">
      @forelse($myTasks as $task)
        @php
          $statusClass = match($task->status) {
            'DONE' => 'green',
            'BLOCKED' => 'rose',
            'IN_PROGRESS' => 'blue',
            default => 'amber',
          };
        @endphp
        <div style="padding:10px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; gap:10px;">
          <div>
            <div style="font-weight:700;">{{ $task->title }}</div>
            <div class="muted">Project: {{ $task->project?->name ?? 'N/A' }} | Module: {{ $task->module?->name ?? 'N/A' }}</div>
            <div class="muted">Status: {{ str_replace('_',' ', $task->status) }} | Due: {{ optional($task->due_date)->toDateString() ?? 'N/A' }}</div>
          </div>
          <span class="badge {{ $statusClass }}">{{ str_replace('_',' ', $task->status) }}</span>
        </div>
      @empty
        <div class="muted">No tasks found.</div>
      @endforelse
    </div>
    <div style="margin-top:12px;">
      {{ $myTasks->links() }}
    </div>
  </div>

  @if(in_array($role, ['Admin','PM']))
    <div class="card" style="margin-top:16px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <div>
          <h3 style="margin:0;">Team tasks</h3>
          <p class="muted">Summary and list across the team.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          @foreach($teamSummary as $row)
            @php
              $cls = match($row->status) {
                'DONE' => 'green',
                'BLOCKED' => 'rose',
                'IN_PROGRESS' => 'blue',
                default => 'amber',
              };
            @endphp
            <span class="badge {{ $cls }}">{{ str_replace('_',' ', $row->status) }}: {{ $row->count }}</span>
          @endforeach
        </div>
      </div>
      <div class="stacked">
        @forelse($teamTasks as $task)
          @php
            $statusClass = match($task->status) {
              'DONE' => 'green',
              'BLOCKED' => 'rose',
              'IN_PROGRESS' => 'blue',
              default => 'amber',
            };
          @endphp
          <div style="padding:10px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; gap:10px;">
            <div>
              <div style="font-weight:700;">{{ $task->title }}</div>
              <div class="muted">Project: {{ $task->project?->name ?? 'N/A' }} | Module: {{ $task->module?->name ?? 'N/A' }}</div>
              <div class="muted">Assignee: {{ $task->assignee?->name ?? 'Unassigned' }} | Due: {{ optional($task->due_date)->toDateString() ?? 'N/A' }}</div>
            </div>
            <span class="badge {{ $statusClass }}">{{ str_replace('_',' ', $task->status) }}</span>
          </div>
        @empty
          <div class="muted">No team tasks for these filters.</div>
        @endforelse
      </div>
      <div style="margin-top:12px;">
        {{ $teamTasks->links() }}
      </div>
    </div>
  @endif
@endsection
