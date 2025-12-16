@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Tasks — {{ $project->name }}</h1>
      <p class="muted">Filter by module, assignee, status, or overdue. Developers can update only their tasks.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
      <a class="btn secondary" href="{{ route('admin.projects.show', $project->id) }}">Overview</a>
      <a class="btn secondary" href="{{ route('admin.projects.workflow', $project->id) }}">Workflow</a>
      @if(in_array($role, ['Admin','PM']))
        <a class="btn" href="{{ route('admin.projects.workflow', $project->id) }}">Add tasks</a>
      @endif
    </div>
  </div>

  <form method="GET" class="card grid two" style="align-items:flex-end;">
    <div>
      <label>Module</label>
      <select name="module_id">
        <option value="">All</option>
        @foreach($modules as $module)
          <option value="{{ $module->id }}" @selected($filters['module_id'] == $module->id)>{{ $module->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label>Assignee</label>
      <select name="assignee_user_id">
        <option value="">All</option>
        @foreach($users as $user)
          <option value="{{ $user->id }}" @selected($filters['assignee_user_id'] == $user->id)>{{ $user->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        @foreach($taskStatuses as $status)
          <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_',' ', $status) }}</option>
        @endforeach
      </select>
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
        <h3 style="margin:0;">Tasks ({{ $tasks->total() }})</h3>
        <p class="muted">Quick status edits inline. Use workflow tab for module context.</p>
      </div>
    </div>
    <div class="stacked">
      @forelse($tasks as $task)
        @php
          $statusClass = match($task->status) {
            'DONE' => 'green',
            'BLOCKED' => 'rose',
            'IN_PROGRESS' => 'blue',
            default => 'amber',
          };
          $canEdit = in_array($role, ['Admin','PM']) || ($role === 'Developer' && $task->assignee_user_id === auth()->id());
        @endphp
        <div style="display:grid; grid-template-columns: 1fr 260px; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); align-items:flex-start;">
          <div>
            <div style="font-weight:700;">{{ $task->title }}</div>
            <div class="muted">Module: {{ $task->module?->name ?? 'N/A' }} | Assignee: {{ $task->assignee?->name ?? 'Unassigned' }}</div>
            <div class="muted">Priority: {{ ucfirst(strtolower($task->priority)) ?? 'Medium' }} | Due: {{ optional($task->due_date)->toDateString() ?? 'N/A' }}</div>
            @if($task->blocker_reason)
              <div class="muted">Blocker: {{ $task->blocker_reason }}</div>
            @endif
          </div>
          <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-start;">
            <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
              <span class="badge {{ $statusClass }}">{{ str_replace('_',' ', $task->status) }}</span>
              @if($canEdit)
                <form method="POST" action="{{ route('admin.projects.modules.tasks.update', [$project->id, $task->project_module_id, $task->id]) }}" data-blocker-required style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                  @csrf
                  @method('PUT')
                  <select name="status" data-status-toggle>
                    @foreach($taskStatuses as $status)
                      <option value="{{ $status }}" @selected($task->status === $status)>{{ str_replace('_',' ', $status) }}</option>
                    @endforeach
                  </select>
                  <input type="text" name="blocker_reason" value="{{ $task->blocker_reason }}" placeholder="Blocker reason" data-blocker-input style="display:none; width:160px;">
                  <button class="btn secondary" type="submit">Save</button>
                </form>
              @endif
            </div>
            @if(in_array($role, ['Admin','PM']))
              <details>
                <summary class="muted" style="cursor:pointer;">Edit task</summary>
                <form method="POST" action="{{ route('admin.projects.modules.tasks.update', [$project->id, $task->project_module_id, $task->id]) }}" class="card" style="margin-top:6px;" data-blocker-required>
                  @csrf
                  @method('PUT')
                  <div class="grid two">
                    <div>
                      <label>Title</label>
                      <input name="title" value="{{ $task->title }}" required>
                    </div>
                    <div>
                      <label>Assignee</label>
                      <select name="assignee_user_id">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                          <option value="{{ $user->id }}" @selected($task->assignee_user_id === $user->id)>{{ $user->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="grid two" style="margin-top:8px;">
                    <div>
                      <label>Status</label>
                      <select name="status" data-status-toggle>
                        @foreach($taskStatuses as $status)
                          <option value="{{ $status }}" @selected($task->status === $status)>{{ str_replace('_',' ', $status) }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div>
                      <label>Priority</label>
                      <select name="priority">
                        @foreach(['LOW','MEDIUM','HIGH'] as $priority)
                          <option value="{{ $priority }}" @selected(strtoupper($task->priority) === $priority)>{{ ucfirst(strtolower($priority)) }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="grid two" style="margin-top:8px;">
                    <div>
                      <label>Due date</label>
                      <input type="date" name="due_date" value="{{ optional($task->due_date)->toDateString() }}">
                    </div>
                    <div>
                      <label>Description</label>
                      <input name="description" value="{{ $task->description }}">
                    </div>
                  </div>
                  <div style="margin-top:8px;" data-blocker>
                    <label>Blocker reason</label>
                    <textarea name="blocker_reason" data-blocker-input>{{ $task->blocker_reason }}</textarea>
                  </div>
                  <div style="margin-top:8px; display:flex; gap:8px;">
                    <button class="btn secondary" type="submit">Save changes</button>
                  </div>
                </form>
                <form method="POST" action="{{ route('admin.projects.modules.tasks.destroy', [$project->id, $task->project_module_id, $task->id]) }}" onsubmit="return confirm('Delete task?')" style="margin-top:6px;">
                  @csrf
                  @method('DELETE')
                  <button class="btn secondary" type="submit">Delete</button>
                </form>
              </details>
            @endif
          </div>
        </div>
      @empty
        <div class="muted">No tasks found.</div>
      @endforelse
    </div>
    <div style="margin-top:12px;">
      {{ $tasks->links() }}
    </div>
  </div>

  <script>
    (function() {
      const toggleBlocker = (select) => {
        const form = select.closest('form');
        const blocker = form?.querySelector('[data-blocker]');
        const blockerInput = form?.querySelector('[data-blocker-input]');
        const blocked = select.value === 'BLOCKED';
        if (blocker) blocker.style.display = blocked ? 'block' : 'none';
        if (blockerInput) blockerInput.style.display = blocked ? 'block' : (blockerInput.tagName === 'INPUT' ? '' : 'none');
      };
      document.querySelectorAll('[data-status-toggle]').forEach((select) => {
        toggleBlocker(select);
        select.addEventListener('change', () => toggleBlocker(select));
      });
      document.querySelectorAll('form[data-blocker-required]').forEach((form) => {
        form.addEventListener('submit', (e) => {
          const status = form.querySelector('[data-status-toggle]');
          const blocker = form.querySelector('[data-blocker-input]');
          if (status && status.value === 'BLOCKED' && blocker && !blocker.value.trim()) {
            e.preventDefault();
            alert('Blocker reason is required when blocking.');
          }
        });
      });
    })();
  </script>
@endsection
