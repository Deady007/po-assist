@extends('layout')

@section('content')
  <h2>Generated Product Update</h2>
  <p class="muted">
    Project: <strong>{{ $project->name }}</strong> |
    Created: {{ $artifact->created_at }}
  </p>

  <div class="card">
    <label>Subject</label>
    <textarea id="subject" rows="2">{{ $artifact->subject }}</textarea>
    <button type="button" onclick="copyFrom('subject')">Copy Subject</button>

    <label style="margin-top:16px;">Body</label>
    <textarea id="body" rows="18">{{ $artifact->body_text }}</textarea>
    <button type="button" onclick="copyFrom('body')">Copy Body</button>
  </div>

  <div class="card">
    <a href="{{ route('history.show', $artifact->id) }}">Open this in History</a>
  </div>

  <script>
    async function copyFrom(id) {
      const el = document.getElementById(id);
      const text = el.value;

      try {
        await navigator.clipboard.writeText(text);
        alert('Copied!');
      } catch (e) {
        // fallback
        el.select();
        document.execCommand('copy');
        alert('Copied!');
      }
    }
  </script>
@endsection
