@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>{{ $model->name }} <span class="muted">({{ $model->project_code ?? 'TBD' }})</span></h1>
      <p class="muted">
        Client: {{ $model->client?->name ?? 'Unassigned' }} |
        Status: {{ $model->status?->name ?? 'Default' }} |
        Due: {{ optional($model->due_date)->toDateString() ?? 'N/A' }}
      </p>
    </div>
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <span class="badge {{ $model->is_active ? 'green' : 'rose' }}">{{ $model->is_active ? 'Active' : 'Inactive' }}</span>
      <a class="btn secondary" href="{{ route('admin.projects.index') }}">All projects</a>
      <a class="btn secondary" href="{{ route('admin.projects.tasks', $model->id) }}">Tasks</a>
      <a class="btn" href="{{ route('admin.projects.workflow', $model->id) }}">Workflow</a>
      @if(in_array($role, ['Admin','PM']))
        <a class="btn secondary" href="{{ route('admin.projects.edit', $model->id) }}">Edit</a>
      @endif
    </div>
  </div>

  <div class="card stacked">
    <div class="grid two">
      <div>
        <div class="muted">Owner</div>
        <div style="font-weight:700;">{{ $model->owner?->name ?? 'Unassigned' }}</div>
      </div>
      <div>
        <div class="muted">Status</div>
        <div>{{ $model->status?->name ?? 'Default' }}</div>
      </div>
      <div>
        <div class="muted">Dates</div>
        <div>{{ optional($model->start_date)->toDateString() ?? 'N/A' }} → {{ optional($model->due_date)->toDateString() ?? 'N/A' }}</div>
      </div>
      <div>
        <div class="muted">Priority</div>
        <div>{{ ucfirst(strtolower($model->priority ?? 'medium')) }}</div>
      </div>
    </div>
    <div>
      <div class="muted">Description</div>
      <div>{{ $model->description ?? 'No description yet.' }}</div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="pill-tag" href="{{ route('admin.projects.show', $model->id) }}">Overview</a>
      <a class="pill-tag" href="{{ route('admin.projects.workflow', $model->id) }}">Workflow</a>
      <a class="pill-tag" href="{{ route('admin.projects.tasks', $model->id) }}">Tasks</a>
      <a class="pill-tag" href="{{ route('admin.projects.emails', $model->id) }}">Emails</a>
    </div>
  </div>

  <div class="grid two" style="margin-top:16px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
        <h3 style="margin:0;">Team</h3>
        <span class="pill-tag">{{ $model->team->count() }} members</span>
      </div>
      @if(in_array($role, ['Admin','PM']))
        <form method="POST" action="{{ route('admin.projects.team.store', $model->id) }}" style="margin-bottom:12px;">
          @csrf
          <div class="grid two">
            <div>
              <label>Add member</label>
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
          <div style="margin-top:10px;">
            <button class="btn" type="submit">Add to team</button>
          </div>
        </form>
      @endif
      <div class="stacked">
        @forelse($model->team as $member)
          <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
            <div>
              <div style="font-weight:700;">{{ $member->user?->name }}</div>
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
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0;">Recent email activity</h3>
        <a class="pill-tag" href="{{ route('admin.projects.emails', $model->id) }}">Emails tab</a>
      </div>
      <p class="muted">Phase 4 will add full email workflows. Existing logs stay visible.</p>
      <div class="stacked">
        @forelse($emailLogs->take(5) as $log)
          <div style="padding:8px 0; border-bottom:1px solid var(--border);">
            <div style="font-weight:700;">{{ $log->template?->name ?? 'Template' }} <span class="muted">({{ $log->generated_at->toDayDateTimeString() }})</span></div>
            <div class="muted">Subject: {{ $log->subject ?? 'N/A' }}</div>
          </div>
        @empty
          <div class="muted">No email activity yet.</div>
        @endforelse
      </div>
      <div>
        <a class="btn secondary" href="{{ route('admin.projects.emails', $model->id) }}">Open emails</a>
      </div>
    </div>
  </div>
@endsection
