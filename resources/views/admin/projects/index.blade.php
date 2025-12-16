@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Projects</h1>
      <p class="muted">Filter by customer, status, owner, priority, or dates.</p>
    </div>
    @if(in_array(auth()->user()?->role?->name, ['Admin','PM']))
      <a class="btn" href="{{ route('admin.projects.create') }}">New project</a>
    @endif
  </div>

  <form method="GET" class="card grid two" style="align-items:flex-end;">
    <div>
      <label>Search</label>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Code or name">
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
      <label>Status</label>
      <select name="status_id">
        <option value="">All</option>
        @foreach($statuses as $status)
          <option value="{{ $status->id }}" @selected(request('status_id') == $status->id)>{{ $status->name }}</option>
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
      <label>Priority</label>
      <select name="priority">
        <option value="">All</option>
        @foreach(['low','medium','high'] as $priority)
          <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
        @endforeach
      </select>
    </div>
    <div class="grid two" style="gap:8px;">
      <div>
        <label>Due from</label>
        <input type="date" name="due_from" value="{{ request('due_from') }}">
      </div>
      <div>
        <label>Due to</label>
        <input type="date" name="due_to" value="{{ request('due_to') }}">
      </div>
    </div>
    <div>
      <label>Active</label>
      <select name="is_active">
        <option value="">All</option>
        <option value="1" @selected(request('is_active')==='1')>Active</option>
        <option value="0" @selected(request('is_active')==='0')>Inactive</option>
      </select>
    </div>
    <div style="align-self:flex-end;">
      <button class="btn secondary" type="submit">Apply</button>
    </div>
  </form>

  <div class="card" style="margin-top:16px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
      <div>
        <h2 style="margin:0;">Projects</h2>
        <p class="muted">Statuses, owners, and due dates at a glance.</p>
      </div>
      <span class="pill-tag">{{ $projects->total() }} total</span>
    </div>
    <div class="stacked">
      @forelse($projects as $project)
        <div style="padding:12px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; gap:16px; align-items:center;">
          <div>
            <div style="font-weight:700;">{{ $project->project_code }} — {{ $project->name }}</div>
            <div class="muted">{{ $project->client?->name ?? 'No client' }}</div>
            <div class="muted">Owner: {{ $project->owner?->name ?? 'Unassigned' }} | Status: {{ $project->status?->name ?? 'N/A' }}</div>
            <div class="muted">Due {{ optional($project->due_date)->format('Y-m-d') ?? 'N/A' }} | Priority: {{ ucfirst($project->priority) }}</div>
          </div>
          <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
            <span class="badge {{ $project->is_active ? 'green' : 'rose' }}">{{ $project->is_active ? 'Active' : 'Inactive' }}</span>
            <a class="btn secondary" href="{{ route('admin.projects.show', $project->id) }}">Open</a>
          </div>
        </div>
      @empty
        <div class="muted">No projects found.</div>
      @endforelse
    </div>
    <div style="margin-top:12px;">
      {{ $projects->links() }}
    </div>
  </div>
@endsection
