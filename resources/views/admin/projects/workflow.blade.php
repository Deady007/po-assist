@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Workflow — {{ $project->name }}</h1>
      <p class="muted">Track modules and tasks. Blockers require reasons; DONE modules require all tasks DONE.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
      <a class="btn secondary" href="{{ route('admin.projects.show', $project->id) }}">Overview</a>
      <a class="btn secondary" href="{{ route('admin.projects.tasks', $project->id) }}">Tasks</a>
      @if(in_array($role, ['Admin','PM']))
        @if($modules->isEmpty())
          <form method="POST" action="{{ route('admin.projects.modules.init', $project->id) }}">
            @csrf
            <button class="btn" type="submit">Initialize modules</button>
          </form>
        @else
          <form method="POST" action="{{ route('admin.projects.modules.init', $project->id) }}">
            @csrf
            <button class="btn secondary" type="submit">Re-run init (idempotent)</button>
          </form>
        @endif
      @endif
    </div>
  </div>

  @if(session('status'))
    <div class="card" style="background:#f8fafc; border-color: var(--border);">
      {{ session('status') }}
    </div>
  @endif

  @if(in_array($role, ['Admin','PM']))
    <div class="card stacked">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
          <div class="section-title">Add module</div>
          <p class="muted">Custom modules live alongside template-driven ones.</p>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.projects.modules.store', $project->id) }}" data-blocker-required>
        @csrf
        <div class="grid two">
          <div>
            <label>Name</label>
            <input name="name" required placeholder="e.g., Data Migration">
          </div>
          <div>
            <label>Order</label>
            <input type="number" min="1" name="order_no" placeholder="{{ $modules->max('order_no') + 1 }}">
          </div>
        </div>
        <div class="grid two" style="margin-top:8px;">
          <div>
            <label>Owner</label>
            <select name="owner_user_id">
              <option value="">Unassigned</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Due date</label>
            <input type="date" name="due_date">
          </div>
        </div>
        <div class="grid two" style="margin-top:8px;">
          <div>
            <label>Start date</label>
            <input type="date" name="start_date">
          </div>
          <div>
            <label>Status</label>
            <select name="status" data-status-toggle>
              @foreach($moduleStatuses as $status)
                <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div style="margin-top:8px; display:none;" data-blocker>
          <label>Blocker reason</label>
          <textarea name="blocker_reason" data-blocker-input placeholder="Required when blocked"></textarea>
        </div>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Create module</button>
        </div>
      </form>
    </div>
  @endif

  @if($modules->isEmpty())
    <div class="card">
      <div class="section-title">No modules yet</div>
      <p class="muted">Run "Initialize modules" to seed from templates or add your own.</p>
    </div>
  @endif

  @foreach($modules as $module)
    @php
      $moduleStatusClass = match($module->status) {
        'DONE' => 'green',
        'BLOCKED' => 'rose',
        'IN_PROGRESS' => 'blue',
        default => 'amber',
      };
      $canManageModules = in_array($role, ['Admin','PM']);
    @endphp
    <details class="card" @if($loop->first) open @endif>
      <summary style="cursor:pointer; list-style:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
          <div>
            <div style="font-weight:800;">{{ $module->name }}</div>
            <div class="muted">
              Owner: {{ $module->owner?->name ?? 'Unassigned' }} | Due: {{ optional($module->due_date)->toDateString() ?? 'N/A' }} | Order: {{ $module->order_no ?? '-' }}
            </div>
            <div class="muted">Progress: {{ $module->done_tasks ?? 0 }}/{{ $module->total_tasks ?? 0 }} done | Overdue tasks: {{ $module->overdue_tasks ?? 0 }}</div>
            @if($module->blocker_reason)
              <div class="muted">Blocker: {{ $module->blocker_reason }}</div>
            @endif
          </div>
          <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <span class="badge {{ $moduleStatusClass }}">{{ str_replace('_',' ', $module->status) }}</span>
            @if($canManageModules)
              <form method="POST" action="{{ route('admin.projects.modules.update', [$project->id, $module->id]) }}" data-blocker-required style="display:flex; gap:6px; align-items:center;">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ $module->name }}">
                <input type="hidden" name="order_no" value="{{ $module->order_no }}">
                <select name="status" data-status-toggle>
                  @foreach($moduleStatuses as $status)
                    <option value="{{ $status }}" @selected($module->status === $status)>{{ str_replace('_',' ', $status) }}</option>
                  @endforeach
                </select>
                <input type="text" name="blocker_reason" value="{{ $module->blocker_reason }}" placeholder="Blocker reason" data-blocker-input style="display:none; width:200px;">
                <button class="btn secondary" type="submit">Update</button>
              </form>
            @endif
          </div>
        </div>
      </summary>
      <div class="stacked" style="margin-top:12px;">
        @if($canManageModules)
          <form method="POST" action="{{ route('admin.projects.modules.update', [$project->id, $module->id]) }}" class="card" style="background:#f8fafc;" data-blocker-required>
            @csrf
            @method('PUT')
            <div class="grid two">
              <div>
                <label>Name</label>
                <input name="name" value="{{ $module->name }}" required>
              </div>
              <div>
                <label>Order</label>
                <input type="number" min="1" name="order_no" value="{{ $module->order_no }}">
              </div>
            </div>
            <div class="grid two" style="margin-top:8px;">
              <div>
                <label>Owner</label>
                <select name="owner_user_id">
                  <option value="">Unassigned</option>
                  @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected($module->owner_user_id === $user->id)>{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label>Due date</label>
                <input type="date" name="due_date" value="{{ optional($module->due_date)->toDateString() }}">
              </div>
            </div>
            <div class="grid two" style="margin-top:8px;">
              <div>
                <label>Start date</label>
                <input type="date" name="start_date" value="{{ optional($module->start_date)->toDateString() }}">
              </div>
              <div>
                <label>Status</label>
                <select name="status" data-status-toggle>
                  @foreach($moduleStatuses as $status)
                    <option value="{{ $status }}" @selected($module->status === $status)>{{ str_replace('_',' ', $status) }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div data-blocker style="margin-top:8px; @if($module->status !== 'BLOCKED')display:none;@endif">
              <label>Blocker reason</label>
              <textarea name="blocker_reason" data-blocker-input placeholder="Required when blocked">{{ $module->blocker_reason }}</textarea>
            </div>
            <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
              <button class="btn secondary" type="submit">Save module</button>
            </div>
          </form>
          <form method="POST" action="{{ route('admin.projects.modules.destroy', [$project->id, $module->id]) }}" onsubmit="return confirm('Delete module| Tasks will also be removed.')" style="margin-top:8px;">
            @csrf
            @method('DELETE')
            <button class="btn secondary" type="submit">Delete module</button>
          </form>
        @endif

        <div class="card">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div>
              <div class="section-title">Tasks</div>
              <p class="muted">Quick status changes allowed for assigned developers.</p>
            </div>
            <span class="pill-tag">{{ $module->tasks->count() }} tasks</span>
          </div>
          <div class="stacked">
            @forelse($module->tasks as $task)
              @php
                $taskStatusClass = match($task->status) {
                  'DONE' => 'green',
                  'BLOCKED' => 'rose',
                  'IN_PROGRESS' => 'blue',
                  default => 'amber',
                };
                $canEditTask = in_array($role, ['Admin','PM']) || ($role === 'Developer' && $task->assignee_user_id === auth()->id());
              @endphp
              <div style="border:1px solid var(--border); border-radius:10px; padding:10px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                  <div>
                    <div style="font-weight:700;">{{ $task->title }}</div>
                    <div class="muted">Assignee: {{ $task->assignee?->name ?? 'Unassigned' }} | Priority: {{ ucfirst(strtolower($task->priority)) ?? 'Medium' }}</div>
                    <div class="muted">Due: {{ optional($task->due_date)->toDateString() ?? 'N/A' }}</div>
                    @if($task->blocker_reason)
                      <div class="muted">Blocker: {{ $task->blocker_reason }}</div>
                    @endif
                  </div>
                  <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                    <span class="badge {{ $taskStatusClass }}">{{ str_replace('_',' ', $task->status) }}</span>
                    @if($canEditTask)
                      <form method="POST" action="{{ route('admin.projects.modules.tasks.update', [$project->id, $module->id, $task->id]) }}" data-blocker-required style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                        @csrf
                        @method('PUT')
                        <select name="status" data-status-toggle>
                          @foreach($taskStatuses as $status)
                            <option value="{{ $status }}" @selected($task->status === $status)>{{ str_replace('_',' ', $status) }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="blocker_reason" value="{{ $task->blocker_reason }}" placeholder="Blocker reason" data-blocker-input style="display:none; width:180px;">
                        <button class="btn secondary" type="submit">Save</button>
                      </form>
                    @endif
                    @if(in_array($role, ['Admin','PM']))
                      <form method="POST" action="{{ route('admin.projects.modules.tasks.destroy', [$project->id, $module->id, $task->id]) }}" onsubmit="return confirm('Delete task|')">
                        @csrf
                        @method('DELETE')
                        <button class="btn secondary" type="submit">Delete</button>
                      </form>
                    @endif
                  </div>
                </div>
              </div>
            @empty
              <div class="muted">No tasks yet.</div>
            @endforelse
          </div>
          @if(in_array($role, ['Admin','PM']))
            <form method="POST" action="{{ route('admin.projects.modules.tasks.store', [$project->id, $module->id]) }}" style="margin-top:12px;" data-blocker-required>
              @csrf
              <div class="grid two">
                <div>
                  <label>Task title</label>
                  <input name="title" required>
                </div>
                <div>
                  <label>Assignee</label>
                  <select name="assignee_user_id">
                    <option value="">Unassigned</option>
                    @foreach($users as $user)
                      <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="grid two" style="margin-top:8px;">
                <div>
                  <label>Status</label>
                  <select name="status" data-status-toggle>
                    @foreach($taskStatuses as $status)
                      <option value="{{ $status }}">{{ str_replace('_',' ', $status) }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label>Priority</label>
                  <select name="priority">
                    @foreach(['LOW','MEDIUM','HIGH'] as $priority)
                      <option value="{{ $priority }}">{{ ucfirst(strtolower($priority)) }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="grid two" style="margin-top:8px;">
                <div>
                  <label>Due date</label>
                  <input type="date" name="due_date">
                </div>
                <div>
                  <label>Description</label>
                  <input name="description" placeholder="Optional description">
                </div>
              </div>
              <div style="margin-top:8px; display:none;" data-blocker>
                <label>Blocker reason</label>
                <textarea name="blocker_reason" data-blocker-input placeholder="Required when blocked"></textarea>
              </div>
              <div style="margin-top:10px;">
                <button class="btn secondary" type="submit">Add task</button>
              </div>
            </form>
          @endif
        </div>
      </div>
    </details>
  @endforeach

  <script>
    (function() {
      const toggleBlocker = (select) => {
        const form = select.closest('[data-blocker-required]');
        const blockerWrapper = form | form.querySelector('[data-blocker]') : select.closest('form')|.querySelector('[data-blocker]');
        const blockerInput = form | form.querySelector('[data-blocker-input]') : select.closest('form')|.querySelector('[data-blocker-input]');
        const isBlocked = select.value === 'BLOCKED';
        if (blockerWrapper) blockerWrapper.style.display = isBlocked | 'block' : 'none';
        if (blockerInput) blockerInput.style.display = isBlocked | 'block' : (blockerInput.tagName === 'INPUT' | '' : 'none');
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
