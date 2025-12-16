@extends('layout')

@section('content')
  <div class="grid two">
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Email Templates</h2>
      <p class="muted">Scopes: global / client / project. Generation will pick the most specific match.</p>
      <div class="stacked">
        @foreach($templates as $template)
          <div style="border:1px solid var(--border); border-radius:10px; padding:10px;">
            <form method="POST" action="{{ route('admin.config.email_templates.update', $template->id) }}">
              @csrf
              @method('PUT')
              <div class="grid two">
                <div>
                  <label>Name</label>
                  <input name="name" value="{{ $template->name }}" required>
                </div>
                <div>
                  <label>Code</label>
                  <input name="code" value="{{ $template->code }}" required>
                </div>
              </div>
              <div class="grid two" style="margin-top:6px;">
                <div>
                  <label>Scope</label>
                  <select name="scope_type" required>
                    @foreach(['global','client','project'] as $scope)
                      <option value="{{ $scope }}" @selected($template->scope_type === $scope)>{{ ucfirst($scope) }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label>Scope Id</label>
                  <input name="scope_id" value="{{ $template->scope_id }}">
                  <span class="helper">Required for client/project scopes</span>
                </div>
              </div>
              <div style="margin-top:6px;">
                <label>Description</label>
                <textarea name="description">{{ $template->description }}</textarea>
              </div>
              <div style="margin-top:8px; display:flex; gap:8px;">
                <button class="btn secondary" type="submit">Update</button>
                <a class="btn ghost" href="#" onclick="event.preventDefault(); document.getElementById('delete-template-{{ $template->id }}').submit();">Delete</a>
              </div>
            </form>
            <form id="delete-template-{{ $template->id }}" method="POST" action="{{ route('admin.config.email_templates.destroy', $template->id) }}" onsubmit="return confirm('Delete template?');">
              @csrf
              @method('DELETE')
            </form>
          </div>
        @endforeach
      </div>
    </div>
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Add Template</h2>
      <p class="muted">Use codes from the email generator (PRODUCT_UPDATE, HR_UPDATE, etc.).</p>
      <form method="POST" action="{{ route('admin.config.email_templates.store') }}">
        @csrf
        <div class="stacked">
          <div>
            <label>Name</label>
            <input name="name" required placeholder="Product Update Email">
          </div>
          <div>
            <label>Code</label>
            <input name="code" required placeholder="PRODUCT_UPDATE">
          </div>
          <div class="grid two">
            <div>
              <label>Scope</label>
              <select name="scope_type">
                <option value="global">Global</option>
                <option value="client">Client</option>
                <option value="project">Project</option>
              </select>
            </div>
            <div>
              <label>Scope Id</label>
              <input name="scope_id" placeholder="Client/Project id">
            </div>
          </div>
          <div>
            <label>Description</label>
            <textarea name="description"></textarea>
          </div>
        </div>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Create</button>
        </div>
      </form>
      <div style="margin-top:12px;">
        <p class="muted">Clients</p>
        <div style="display:flex; flex-wrap:wrap; gap:6px;">
          @foreach($clients as $client)
            <span class="pill-tag">{{ $client->id }} · {{ $client->name }}</span>
          @endforeach
        </div>
        <p class="muted" style="margin-top:10px;">Projects</p>
        <div style="display:flex; flex-wrap:wrap; gap:6px; max-height:140px; overflow:auto;">
          @foreach($projects as $project)
            <span class="pill-tag">{{ $project->id }} · {{ $project->name }}</span>
          @endforeach
        </div>
      </div>
    </div>
  </div>
@endsection
