@extends('layout')

@push('styles')
  <style>
    .projects-grid {
      display: grid;
      gap: 14px;
      grid-template-columns: repeat(auto-fit, minmax(330px, 1fr));
      margin-top: 14px;
    }
    .project-card { display: flex; flex-direction: column; gap: 10px; transition: transform .12s ease; }
    .project-card:hover { transform: translateY(-2px); }
    .project-head { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
    .project-title { font-weight: 900; color: var(--ink); }
    .meta-row { display: flex; gap: 10px; flex-wrap: wrap; font-size: 13px; color: var(--muted); }
    .info-chip { padding: 7px 10px; border-radius: 12px; border: 1px solid var(--border); background: #f8fafc; font-size: 13px; color: var(--muted); }
    .modal.lg { width: min(980px, 94vw); }
    .chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .chip-x { border: none; background: transparent; cursor: pointer; color: var(--muted); font-weight: 900; padding: 0; }
    .chip-x:hover { color: var(--ink); }
  </style>
@endpush

@section('content')
  @php
    $activeFilters = [];
    if (request('search')) $activeFilters[] = ['label' => 'Search: '.request('search'), 'key' => 'search'];
    if (request('client_id')) $activeFilters[] = ['label' => 'Client: '.$clients->firstWhere('id', request('client_id'))?->name, 'key' => 'client_id'];
    if (request('owner_user_id')) $activeFilters[] = ['label' => 'Owner: '.$users->firstWhere('id', request('owner_user_id'))?->name, 'key' => 'owner_user_id'];
    if (request('due_from') || request('due_to')) $activeFilters[] = ['label' => 'Due: '.(request('due_from') ?: '...').' → '.(request('due_to') ?: '...'), 'key' => 'dates'];
    if (request('is_active') !== null && request('is_active') !== '') $activeFilters[] = ['label' => request('is_active')==='1' ? 'Active' : 'Inactive', 'key' => 'is_active'];
    if (request('priority')) $activeFilters[] = ['label' => 'Priority: '.ucfirst(request('priority')), 'key' => 'priority'];
    if (request('status_id')) $activeFilters[] = ['label' => 'Status: '.$statuses->firstWhere('id', request('status_id'))?->name, 'key' => 'status_id'];
  @endphp

  <div class="page-head">
    <div>
      <h1>Projects</h1>
      <p class="muted">Orient quickly: owners, dates, status, and next actions.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
      <span class="pill ghost sm" style="cursor:default;">{{ $projects->total() }} total</span>
      @if(in_array(auth()->user()?->role?->name, ['Admin','PM']))
        <a class="btn" href="{{ route('admin.projects.create') }}">New project</a>
      @endif
      <button class="btn secondary" type="button" id="openFilters">Filters</button>
    </div>
  </div>

  @if(count($activeFilters))
    <div class="card" style="margin-top:12px;">
      <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;">
        <div>
          <div class="section-title" style="margin:0;">Active filters</div>
          <div class="muted">Click × to remove a filter.</div>
        </div>
        <button class="btn secondary" type="button" id="clearAllInline">Clear all</button>
      </div>
      <div class="chips" style="margin-top:12px;">
        @foreach($activeFilters as $f)
          <span class="pill ghost sm" style="cursor:default;">
            {{ $f['label'] }}
            <button type="button" class="chip-x js-clear" data-clear="{{ $f['key'] }}" aria-label="Remove {{ $f['label'] }}">×</button>
          </span>
        @endforeach
      </div>
    </div>
  @endif

  <div class="projects-grid">
    @forelse($projects as $project)
      @php
        $active = $project->is_active;
        $statusName = $project->status?->name ?? 'N/A';
      @endphp
      <div class="card project-card">
        <div class="project-head">
          <div>
            <div class="project-title">{{ $project->project_code ?? 'PRJ' }} &mdash; {{ $project->name }}</div>
            <div class="meta-row">
              <span>{{ $project->client?->name ?? 'No client' }}</span>
              <span>•</span>
              <span>Owner: {{ $project->owner?->name ?? 'Unassigned' }}</span>
            </div>
          </div>
          <span class="badge {{ $active ? 'green' : 'rose' }}">{{ $active ? 'Active' : 'Inactive' }}</span>
        </div>

        <div class="meta-row">
          <span class="info-chip">Status: {{ $statusName }}</span>
          <span class="info-chip">Priority: {{ ucfirst(strtolower($project->priority ?? 'medium')) }}</span>
        </div>

        <div class="meta-row">
          <span class="info-chip">Start: {{ optional($project->start_date)->format('Y-m-d') ?? '—' }}</span>
          <span class="info-chip">Due: {{ optional($project->due_date)->format('Y-m-d') ?? '—' }}</span>
        </div>

        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
          <div class="muted">Updated {{ optional($project->updated_at)->diffForHumans() ?? 'recently' }}</div>
          <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
            <a class="btn secondary" href="{{ route('admin.projects.developer_assign', $project->id) }}">Developer Assign</a>
            <a class="btn secondary" href="{{ route('admin.projects.show', $project->id) }}">Open</a>
          </div>
        </div>
      </div>
    @empty
      <div class="card">
        <div class="muted">No projects found.</div>
      </div>
    @endforelse
  </div>

  <div style="margin-top:14px;">
    {{ $projects->links() }}
  </div>

  <div class="modal-backdrop" id="filtersBackdrop"></div>
  <div class="modal lg" id="filtersModal" role="dialog" aria-modal="true" aria-labelledby="filtersTitle">
    <header>
      <div>
        <div class="section-title" id="filtersTitle" style="margin:0;">Filters</div>
        <p class="muted" style="margin:4px 0 0;">Quick filters + advanced filters in one place.</p>
      </div>
      <button class="btn ghost" type="button" id="closeFilters">Close</button>
    </header>

    <form id="filterForm" method="GET">
      <div class="stacked">
        <div class="row" style="gap:8px; align-items:center; flex-wrap:wrap;">
          <span class="pill ghost sm" style="cursor:default;">Active</span>
          <button type="button" class="pill sm js-pill @if(request('is_active')==='1') active @else ghost @endif" data-group="is_active" data-filter="is_active" data-value="1">Active</button>
          <button type="button" class="pill sm js-pill @if(request('is_active')==='0') active @else ghost @endif" data-group="is_active" data-filter="is_active" data-value="0">Inactive</button>
        </div>

        <div class="row" style="gap:8px; align-items:center; flex-wrap:wrap;">
          <span class="pill ghost sm" style="cursor:default;">Priority</span>
          @foreach(['low','medium','high'] as $priority)
            <button type="button"
                    class="pill sm js-pill @if(request('priority')===$priority) active @else ghost @endif"
                    data-group="priority"
                    data-filter="priority"
                    data-value="{{ $priority }}">
              {{ ucfirst($priority) }}
            </button>
          @endforeach
        </div>

        <div class="row" style="gap:8px; align-items:center; flex-wrap:wrap;">
          <span class="pill ghost sm" style="cursor:default;">Status</span>
          @foreach($statuses as $status)
            <button type="button"
                    class="pill sm js-pill @if((string)request('status_id')===(string)$status->id) active @else ghost @endif"
                    data-group="status_id"
                    data-filter="status_id"
                    data-value="{{ $status->id }}">
              {{ $status->name }}
            </button>
          @endforeach
        </div>

        <div class="row" style="gap:8px; align-items:center; flex-wrap:wrap;">
          <span class="pill ghost sm" style="cursor:default;">Due</span>
          <button type="button" class="pill sm ghost js-range" data-range="overdue">Overdue</button>
          <button type="button" class="pill sm ghost js-range" data-range="this-week">Due this week</button>
          <button type="button" class="pill sm ghost js-range" data-range="this-month">Due this month</button>
        </div>

        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap:12px; align-items:end;">
          <div>
            <label>Search</label>
            <input id="searchInput" type="text" name="search" value="{{ request('search') }}" placeholder="Code or name">
          </div>
          <div>
            <label>Client</label>
            <select name="client_id">
              <option value="">All</option>
              @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Owner</label>
            <select name="owner_user_id">
              <option value="">All</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(request('owner_user_id') == $user->id)>{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Due from</label>
            <input type="date" name="due_from" value="{{ request('due_from') }}">
          </div>
          <div>
            <label>Due to</label>
            <input type="date" name="due_to" value="{{ request('due_to') }}">
          </div>
        </div>
      </div>

      <input type="hidden" name="status_id" value="{{ request('status_id') }}">
      <input type="hidden" name="priority" value="{{ request('priority') }}">
      <input type="hidden" name="is_active" value="{{ request('is_active') }}">

      <div style="margin-top:12px; display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
        <button type="button" class="btn secondary" id="clearAllBtn">Clear all</button>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button type="button" class="btn secondary" id="closeFilters2">Cancel</button>
          <button class="btn" type="submit">Apply</button>
        </div>
      </div>
    </form>
  </div>
@endsection

@push('scripts')
  <script>
    (function () {
      const modal = document.getElementById('filtersModal');
      const backdrop = document.getElementById('filtersBackdrop');
      const openBtn = document.getElementById('openFilters');
      const closeBtn = document.getElementById('closeFilters');
      const closeBtn2 = document.getElementById('closeFilters2');
      const form = document.getElementById('filterForm');
      if (!modal || !backdrop || !openBtn || !form) return;

      const open = () => { modal.classList.add('show'); backdrop.classList.add('show'); };
      const close = () => { modal.classList.remove('show'); backdrop.classList.remove('show'); };

      openBtn.addEventListener('click', open);
      closeBtn?.addEventListener('click', close);
      closeBtn2?.addEventListener('click', close);
      backdrop.addEventListener('click', close);
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

      const hiddenInputs = {
        status_id: form.querySelector('input[name="status_id"]'),
        priority: form.querySelector('input[name="priority"]'),
        is_active: form.querySelector('input[name="is_active"]'),
      };
      const dueFrom = form.querySelector('input[name="due_from"]');
      const dueTo = form.querySelector('input[name="due_to"]');

      const setActiveState = (group, value) => {
        document.querySelectorAll(`.js-pill[data-group="${group}"]`).forEach((b) => {
          const isActive = b.dataset.value === value && value !== '';
          b.classList.toggle('active', isActive);
          b.classList.toggle('ghost', !isActive);
        });
      };

      document.querySelectorAll('.js-pill').forEach((btn) => {
        btn.addEventListener('click', () => {
          const key = btn.dataset.filter;
          const val = btn.dataset.value;
          const target = hiddenInputs[key];
          if (!target) return;

          target.value = target.value === val ? '' : val;
          setActiveState(btn.dataset.group, target.value);
        });
      });

      const fmt = (d) => d.toISOString().slice(0, 10);
      const startOfWeek = (date) => {
        const d = new Date(date);
        const day = d.getDay() || 7;
        d.setDate(d.getDate() - (day - 1));
        d.setHours(0, 0, 0, 0);
        return d;
      };
      const endOfWeek = (date) => {
        const s = startOfWeek(date);
        const e = new Date(s);
        e.setDate(s.getDate() + 6);
        e.setHours(23, 59, 59, 999);
        return e;
      };

      document.querySelectorAll('.js-range').forEach((btn) => {
        btn.addEventListener('click', () => {
          if (!dueFrom || !dueTo) return;
          const today = new Date();
          const range = btn.dataset.range;
          if (range === 'overdue') {
            dueFrom.value = '';
            dueTo.value = fmt(today);
          } else if (range === 'this-week') {
            dueFrom.value = fmt(startOfWeek(today));
            dueTo.value = fmt(endOfWeek(today));
          } else if (range === 'this-month') {
            const s = new Date(today.getFullYear(), today.getMonth(), 1);
            const e = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            dueFrom.value = fmt(s);
            dueTo.value = fmt(e);
          }
        });
      });

      const clearAll = () => {
        Object.values(hiddenInputs).forEach(i => { if (i) i.value = ''; });
        ['search','client_id','owner_user_id','due_from','due_to'].forEach((name) => {
          const el = form.querySelector(`[name="${name}"]`);
          if (el) el.value = '';
        });
        form.submit();
      };

      document.getElementById('clearAllBtn')?.addEventListener('click', clearAll);
      document.getElementById('clearAllInline')?.addEventListener('click', clearAll);

      document.querySelectorAll('.js-clear').forEach((btn) => {
        btn.addEventListener('click', () => {
          const key = btn.dataset.clear;
          if (hiddenInputs[key]) hiddenInputs[key].value = '';

          if (key === 'dates') {
            if (dueFrom) dueFrom.value = '';
            if (dueTo) dueTo.value = '';
          } else {
            const el = form.querySelector(`[name="${key}"]`);
            if (el) el.value = '';
          }

          form.submit();
        });
      });

      // UX: focus search when opening filters.
      openBtn.addEventListener('click', () => {
        const input = document.getElementById('searchInput');
        setTimeout(() => input?.focus(), 0);
      });
    })();
  </script>
@endpush
