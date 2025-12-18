@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Requirements Management — {{ $project->name }}</h1>
      <p class="muted">UI draft with dummy data. Later: structured requirement intake + SRS generation via AI.</p>
    </div>
  </div>

  @include('admin.projects._subnav', ['project' => $project])

  <div class="grid two">
    <div class="card stacked">
      <div class="section-title">New requirement (dummy)</div>
      <div class="muted">This is UI-only for now. Save will be wired later.</div>
      <form>
        <div class="grid two">
          <div>
            <label>Requirement name</label>
            <input placeholder="e.g., Login with OTP">
          </div>
          <div>
            <label>Date received</label>
            <input type="date">
          </div>
        </div>
        <div class="grid two" style="margin-top:10px;">
          <div>
            <label>Reference</label>
            <input placeholder="e.g., Email thread / WhatsApp / Doc link">
          </div>
          <div>
            <label>Responsible person</label>
            <input placeholder="e.g., Client - John / PM - You">
          </div>
        </div>
        <div class="grid two" style="margin-top:10px;">
          <div>
            <label>Module</label>
            <select>
              <option>Authentication</option>
              <option>Dashboard</option>
              <option>Developer Assign</option>
              <option>Testing</option>
              <option>Delivery</option>
            </select>
          </div>
          <div>
            <label>Priority</label>
            <select>
              <option>HIGH</option>
              <option selected>MEDIUM</option>
              <option>LOW</option>
            </select>
          </div>
        </div>
        <div style="margin-top:10px;">
          <label>Notes</label>
          <textarea placeholder="Optional notes..."></textarea>
        </div>
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:10px;">
          <button class="btn secondary" type="button" disabled>Cancel</button>
          <button class="btn" type="button" disabled>Save (coming soon)</button>
        </div>
      </form>
    </div>

    <div class="card stacked">
      <div class="section-title">SRS Document</div>
      <div class="grid two">
        <div>
          <div class="muted">Status</div>
          <div style="font-weight:800;">{{ $srs['status'] ?? '—' }}</div>
        </div>
        <div>
          <div class="muted">Version</div>
          <div style="font-weight:800;">{{ $srs['version'] ?? '—' }}</div>
        </div>
        <div>
          <div class="muted">Last generated</div>
          <div style="font-weight:800;">{{ $srs['last_generated_at'] ?? '—' }}</div>
        </div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
        <button class="btn" type="button" disabled>Generate SRS via AI (future)</button>
        <button class="btn secondary" type="button" disabled>Download PDF (future)</button>
      </div>
      <div class="muted" style="margin-top:10px;">Planned output: structured SRS with modules, flows, roles, and acceptance criteria.</div>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Module-wise requirements (dummy)</div>
    @foreach($requirementsByModule as $moduleName => $items)
      <div class="card" style="box-shadow:none; margin-top:12px;">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
          <div style="font-weight:900;">{{ $moduleName }}</div>
          <span class="pill-tag">{{ count($items) }} items</span>
        </div>
        <div class="table-wrap" style="margin-top:10px;">
          <table class="table">
            <thead>
              <tr>
                <th>Received</th>
                <th>Requirement</th>
                <th>Reference</th>
                <th>Responsible</th>
                <th>Priority</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($items as $r)
                @php
                  $priorityClass = match($r['priority'] ?? '') {
                    'HIGH' => 'rose',
                    'MEDIUM' => 'amber',
                    default => 'blue',
                  };
                  $statusClass = match($r['status'] ?? '') {
                    'APPROVED' => 'green',
                    'IN_REVIEW' => 'blue',
                    'NEW' => 'amber',
                    default => 'amber',
                  };
                @endphp
                <tr>
                  <td>{{ $r['received_on'] ?? '—' }}</td>
                  <td style="font-weight:700;">{{ $r['name'] ?? '—' }}</td>
                  <td>{{ $r['reference'] ?? '—' }}</td>
                  <td>{{ $r['responsible'] ?? '—' }}</td>
                  <td><span class="badge {{ $priorityClass }}">{{ $r['priority'] ?? '—' }}</span></td>
                  <td><span class="badge {{ $statusClass }}">{{ $r['status'] ?? '—' }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endforeach
  </div>
@endsection
