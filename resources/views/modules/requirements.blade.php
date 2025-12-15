@extends('layout')

@section('content')
  <h2>Requirements & RFP — {{ $project->name }}</h2>
  <p class="subhead">Capture requirements, track change requests, and keep RFP artifacts in Drive.</p>

  @if(!empty($warnings))
    <div class="card">
      <div class="section-title">Warnings</div>
      <ul class="muted">
        @foreach($warnings as $w)
          <li><strong>{{ $w['code'] }}</strong> ({{ $w['severity'] }}): {{ $w['message'] }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="grid two" style="margin-top:16px;">
    <div class="card stacked">
      <div class="section-title">Create requirement</div>
      <form id="reqForm">
        <input type="hidden" name="project_id" value="{{ $project->id }}">
        <label>Title</label>
        <input name="title" required>
        <label>Description</label>
        <textarea name="description" required></textarea>
        <label>Priority</label>
        <select name="priority" required>
          <option>HIGH</option><option>MEDIUM</option><option>LOW</option>
        </select>
        <label>Status</label>
        <select name="status" required>
          <option>NOT_STARTED</option><option>IN_PROGRESS</option><option>APPROVED</option><option>DELIVERED</option>
        </select>
        <button class="btn" type="submit" style="margin-top:10px;">Save</button>
      </form>
      <div id="reqStatus" class="muted"></div>
    </div>

    <div class="card stacked">
      <div class="section-title">Upload/link RFP</div>
      <form id="rfpUpload" enctype="multipart/form-data">
        <label>Title</label>
        <input name="title" required>
        <label>File</label>
        <input type="file" name="file" required>
        <button class="btn" type="submit" style="margin-top:8px;">Upload to Drive</button>
      </form>
      <div class="muted" style="margin-top:8px;">Existing RFP files:</div>
      <ul class="muted">
        @forelse($rfps as $d)
          <li>{{ $d->title }} @if($d->drive_web_view_link) <a href="{{ $d->drive_web_view_link }}" target="_blank">view</a>@endif</li>
        @empty
          <li>No RFP documents yet.</li>
        @endforelse
      </ul>
      <form id="rfpLink">
        <label>Title</label>
        <input name="title" required>
        <label>Drive File ID</label>
        <input name="drive_file_id" required>
        <button class="btn secondary" type="submit" style="margin-top:8px;">Link existing</button>
      </form>
      <div id="rfpStatus" class="muted"></div>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Requirements</div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr><th>Code</th><th>Title</th><th>Status</th><th>Priority</th><th>Change?</th></tr></thead>
        <tbody>
          @foreach($requirements as $r)
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:6px;">{{ $r->req_code }}</td>
              <td style="padding:6px;">{{ $r->title }}</td>
              <td style="padding:6px;">{{ $r->status }}</td>
              <td style="padding:6px;">{{ $r->priority }}</td>
              <td style="padding:6px;">{{ $r->is_change_request ? 'Yes' : 'No' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res) {
      if (!res.ok) return res.json().then(j => { throw new Error(j.errors?.[0]?.message || 'Request failed'); });
      return res.json();
    }
    function setStatus(el, msg, isError=false) {
      if (!el) return;
      el.textContent = msg;
      el.style.color = isError ? '#b91c1c' : '#5b6475';
    }
    document.getElementById('reqForm')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const form = e.target;
      const data = new FormData(form);
      setStatus(document.getElementById('reqStatus'), 'Saving...');
      fetch(`/api/projects/${projectId}/requirements`, { method:'POST', body:data })
        .then(handleResponse)
        .then(() => location.reload())
        .catch(err => setStatus(document.getElementById('reqStatus'), err.message, true));
    });
    document.getElementById('rfpUpload')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const form = e.target;
      const data = new FormData(form);
      setStatus(document.getElementById('rfpStatus'), 'Uploading...');
      fetch(`/api/projects/${projectId}/rfp-documents/upload`, { method:'POST', body:data })
        .then(handleResponse)
        .then(() => location.reload())
        .catch(err => setStatus(document.getElementById('rfpStatus'), err.message, true));
    });
    document.getElementById('rfpLink')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const payload = {
        title: e.target.title.value,
        drive_file_id: e.target.drive_file_id.value,
      };
      setStatus(document.getElementById('rfpStatus'), 'Linking...');
      fetch(`/api/projects/${projectId}/rfp-documents/link`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      }).then(handleResponse)
        .then(() => location.reload())
        .catch(err => setStatus(document.getElementById('rfpStatus'), err.message, true));
    });
  </script>
@endsection
