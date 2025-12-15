@extends('layout')

@section('content')
  <h2>Google Drive OAuth Callback</h2>
  <p class="subhead">Copy the refresh token below and place it into <code>GOOGLE_DRIVE_REFRESH_TOKEN</code> in your <code>.env</code>.</p>

  @if ($error)
    <div class="card" style="border-color:#fecdd3;">
      <div class="section-title">Authorization failed</div>
      <p class="muted">{{ $error }}</p>
      <a class="btn secondary" href="{{ route('drive.oauth.start') }}">Try again</a>
    </div>
  @elseif ($refreshToken)
    <div class="card stacked">
      <div class="section-title">Refresh token received</div>
      <p class="muted">Paste this into your environment file, then restart the app.</p>
      <textarea readonly style="min-height:80px;">{{ $refreshToken }}</textarea>
      <div class="pill-tag">Next steps</div>
      <ul class="muted">
        <li>Update <code>.env</code>: <code>GOOGLE_DRIVE_REFRESH_TOKEN={{ $refreshToken }}</code></li>
        <li>Optional: set <code>GOOGLE_DRIVE_ROOT_FOLDER_ID</code> to pin projects under a shared folder.</li>
        <li>Provision folders via UI or <code>php artisan drive:provision</code>.</li>
      </ul>
    </div>
  @else
    <div class="card">
      <p class="muted">No refresh token was returned. Ensure you forced consent (<code>prompt=consent</code>) and granted offline access.</p>
      <a class="btn secondary" href="{{ route('drive.oauth.start') }}">Retry</a>
    </div>
  @endif
@endsection
