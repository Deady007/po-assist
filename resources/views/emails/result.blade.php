@extends('layout')

@section('content')
  <h2>{{ $heading ?? 'Generated Email' }}</h2>
  <p class="subhead">Project: <strong>{{ $project->name }}</strong> | {{ $artifact->created_at }}</p>

  <div class="card stacked">
    <div class="row" style="align-items:center;">
      <div class="col">
        <span class="badge blue">{{ $typeLabel ?? $artifact->type }}</span>
        <span class="pill-tag">Tone: {{ $artifact->tone }}</span>
      </div>
      <div class="col" style="text-align:right;">
        <a class="btn secondary" href="{{ route('history.show', $artifact->id) }}">View in history</a>
      </div>
    </div>

    <label>Subject</label>
    <textarea id="subject" rows="2" readonly>{{ $artifact->subject }}</textarea>
    <label>Body</label>
    <textarea id="body" rows="16" readonly>{{ $artifact->body_text }}</textarea>

    <div class="row" style="justify-content:flex-end;">
      <button class="btn secondary" type="button" onclick="copyFrom('subject')">Copy Subject</button>
      <button class="btn secondary" type="button" onclick="copyFrom('body')">Copy Body</button>
    </div>
  </div>

  <script>
    async function copyFrom(id) {
      const el = document.getElementById(id);
      const text = el.value;

      try {
        await navigator.clipboard.writeText(text);
        alert('Copied!');
      } catch (e) {
        el.select();
        document.execCommand('copy');
        alert('Copied!');
      }
    }
  </script>
@endsection
