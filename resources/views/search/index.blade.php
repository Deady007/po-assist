@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Search</h1>
      <p class="muted">Projects and customers</p>
    </div>
  </div>

  <form method="GET" class="card row" style="gap:12px; align-items:flex-end;">
    <div class="col">
      <label>Query</label>
      <input type="text" name="q" value="{{ $q }}" placeholder="Project code/name or customer name">
    </div>
    <div class="col" style="max-width:140px;">
      <button class="btn secondary" type="submit">Search</button>
    </div>
  </form>

  <div class="grid two" style="margin-top:16px;">
    <div class="card">
      <h3>Projects</h3>
      @forelse($projects as $project)
        <div style="padding:10px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between;">
          <div>
            <div><strong>{{ $project->project_code }}</strong> — {{ $project->name }}</div>
            <div class="muted">Client: {{ $project->client_name ?? 'N/A' }}</div>
          </div>
          <a class="btn secondary" href="{{ route('admin.projects.show', $project->id) }}">Open</a>
        </div>
      @empty
        <div class="muted">No projects found</div>
      @endforelse
    </div>
    <div class="card">
      <h3>Customers</h3>
      @forelse($customers as $customer)
        <div style="padding:10px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between;">
          <div>
            <div><strong>{{ $customer->name }}</strong></div>
            <div class="muted">{{ $customer->client_code }}</div>
          </div>
          <a class="btn secondary" href="{{ route('admin.clients.edit', $customer->id) }}">Open</a>
        </div>
      @empty
        <div class="muted">No customers found</div>
      @endforelse
    </div>
  </div>
@endsection
