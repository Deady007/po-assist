@extends('layout')

@section('content')
@php
  $remaining = null;
  if ($model->start_date && $model->due_date) {
      $remaining = \Carbon\Carbon::parse($model->start_date)->diffInDays(\Carbon\Carbon::parse($model->due_date));
  }
@endphp

<div class="page-head">
  <div>
    <h1>{{ $model->name }} <span class="muted">({{ $model->project_code ?? 'TBD' }})</span></h1>
    <p class="muted">
      Client: {{ $model->client?->name ?? 'Unassigned' }}
      • Owner: {{ $model->owner?->name ?? 'Unassigned' }}
      • Status: {{ $model->status?->name ?? 'Default' }}
      • Priority: {{ ucfirst(strtolower($model->priority ?? 'medium')) }}
    </p>
  </div>
  <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <span class="pill {{ $model->is_active ? 'active' : 'ghost' }} sm">{{ $model->is_active ? 'Active' : 'Inactive' }}</span>
    @if(in_array($role, ['Admin','PM']))
      <a class="btn" href="{{ route('admin.projects.edit', $model->id) }}">Edit project</a>
    @endif
  </div>
</div>

@include('admin.projects._subnav', ['project' => $model, 'role' => $role])

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
  <div class="card">
    <div class="muted" style="font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase;">Dates</div>
    <div style="font-weight:900; font-size:16px; margin-top:6px;">
      {{ optional($model->start_date)->toDateString() ?? 'N/A' }} → {{ optional($model->due_date)->toDateString() ?? 'N/A' }}
    </div>
    @if($remaining !== null)
      <div class="muted" style="margin-top:4px;">{{ $remaining }} days total</div>
    @endif
  </div>

  <div class="card">
    <div class="muted" style="font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase;">Team</div>
    <div style="font-weight:900; font-size:16px; margin-top:6px;">{{ $model->team->count() }} members</div>
    <div class="muted" style="margin-top:4px;">Roles tracked per project.</div>
  </div>

  <div class="card">
    <div class="muted" style="font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase;">Email activity</div>
    <div style="font-weight:900; font-size:16px; margin-top:6px;">{{ $emailLogs->count() }} logs</div>
    <div class="muted" style="margin-top:4px;">Recent generated outputs.</div>
  </div>

  <div class="card">
    <div class="muted" style="font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase;">Due date</div>
    <div style="font-weight:900; font-size:16px; margin-top:6px;">{{ optional($model->due_date)->toDateString() ?? 'N/A' }}</div>
    <div class="muted" style="margin-top:4px;">Keep modules aligned to this target.</div>
  </div>
</div>

<div class="card stacked" style="margin-top:16px;">
  <div class="section-title">Description</div>
  <div class="muted">{{ $model->description ?? 'No description yet.' }}</div>
</div>

<div class="grid two" style="margin-top:16px;">
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
      <h3 style="margin:0;">Team</h3>
      @if(in_array($role, ['Admin','PM']))
        <button class="btn secondary" type="button" id="openTeamModal">Add team member</button>
      @endif
    </div>
    <div class="stacked" style="margin-top:12px;">
      @forelse($model->team as $member)
        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); gap:12px;">
          <div>
            <div style="font-weight:800;">{{ $member->user?->name }}</div>
            <div class="muted">{{ $member->role_in_project ?? 'Contributor' }}</div>
          </div>
          @if(in_array($role, ['Admin','PM']))
            <form method="POST" action="{{ route('admin.projects.team.destroy', [$model->id, $member->id]) }}" onsubmit="return confirm('Remove team member?')">
              @csrf
              @method('DELETE')
              <button class="btn secondary" type="submit">Remove</button>
            </form>
          @endif
        </div>
      @empty
        <div class="muted">No team members yet.</div>
      @endforelse
    </div>
  </div>

  <div class="card stacked">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
      <h3 style="margin:0;">Recent email activity</h3>
      <a class="pill ghost sm" href="{{ route('admin.projects.emails', $model->id) }}">Open emails</a>
    </div>
    <p class="muted">Recent generated emails linked to this project.</p>
    <div class="stacked">
      @forelse($emailLogs->take(5) as $log)
        <div style="padding:8px 0; border-bottom:1px solid var(--border);">
          <div style="font-weight:800;">{{ $log->template?->name ?? 'Template' }} <span class="muted">({{ $log->generated_at->toDayDateTimeString() }})</span></div>
          <div class="muted">Subject: {{ $log->subject ?? 'N/A' }}</div>
        </div>
      @empty
        <div class="muted">No email activity yet.</div>
      @endforelse
    </div>
  </div>
</div>

@if(in_array($role, ['Admin','PM']))
  <div class="modal-backdrop" id="teamModalBackdrop"></div>
  <div class="modal" id="teamModal" role="dialog" aria-modal="true" aria-labelledby="teamModalTitle">
    <header>
      <div>
        <div class="section-title" id="teamModalTitle" style="margin:0;">Add team member</div>
        <p class="muted" style="margin:4px 0 0;">Select user and set project role.</p>
      </div>
      <button class="btn ghost" type="button" id="closeTeamModal">Close</button>
    </header>
    <form method="POST" action="{{ route('admin.projects.team.store', $model->id) }}">
      @csrf
      <div class="grid two">
        <div>
          <label>Select user</label>
          <select name="user_id" required>
            <option value="">Select user</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role?->name ?? 'No role' }})</option>
            @endforeach
          </select>
        </div>
        <div>
          <label>Role in project</label>
          <input name="role_in_project" placeholder="e.g., Tech Lead">
        </div>
      </div>
      <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px;">
        <button class="btn secondary" type="button" id="closeTeamModal2">Cancel</button>
        <button class="btn" type="submit">Add team member</button>
      </div>
    </form>
  </div>
@endif

<script>
  (function() {
    const modal = document.getElementById('teamModal');
    const backdrop = document.getElementById('teamModalBackdrop');
    const openBtn = document.getElementById('openTeamModal');
    const closeBtns = [document.getElementById('closeTeamModal'), document.getElementById('closeTeamModal2')];

    if (!modal || !backdrop || !openBtn) return;

    const open = () => { modal.classList.add('show'); backdrop.classList.add('show'); };
    const close = () => { modal.classList.remove('show'); backdrop.classList.remove('show'); };

    openBtn.addEventListener('click', open);
    closeBtns.forEach(btn => btn?.addEventListener('click', close));
    backdrop.addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  })();
</script>
@endsection
