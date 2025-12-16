@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Emails — {{ $project->name }}</h1>
      <p class="muted">Phase 4 will deepen this. For now, view scoped templates and recent outputs.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
      <a class="btn secondary" href="{{ route('admin.projects.show', $project->id) }}">Overview</a>
      <a class="btn secondary" href="{{ route('admin.projects.workflow', $project->id) }}">Workflow</a>
      <a class="btn secondary" href="{{ route('admin.projects.tasks', $project->id) }}">Tasks</a>
    </div>
  </div>

  <div class="card stacked">
    <div class="section-title">Templates in scope</div>
    <p class="muted">Global, client, and project-scoped templates listed. Generation enhancements stay in Phase 4.</p>
    <div class="stacked">
      @forelse($templates as $template)
        <div style="padding:8px 0; border-bottom:1px solid var(--border);">
          <div style="font-weight:700;">{{ $template->name }} <span class="muted">({{ $template->code }})</span></div>
          <div class="muted">Scope: {{ ucfirst($template->scope_type) }} @if($template->scope_type !== 'global') #{{ $template->scope_id }} @endif</div>
        </div>
      @empty
        <div class="muted">No templates in scope.</div>
      @endforelse
    </div>
  </div>

  <div class="card stacked" style="margin-top:12px;">
    <div class="section-title">Email log</div>
    <p class="muted">Recent generated emails linked to this project.</p>
    <div class="stacked">
      @forelse($emailLogs as $log)
        <div style="padding:10px 0; border-bottom:1px solid var(--border);">
          <div style="font-weight:700;">{{ $log->template?->name ?? 'Template' }} <span class="muted">({{ $log->generated_at->toDayDateTimeString() }})</span></div>
          <div class="muted">Subject: {{ $log->subject ?? 'N/A' }}</div>
          <div class="muted">Scope: {{ ucfirst($log->scope_type ?? 'project') }}</div>
        </div>
      @empty
        <div class="muted">No email activity yet.</div>
      @endforelse
    </div>
  </div>
@endsection
