@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Data Collection / Management — {{ $project->name }}</h1>
      <p class="muted">UI draft with dummy data. Later: Drive integration + auto folders + upload tracking.</p>
    </div>
  </div>

  @include('admin.projects._subnav', ['project' => $project])

  <div class="grid two">
    <div class="card stacked">
      <div class="section-title">Drive connection (future)</div>
      <p class="muted">This module will create folders automatically and track every file received.</p>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button class="btn" type="button" disabled>Connect Drive (future)</button>
        <button class="btn secondary" type="button" disabled>Create project folders (future)</button>
      </div>
      <div class="muted" style="margin-top:10px;">Planned: permission-aware folder structure + audit trail.</div>
    </div>

    <div class="card stacked">
      <div class="section-title">Upload / log item (dummy)</div>
      <form>
        <label>Item name</label>
        <input placeholder="e.g., Logo ZIP / Sample data / Credentials">
        <div class="grid two" style="margin-top:10px;">
          <div>
            <label>Received date</label>
            <input type="date">
          </div>
          <div>
            <label>From</label>
            <input placeholder="Client / Client IT / Vendor">
          </div>
        </div>
        <div class="grid two" style="margin-top:10px;">
          <div>
            <label>Type</label>
            <select>
              <option>PDF</option>
              <option>ZIP</option>
              <option>XLSX</option>
              <option>Image</option>
              <option>Credentials</option>
            </select>
          </div>
          <div>
            <label>Status</label>
            <select>
              <option>RECEIVED</option>
              <option>PENDING</option>
              <option>NEED_CLARIFICATION</option>
            </select>
          </div>
        </div>
        <div style="margin-top:10px;">
          <label>Notes</label>
          <textarea placeholder="Optional notes..."></textarea>
        </div>
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:10px;">
          <button class="btn" type="button" disabled>Save log (coming soon)</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Folder plan (dummy)</div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Folder</th>
            <th>Path</th>
            <th>Status</th>
            <th>Last activity</th>
          </tr>
        </thead>
        <tbody>
          @foreach($folders as $f)
            @php
              $badge = match($f['status'] ?? '') {
                'Created' => 'green',
                'Planned' => 'amber',
                default => 'amber',
              };
            @endphp
            <tr>
              <td style="font-weight:800;">{{ $f['name'] }}</td>
              <td>{{ $f['path'] }}</td>
              <td><span class="badge {{ $badge }}">{{ $f['status'] }}</span></td>
              <td>{{ $f['last_activity'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Data intake log (dummy)</div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Received</th>
            <th>Item</th>
            <th>Type</th>
            <th>From</th>
            <th>Status</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          @foreach($intakeLog as $row)
            @php
              $badge = match($row['status'] ?? '') {
                'RECEIVED' => 'green',
                'PENDING' => 'amber',
                default => 'amber',
              };
            @endphp
            <tr>
              <td>{{ $row['received_on'] }}</td>
              <td style="font-weight:700;">{{ $row['item'] }}</td>
              <td>{{ $row['type'] }}</td>
              <td>{{ $row['from'] }}</td>
              <td><span class="badge {{ $badge }}">{{ $row['status'] }}</span></td>
              <td>{{ $row['notes'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection
