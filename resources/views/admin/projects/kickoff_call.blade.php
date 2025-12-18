@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Kick-off Call — {{ $project->name }}</h1>
      <p class="muted">UI draft with dummy data. Later: approved scope + timeline + kick-off document generation.</p>
    </div>
  </div>

  @include('admin.projects._subnav', ['project' => $project])

  <div class="grid two">
    <div class="card stacked">
      <div class="section-title">Kick-off summary (dummy)</div>
      <div class="grid two">
        <div>
          <div class="muted">Scheduled</div>
          <div style="font-weight:900;">{{ $kickoff['scheduled_at'] ?? '—' }}</div>
        </div>
        <div>
          <div class="muted">Client</div>
          <div style="font-weight:900;">{{ $kickoff['client'] ?? '—' }}</div>
        </div>
        <div>
          <div class="muted">Responsible</div>
          <div style="font-weight:900;">{{ $kickoff['responsible'] ?? '—' }}</div>
        </div>
      </div>

      <div style="margin-top:10px;">
        <div class="muted" style="font-weight:800; margin-bottom:6px;">Agenda</div>
        <ul class="muted" style="margin:0; padding-left:18px;">
          @foreach(($kickoff['agenda'] ?? []) as $a)
            <li>{{ $a }}</li>
          @endforeach
        </ul>
      </div>

      <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
        <button class="btn" type="button" disabled>Generate Kick-off Doc (future)</button>
        <button class="btn secondary" type="button" disabled>Mark scope approved (future)</button>
      </div>
    </div>

    <div class="card stacked">
      <div class="section-title">Deliverables (dummy)</div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Deliverable</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($deliverables as $d)
              @php
                $badge = match($d['status'] ?? '') {
                  'Draft' => 'blue',
                  'Planned' => 'amber',
                  'Done' => 'green',
                  default => 'amber',
                };
              @endphp
              <tr>
                <td style="font-weight:700;">{{ $d['name'] }}</td>
                <td><span class="badge {{ $badge }}">{{ $d['status'] }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="muted" style="margin-top:10px;">This will later pull from requirements + AI-generated docs.</div>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Timeline (dummy)</div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Phase</th>
            <th>From</th>
            <th>To</th>
            <th>Owner</th>
          </tr>
        </thead>
        <tbody>
          @foreach($timeline as $t)
            <tr>
              <td style="font-weight:800;">{{ $t['phase'] }}</td>
              <td>{{ $t['from'] }}</td>
              <td>{{ $t['to'] }}</td>
              <td>{{ $t['owner'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Approved scope (module-wise dummy)</div>
    <div class="grid two">
      @foreach($approvedScope as $group)
        <div class="card" style="box-shadow:none;">
          <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
            <div style="font-weight:900;">{{ $group['module'] }}</div>
            <span class="pill-tag">{{ count($group['items'] ?? []) }} items</span>
          </div>
          <ul class="muted" style="margin:10px 0 0; padding-left:18px;">
            @foreach(($group['items'] ?? []) as $it)
              <li>{{ $it }}</li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  </div>
@endsection
