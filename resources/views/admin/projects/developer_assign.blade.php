@extends('layout')

@push('styles')
  <style>
    .module-tabs {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: nowrap;
      overflow: auto;
      padding-bottom: 6px;
      scroll-padding-left: 12px;
    }
    .module-tabs .pill { white-space: nowrap; flex-shrink: 0; }
    .module-tabs::-webkit-scrollbar { height: 8px; }
    .module-tabs::-webkit-scrollbar-thumb { background: rgba(100,116,139,0.35); border-radius: 999px; }

    .kpi-label { font-size: 12px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .kpi-value { font-weight: 900; font-size: 18px; margin-top: 6px; }
    .progress { height: 10px; border-radius: 999px; border: 1px solid var(--border); background: #f1f5f9; overflow: hidden; }
    .progress > span { display: block; height: 100%; width: var(--pct, 0%); background: linear-gradient(135deg, var(--accent), #0284c7); }
  </style>
@endpush

@section('content')
  @php
    $totalTasks = (int) $modules->sum('total_tasks');
    $doneTasks = (int) $modules->sum('done_tasks');
    $blockedTasks = (int) $modules->sum('blocked_tasks');
    $overdueTasks = (int) $modules->sum('overdue_tasks');
    $overallProgress = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

    $healthBadge = match($health ?? '') {
      'red' => 'rose',
      'amber' => 'amber',
      default => 'green',
    };

    $projectBadgeClass = match($projectBadge ?? '') {
      'BLOCKED' => 'rose',
      'COMPLETED' => 'green',
      'NOT_STARTED' => 'amber',
      default => 'blue',
    };
  @endphp

  <div class="page-head">
    <div>
      <h1>Developer Assign &mdash; {{ $project->name }}</h1>
      <p class="muted">
        Client: {{ $project->client?->name ?? 'Unassigned' }}
        &bull; Project: {{ $project->project_code ?? $project->id }}
      </p>
    </div>
    @if(in_array($role, ['Admin','PM']))
      <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <button class="btn secondary" type="button" data-open-module-create>Add module</button>
        <form method="POST" action="{{ route('admin.projects.modules.init', $project->id) }}">
          @csrf
          <button class="btn secondary" type="submit">Init default modules</button>
        </form>
      </div>
    @endif
  </div>

  @include('admin.projects._subnav', ['project' => $project, 'role' => $role])

  <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
    <div class="card">
      <div class="kpi-label">Project badge</div>
      <div class="kpi-value"><span class="badge {{ $projectBadgeClass }}">{{ str_replace('_',' ', $projectBadge) }}</span></div>
      <div class="muted" style="margin-top:6px;">Health: <span class="badge {{ $healthBadge }}">{{ strtoupper($health ?? 'GREEN') }}</span></div>
    </div>
    <div class="card">
      <div class="kpi-label">Modules</div>
      <div class="kpi-value">{{ $modules->count() }}</div>
      <div class="muted" style="margin-top:6px;">Blocked: {{ $modules->where('status','BLOCKED')->count() }}</div>
    </div>
    <div class="card">
      <div class="kpi-label">Tasks</div>
      <div class="kpi-value">{{ $doneTasks }}/{{ $totalTasks }} done</div>
      <div class="progress" style="margin-top:8px; --pct: {{ $overallProgress }}%;">
        <span></span>
      </div>
      <div class="muted" style="margin-top:6px;">Blocked: {{ $blockedTasks }} &bull; Overdue: {{ $overdueTasks }}</div>
    </div>
    <div class="card">
      <div class="kpi-label">Context</div>
      <div class="kpi-value">{{ $requirementsCount }} requirements</div>
      <div class="muted" style="margin-top:6px;">Failing tests: {{ $failingTests }}</div>
    </div>
  </div>

  @if($modules->isEmpty())
    <div class="card stacked" style="margin-top:16px;">
      <div class="section-title">No modules yet</div>
      <div class="muted">Add a module or initialize default modules to start assigning work.</div>
      @if(in_array($role, ['Admin','PM']))
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
          <button class="btn" type="button" data-open-module-create>Add module</button>
          <form method="POST" action="{{ route('admin.projects.modules.init', $project->id) }}">
            @csrf
            <button class="btn secondary" type="submit">Init default modules</button>
          </form>
        </div>
      @endif
    </div>
  @else
    <div class="card" style="margin-top:16px;">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
          <div class="section-title" style="margin:0;">Modules</div>
          <div class="muted">Select a module to view tasks. Use Tasks page for bulk edits.</div>
        </div>
        <a class="pill ghost sm" href="{{ route('admin.projects.tasks', $project->id) }}">Open all tasks</a>
      </div>

      <div class="module-tabs" style="margin-top:12px;">
        @foreach($modules as $module)
          @php
            $moduleLabel = $module->name ?? $module->module_name ?? ('Module '.$module->id);
            $isActive = $loop->first;
          @endphp
          <button type="button"
                  class="pill sm {{ $isActive ? 'active' : 'ghost' }}"
                  data-tab-button
                  data-tab-target="module-{{ $module->id }}">
            {{ $moduleLabel }}
          </button>
        @endforeach
      </div>
    </div>

    @foreach($modules as $module)
      @php
        $moduleName = $module->name ?? $module->module_name ?? ('Module '.$module->id);
        $moduleStatusClass = match($module->status) { 'DONE'=>'green','BLOCKED'=>'rose','IN_PROGRESS'=>'blue', default=>'amber' };

        $total = (int) ($module->total_tasks ?? $module->tasks->count());
        $done = (int) ($module->done_tasks ?? $module->tasks->where('status','DONE')->count());
        $blocked = (int) ($module->blocked_tasks ?? $module->tasks->where('status','BLOCKED')->count());
        $overdue = (int) ($module->overdue_tasks ?? 0);
        $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;
      @endphp

      <section class="card" style="margin-top:16px;"
               data-tab-content="module-{{ $module->id }}"
               @if(!$loop->first) hidden @endif>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
          <div style="min-width:260px;">
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
              <div class="section-title" style="margin:0;">{{ $moduleName }}</div>
              <span class="badge {{ $moduleStatusClass }}">{{ str_replace('_',' ', $module->status) }}</span>
              @if($module->owner?->name)
                <span class="pill-tag">{{ $module->owner->name }}</span>
              @endif
              @if($module->due_date)
                <span class="pill-tag">Due {{ $module->due_date->toDateString() }}</span>
              @endif
            </div>
            <div class="muted" style="margin-top:6px;">
              Tasks: {{ $done }}/{{ $total }} done &bull; Blocked: {{ $blocked }} &bull; Overdue: {{ $overdue }}
            </div>
            <div class="progress" style="margin-top:10px; max-width:520px; --pct: {{ $pct }}%;">
              <span></span>
            </div>
            @if($module->blocker_reason)
              <div class="alert error" style="margin-top:12px;">
                <strong>Blocker</strong> &mdash; {{ $module->blocker_reason }}
              </div>
            @endif
          </div>

          <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
            <a class="btn secondary" href="{{ route('admin.projects.tasks', $project->id) }}?module_id={{ $module->id }}">Open tasks</a>
            @if(in_array($role, ['Admin','PM']))
              <button class="btn secondary" type="button" data-open-module-modal="{{ $module->id }}">Edit module</button>
              <button class="btn" type="button" data-open-task-modal="{{ $module->id }}">Add task</button>
            @endif
          </div>
        </div>

        <div class="table-wrap" style="margin-top:14px;">
          <table class="table">
            <thead>
              <tr>
                <th style="min-width:200px;">Task</th>
                <th style="min-width:160px;">Assignee</th>
                <th style="min-width:140px;">Status</th>
                <th style="min-width:110px;">Priority</th>
                <th style="min-width:260px;">Description</th>
                <th style="min-width:130px;">Due</th>
                <th style="min-width:220px;">Blocker</th>
              </tr>
            </thead>
            <tbody>
              @forelse($module->tasks as $task)
                @php
                  $taskStatusClass = match($task->status) { 'DONE'=>'green','BLOCKED'=>'rose','IN_PROGRESS'=>'blue', default=>'amber' };
                @endphp
                <tr>
                  <td>{{ $task->title }}</td>
                  <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                  <td><span class="badge {{ $taskStatusClass }}">{{ str_replace('_',' ', $task->status) }}</span></td>
                  <td>{{ ucfirst(strtolower($task->priority ?? '')) }}</td>
                  <td style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $task->description }}">{{ $task->description ?? '—' }}</td>
                  <td>{{ optional($task->due_date)->toDateString() ?? 'N/A' }}</td>
                  <td>{{ $task->blocker_reason ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="7" class="muted">No tasks yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    @endforeach
  @endif

  @if(in_array($role, ['Admin','PM']))
    <div class="modal-backdrop" id="moduleBackdropCreate"></div>
    <div class="modal" id="moduleModalCreate" role="dialog" aria-modal="true" aria-labelledby="moduleCreateTitle">
      <header>
        <div>
          <div class="section-title" id="moduleCreateTitle" style="margin:0;">Create module</div>
          <p class="muted" style="margin:4px 0 0;">Set owner, status, and due date for this module.</p>
        </div>
        <button class="btn ghost" type="button" data-close-module-create>Close</button>
      </header>
      <form method="POST" action="{{ route('admin.projects.modules.store', $project->id) }}" data-blocker-required>
        @csrf
        <div class="grid two">
          <div>
            <label>Module name</label>
            <input name="name" required>
          </div>
          <div>
            <label>Owner (assignee)</label>
            <select name="owner_user_id" class="select2">
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
            <select name="status" class="select2" data-status-toggle>
              @foreach($moduleStatuses as $status)
                <option value="{{ $status }}" @selected($status === 'NOT_STARTED')>{{ str_replace('_',' ', $status) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Order (optional)</label>
            <input type="number" name="order_no" min="1" placeholder="Auto if blank">
          </div>
        </div>
        <div class="grid two" style="margin-top:8px;">
          <div>
            <label>Start date</label>
            <input type="date" name="start_date">
          </div>
          <div>
            <label>Due date</label>
            <input type="date" name="due_date">
          </div>
        </div>
        <div style="margin-top:8px;" data-blocker hidden>
          <label>Blocker reason</label>
          <textarea name="blocker_reason" placeholder="Required only when status is BLOCKED" data-blocker-input></textarea>
        </div>
        <div style="margin-top:10px; display:flex; gap:8px; justify-content:flex-end;">
          <button class="btn secondary" type="button" data-close-module-create>Cancel</button>
          <button class="btn" type="submit">Create module</button>
        </div>
      </form>
    </div>

    @foreach($modules as $module)
      <div class="modal-backdrop" id="moduleBackdrop-{{ $module->id }}"></div>
      <div class="modal" id="moduleModal-{{ $module->id }}" role="dialog" aria-modal="true" aria-labelledby="moduleEditTitle-{{ $module->id }}">
        <header>
          <div>
            <div class="section-title" id="moduleEditTitle-{{ $module->id }}" style="margin:0;">Edit module ({{ $module->name ?? $module->module_name ?? 'Module '.$module->id }})</div>
            <p class="muted" style="margin:4px 0 0;">Update owner, status, and due dates.</p>
          </div>
          <button class="btn ghost" type="button" data-close-module-modal="{{ $module->id }}">Close</button>
        </header>
        <form method="POST" action="{{ route('admin.projects.modules.update', [$project->id, $module->id]) }}" data-blocker-required>
          @csrf
          @method('PUT')
          <div class="grid two">
            <div>
              <label>Module name</label>
              <input name="name" value="{{ $module->name }}" required>
            </div>
            <div>
              <label>Owner (assignee)</label>
              <select name="owner_user_id" class="select2">
                <option value="">Unassigned</option>
                @foreach($users as $user)
                  <option value="{{ $user->id }}" @selected($module->owner_user_id === $user->id)>{{ $user->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="grid two" style="margin-top:8px;">
            <div>
              <label>Status</label>
              <select name="status" class="select2" data-status-toggle>
                @foreach($moduleStatuses as $status)
                  <option value="{{ $status }}" @selected($module->status === $status)>{{ str_replace('_',' ', $status) }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label>Order</label>
              <input type="number" name="order_no" min="1" value="{{ $module->order_no }}">
            </div>
          </div>
          <div class="grid two" style="margin-top:8px;">
            <div>
              <label>Start date</label>
              <input type="date" name="start_date" value="{{ optional($module->start_date)->toDateString() }}">
            </div>
            <div>
              <label>Due date</label>
              <input type="date" name="due_date" value="{{ optional($module->due_date)->toDateString() }}">
            </div>
          </div>
          <div style="margin-top:8px;" data-blocker hidden>
            <label>Blocker reason</label>
            <textarea name="blocker_reason" placeholder="Required only when status is BLOCKED" data-blocker-input>{{ $module->blocker_reason }}</textarea>
          </div>
          <div style="margin-top:10px; display:flex; gap:8px; justify-content:space-between; align-items:center;">
            <button class="btn secondary" type="submit" form="moduleDeleteForm-{{ $module->id }}">Remove module</button>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
              <button class="btn secondary" type="button" data-close-module-modal="{{ $module->id }}">Cancel</button>
              <button class="btn" type="submit">Save changes</button>
            </div>
          </div>
        </form>
      </div>
      <form id="moduleDeleteForm-{{ $module->id }}" method="POST" action="{{ route('admin.projects.modules.destroy', [$project->id, $module->id]) }}" onsubmit="return confirm('Remove this module? This will delete its tasks too.');">
        @csrf
        @method('DELETE')
      </form>

      <div class="modal-backdrop" id="taskBackdrop-{{ $module->id }}"></div>
      <div class="modal" id="taskModal-{{ $module->id }}" role="dialog" aria-modal="true" aria-labelledby="taskCreateTitle-{{ $module->id }}">
        <header>
          <div>
            <div class="section-title" id="taskCreateTitle-{{ $module->id }}" style="margin:0;">Add task ({{ $module->name ?? $module->module_name ?? 'Module '.$module->id }})</div>
            <p class="muted" style="margin:4px 0 0;">Create a task for this module.</p>
          </div>
          <button class="btn ghost" type="button" data-close-task-modal="{{ $module->id }}">Close</button>
        </header>
        <form method="POST" action="{{ route('admin.projects.modules.tasks.store', [$project->id, $module->id]) }}" data-blocker-required>
          @csrf
          <div class="grid two">
            <div>
              <label>Task title</label>
              <input name="title" required>
            </div>
            <div>
              <label>Assignee</label>
              <select name="assignee_user_id" class="select2">
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
              <select name="status" class="select2" data-status-toggle>
                @foreach($taskStatuses as $status)
                  <option value="{{ $status }}">{{ str_replace('_',' ', $status) }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label>Priority</label>
              <select name="priority" class="select2">
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
          <div style="margin-top:8px;" data-blocker hidden>
            <label>Blocker reason</label>
            <textarea name="blocker_reason" placeholder="Required only when status is BLOCKED" data-blocker-input></textarea>
          </div>
          <div style="margin-top:10px; display:flex; gap:8px; justify-content:flex-end;">
            <button class="btn secondary" type="button" data-close-task-modal="{{ $module->id }}">Cancel</button>
            <button class="btn" type="submit">Add task</button>
          </div>
        </form>
      </div>
    @endforeach
  @endif
@endsection

@push('scripts')
  <script>
    (function() {
      const tabButtons = document.querySelectorAll('[data-tab-button]');
      const tabContents = document.querySelectorAll('[data-tab-content]');

      const openTab = (targetId) => {
        tabButtons.forEach((btn) => {
          const isActive = btn.dataset.tabTarget === targetId;
          btn.classList.toggle('active', isActive);
          btn.classList.toggle('ghost', !isActive);
        });
        tabContents.forEach((section) => {
          section.hidden = section.dataset.tabContent !== targetId;
        });
      };

      tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => openTab(btn.dataset.tabTarget));
      });

      @if(in_array($role, ['Admin','PM']))
      const openModal = (modalId, backdropId) => {
        document.getElementById(modalId)?.classList.add('show');
        document.getElementById(backdropId)?.classList.add('show');
      };
      const closeModal = (modalId, backdropId) => {
        document.getElementById(modalId)?.classList.remove('show');
        document.getElementById(backdropId)?.classList.remove('show');
      };

      document.querySelectorAll('[data-open-module-create]').forEach((btn) => {
        btn.addEventListener('click', () => openModal('moduleModalCreate', 'moduleBackdropCreate'));
      });
      document.querySelectorAll('[data-close-module-create]').forEach((btn) => {
        btn.addEventListener('click', () => closeModal('moduleModalCreate', 'moduleBackdropCreate'));
      });

      document.querySelectorAll('[data-open-module-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = btn.dataset.openModuleModal;
          openModal(`moduleModal-${id}`, `moduleBackdrop-${id}`);
        });
      });
      document.querySelectorAll('[data-close-module-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = btn.dataset.closeModuleModal;
          closeModal(`moduleModal-${id}`, `moduleBackdrop-${id}`);
        });
      });

      document.querySelectorAll('[data-open-task-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = btn.dataset.openTaskModal;
          openModal(`taskModal-${id}`, `taskBackdrop-${id}`);
        });
      });
      document.querySelectorAll('[data-close-task-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = btn.dataset.closeTaskModal;
          closeModal(`taskModal-${id}`, `taskBackdrop-${id}`);
        });
      });

      document.querySelectorAll('.modal-backdrop').forEach((bd) => {
        bd.addEventListener('click', () => {
          const backdropId = bd.id;
          bd.classList.remove('show');

          if (backdropId === 'moduleBackdropCreate') {
            document.getElementById('moduleModalCreate')?.classList.remove('show');
            return;
          }
          if (backdropId.startsWith('moduleBackdrop-')) {
            const id = backdropId.replace('moduleBackdrop-', '');
            document.getElementById(`moduleModal-${id}`)?.classList.remove('show');
            return;
          }
          if (backdropId.startsWith('taskBackdrop-')) {
            const id = backdropId.replace('taskBackdrop-', '');
            document.getElementById(`taskModal-${id}`)?.classList.remove('show');
          }
        });
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
          document.querySelectorAll('.modal-backdrop').forEach(bd => bd.classList.remove('show'));
        }
      });

      const toggleBlocker = (select) => {
        const form = select.closest('form');
        const blocker = form?.querySelector('[data-blocker]');
        const blocked = select.value === 'BLOCKED';
        if (blocker) blocker.hidden = !blocked;
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
      @endif
    })();
  </script>
@endpush
