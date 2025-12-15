@extends('layout')

@section('content')
  <h2>Google Drive Connection</h2>
  <p class="subhead">Single admin OAuth2 flow. Connect once to generate a refresh token for the system.</p>

  <div class="card stacked">
    <div class="section-title">Authorize PO-Assist</div>
    <p class="muted">We request Drive access using the configured scope (default: drive.file). After consent, copy the refresh token into <code>.env</code>.</p>
    <div class="row" style="align-items:center;">
      <div class="col">
        <button class="btn" onclick="window.location.href='{{ route('drive.oauth.start') }}'">Connect Google Drive</button>
      </div>
      <div class="col">
        <div class="pill-tag">Redirect URI</div>
        <div class="muted">{{ config('services.google_drive.redirect_uri') }}</div>
      </div>
    </div>
  </div>

  <div class="card stacked">
    <div class="section-title">Setup checklist</div>
    <ul class="muted">
      <li>Create OAuth client (type: Web) in Google Cloud, add redirect URI above.</li>
      <li>Set <code>GOOGLE_DRIVE_CLIENT_ID</code> and <code>GOOGLE_DRIVE_CLIENT_SECRET</code> in <code>.env</code>.</li>
      <li>Click Connect, approve consent, copy the refresh token shown on callback.</li>
      <li>Paste token into <code>GOOGLE_DRIVE_REFRESH_TOKEN</code>, then use the project Drive page to provision folders.</li>
    </ul>
  </div>
@endsection
