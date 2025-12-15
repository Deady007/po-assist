@extends('layout')

@section('content')
  <h2>Project Drive: {{ $project->name }}</h2>
  <p class="subhead">Provision folders, upload artifacts, or link existing Google Drive files. Single admin, no auth required.</p>

  <div class="card stacked">
    <div class="row">
      <div class="col">
        <div class="section-title">Provision Drive structure</div>
        <p class="muted">Creates (or reuses) a project root and six phase folders. Idempotent and safe to re-run.</p>
        <button class="btn" id="provisionBtn">Provision Drive Folders</button>
        <div id="provisionStatus" class="muted" style="margin-top:10px;"></div>
      </div>
      <div class="col">
        <div class="section-title">Folder map</div>
        <div class="muted">
          <div><strong>Root:</strong>
            @if ($rootFolder)
              <a href="{{ $rootFolder->drive_web_view_link }}" target="_blank">{{ $rootFolder->drive_folder_id }}</a>
            @else
              Not provisioned
            @endif
          </div>
          <ul>
            @foreach ($phaseMap as $phaseKey => $folderName)
              @php $f = $folders[$phaseKey] ?? null; @endphp
              <li><strong>{{ $folderName }}</strong> — {{ $phaseKey }}:
                @if ($f && $f->drive_web_view_link)
                  <a href="{{ $f->drive_web_view_link }}" target="_blank">{{ $f->drive_folder_id }}</a>
                @else
                  not yet
                @endif
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="grid two" style="margin-top:16px;">
    <div class="card stacked">
      <div class="section-title">Upload a file</div>
      <form id="uploadForm" enctype="multipart/form-data">
        <label>Phase</label>
        <select name="phase_key" required>
          @foreach ($phaseMap as $phaseKey => $folderName)
            <option value="{{ $phaseKey }}">{{ $folderName }} ({{ $phaseKey }})</option>
          @endforeach
        </select>

        <label>Entity type</label>
        <input type="text" name="entity_type" placeholder="rfp_document, data_item, master_data_change, validation_report, generic" required>

        <label>Entity ID (optional)</label>
        <input type="number" name="entity_id" min="1" placeholder="123">

        <label>File</label>
        <input type="file" name="file" required>

        <button class="btn" type="submit" style="margin-top:10px;">Upload to Drive</button>
      </form>
      <div id="uploadStatus" class="muted" style="margin-top:10px;"></div>
    </div>

    <div class="card stacked">
      <div class="section-title">Link an existing Drive file</div>
      <form id="linkForm">
        <label>Phase</label>
        <select name="phase_key" required>
          @foreach ($phaseMap as $phaseKey => $folderName)
            <option value="{{ $phaseKey }}">{{ $folderName }} ({{ $phaseKey }})</option>
          @endforeach
        </select>

        <label>Entity type</label>
        <input type="text" name="entity_type" placeholder="generic" required>

        <label>Entity ID (optional)</label>
        <input type="number" name="entity_id" min="1" placeholder="123">

        <label>Drive file ID</label>
        <input type="text" name="drive_file_id" placeholder="1Abc123..." required>

        <button class="btn" type="submit" style="margin-top:10px;">Link Drive File</button>
      </form>
      <div id="linkStatus" class="muted" style="margin-top:10px;"></div>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Recent Drive files</div>
    @if ($files->isEmpty())
      <p class="muted">No files linked or uploaded yet.</p>
    @else
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="text-align:left; border-bottom:1px solid #e2e8f0;">
              <th style="padding:8px;">Phase</th>
              <th style="padding:8px;">Entity</th>
              <th style="padding:8px;">File</th>
              <th style="padding:8px;">MIME</th>
              <th style="padding:8px;">Uploaded</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($files as $file)
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px;">{{ $file->phase_key }}</td>
                <td style="padding:8px;">
                  <div>{{ $file->entity_type }}</div>
                  @if($file->entity_id)
                    <div class="muted">ID: {{ $file->entity_id }}</div>
                  @endif
                </td>
                <td style="padding:8px;">
                  @if($file->web_view_link)
                    <a href="{{ $file->web_view_link }}" target="_blank">{{ $file->file_name }}</a>
                  @else
                    {{ $file->file_name }}
                  @endif
                  <div class="muted">{{ $file->drive_file_id }}</div>
                </td>
                <td style="padding:8px;">{{ $file->mime_type }}</td>
                <td style="padding:8px;">{{ $file->uploaded_at?->format('Y-m-d H:i') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <script>
    const apiBase = '/api/projects/{{ $project->id }}/drive';

    const provisionBtn = document.getElementById('provisionBtn');
    const provisionStatus = document.getElementById('provisionStatus');
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const linkForm = document.getElementById('linkForm');
    const linkStatus = document.getElementById('linkStatus');

    function setStatus(el, message, isError = false) {
      if (!el) return;
      el.textContent = message;
      el.style.color = isError ? '#b91c1c' : '#5b6475';
    }

    function handleErrors(response) {
      if (!response.ok) {
        return response.json().then((body) => {
          const msg = body?.errors?.[0]?.message || 'Request failed';
          throw new Error(msg);
        }).catch(() => {
          throw new Error('Request failed');
        });
      }
      return response.json();
    }

    provisionBtn?.addEventListener('click', () => {
      setStatus(provisionStatus, 'Provisioning...');
      fetch(apiBase + '/provision', { method: 'POST' })
        .then(handleErrors)
        .then((data) => {
          setStatus(provisionStatus, 'Provisioned. Reloading...');
          setTimeout(() => window.location.reload(), 600);
        })
        .catch((err) => setStatus(provisionStatus, err.message, true));
    });

    uploadForm?.addEventListener('submit', (e) => {
      e.preventDefault();
      setStatus(uploadStatus, 'Uploading...');
      const formData = new FormData(uploadForm);
      fetch(apiBase + '/upload', {
        method: 'POST',
        body: formData,
      })
        .then(handleErrors)
        .then((data) => {
          setStatus(uploadStatus, 'Uploaded successfully. Reloading...');
          setTimeout(() => window.location.reload(), 600);
        })
        .catch((err) => setStatus(uploadStatus, err.message, true));
    });

    linkForm?.addEventListener('submit', (e) => {
      e.preventDefault();
      setStatus(linkStatus, 'Linking...');
      const payload = {
        phase_key: linkForm.phase_key.value,
        entity_type: linkForm.entity_type.value,
        entity_id: linkForm.entity_id.value || null,
        drive_file_id: linkForm.drive_file_id.value,
      };
      fetch(apiBase + '/link', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
        .then(handleErrors)
        .then((data) => {
          setStatus(linkStatus, 'Linked successfully. Reloading...');
          setTimeout(() => window.location.reload(), 600);
        })
        .catch((err) => setStatus(linkStatus, err.message, true));
    });
  </script>
@endsection
