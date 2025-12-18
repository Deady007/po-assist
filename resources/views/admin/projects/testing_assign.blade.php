@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Testing Assign — {{ $project->name }}</h1>
      <p class="muted">UI draft with dummy data. Later: AI-generated test tasks + feedback loop to Developer Assign.</p>
    </div>
  </div>

  @include('admin.projects._subnav', ['project' => $project])

  <div class="card stacked">
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
      <div>
        <div class="section-title" style="margin-bottom:4px;">QA board summary</div>
        <div class="muted">Dummy counts by status.</div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        @foreach(['TODO' => 'amber', 'IN_PROGRESS' => 'blue', 'BLOCKED' => 'rose', 'DONE' => 'green'] as $key => $color)
          @php $count = (int) ($statusSummary[$key] ?? 0); @endphp
          <span class="badge {{ $color }}">{{ $key }}: {{ $count }}</span>
        @endforeach
      </div>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
      <button class="btn" type="button" disabled>Generate test tasks via AI (future)</button>
      <button class="btn secondary" type="button" disabled>Assign testers (future)</button>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Testing tasks (dummy)</div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Module</th>
            <th>Page</th>
            <th>Task</th>
            <th>Assignee</th>
            <th>Status</th>
            <th>Linked dev task</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($testingBoard as $row)
            @php
              $badge = match($row['status'] ?? '') {
                'DONE' => 'green',
                'BLOCKED' => 'rose',
                'IN_PROGRESS' => 'blue',
                default => 'amber',
              };
            @endphp
            <tr>
              <td style="font-weight:800;">{{ $row['module'] }}</td>
              <td>{{ $row['page'] }}</td>
              <td>{{ $row['task'] }}</td>
              <td>{{ $row['assignee'] }}</td>
              <td><span class="badge {{ $badge }}">{{ $row['status'] }}</span></td>
              <td><span class="pill-tag">{{ $row['linked_dev_task'] }}</span></td>
              <td>
                <button class="btn secondary" type="button" disabled>Send back to dev</button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection
