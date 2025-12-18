@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Review — {{ $project->name }}</h1>
      <p class="muted">UI draft with dummy data. Later: auto user manual + validation report + final sign-off.</p>
    </div>
  </div>

  @include('admin.projects._subnav', ['project' => $project])

  <div class="grid two">
    <div class="card stacked">
      <div class="section-title">Final review checklist (dummy)</div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Owner</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($checklist as $c)
              @php
                $badge = match($c['status'] ?? '') {
                  'DONE' => 'green',
                  'PLANNED' => 'amber',
                  'PENDING' => 'blue',
                  default => 'amber',
                };
              @endphp
              <tr>
                <td style="font-weight:700;">{{ $c['item'] }}</td>
                <td>{{ $c['owner'] }}</td>
                <td><span class="badge {{ $badge }}">{{ $c['status'] }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; margin-top:10px;">
        <button class="btn secondary" type="button" disabled>Mark complete (future)</button>
        <button class="btn" type="button" disabled>Request client sign-off (future)</button>
      </div>
    </div>

    <div class="card stacked">
      <div class="section-title">Documents (dummy)</div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Document</th>
              <th>Status</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            @foreach($docs as $d)
              @php
                $badge = match($d['status'] ?? '') {
                  'Draft' => 'blue',
                  'Done' => 'green',
                  default => 'amber',
                };
              @endphp
              <tr>
                <td style="font-weight:800;">{{ $d['name'] }}</td>
                <td><span class="badge {{ $badge }}">{{ $d['status'] }}</span></td>
                <td>{{ $d['updated_at'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
        <button class="btn" type="button" disabled>Generate user manual (future)</button>
        <button class="btn secondary" type="button" disabled>Generate validation report (future)</button>
        <button class="btn secondary" type="button" disabled>Download package (future)</button>
      </div>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Release readiness (dummy)</div>
    <div class="muted">Once testing is approved, this module will guide final review and delivery artifacts.</div>
    <div class="grid two" style="margin-top:10px;">
      <div class="card" style="box-shadow:none;">
        <div style="font-weight:900; margin-bottom:4px;">Go-live checklist</div>
        <ul class="muted" style="margin:0; padding-left:18px;">
          <li>Environment ready</li>
          <li>Backups confirmed</li>
          <li>Credentials shared</li>
          <li>Rollback plan prepared</li>
        </ul>
      </div>
      <div class="card" style="box-shadow:none;">
        <div style="font-weight:900; margin-bottom:4px;">Handover</div>
        <ul class="muted" style="margin:0; padding-left:18px;">
          <li>User manual delivered</li>
          <li>Training completed</li>
          <li>Support window agreed</li>
          <li>Final sign-off recorded</li>
        </ul>
      </div>
    </div>
  </div>
@endsection
