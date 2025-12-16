@extends('layout')

@section('content')
  <div class="grid two">
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Export</h2>
      <p class="muted">Download CSV/XLSX for quick edits. Exports include related lookups (e.g., client codes).</p>
      <form method="POST" action="{{ route('admin.import-export.export') }}">
        @csrf
        <div class="grid two">
          <div>
            <label>Model</label>
            <select name="model" required>
              @foreach($supportedModels as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Format</label>
            <select name="format">
              <option value="csv">CSV</option>
              <option value="xlsx">XLSX</option>
            </select>
          </div>
        </div>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Download</button>
        </div>
      </form>
    </div>
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Import</h2>
      <p class="muted">Uploads validate each row; errors are stored in the import job log.</p>
      <form method="POST" action="{{ route('admin.import-export.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid two">
          <div>
            <label>Model</label>
            <select name="model" required>
              @foreach($supportedModels as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>File</label>
            <input type="file" name="file" accept=".csv,.xlsx" required>
          </div>
        </div>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Upload</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card" style="margin-top:16px;">
    <h2 style="margin:0 0 6px 0;">Recent Imports</h2>
    <p class="muted">Shows last 20 jobs with row error counts.</p>
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="text-align:left; border-bottom:1px solid var(--border);">
          <th style="padding:8px;">Model</th>
          <th style="padding:8px;">File</th>
          <th style="padding:8px;">Status</th>
          <th style="padding:8px;">Rows</th>
          <th style="padding:8px;">Errors</th>
          <th style="padding:8px;">Created</th>
        </tr>
      </thead>
      <tbody>
        @foreach($jobs as $job)
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:8px;">{{ $job->model_name }}</td>
            <td style="padding:8px;">{{ $job->file_name }}</td>
            <td style="padding:8px;">{{ $job->status }}</td>
            <td style="padding:8px;">{{ $job->total_rows }}</td>
            <td style="padding:8px;">{{ $job->error_count }}</td>
            <td style="padding:8px;">{{ $job->created_at?->toDayDateTimeString() }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
