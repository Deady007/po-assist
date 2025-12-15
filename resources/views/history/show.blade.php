@extends('layout')

@section('content')
  <h2>History Detail</h2>
  <p class="subhead">Project: <strong>{{ $artifact->project?->name }}</strong> | {{ $artifact->created_at }}</p>

  <div class="card stacked">
    @php
      $colors = [
        'PRODUCT_UPDATE' => 'blue',
        'MEETING_SCHEDULE' => 'green',
        'MOM_FINAL' => 'amber',
        'HR_UPDATE' => 'rose',
      ];
      $badge = $colors[$artifact->type] ?? 'blue';
    @endphp

    <div class="row" style="align-items:center;">
      <div class="col">
        <span class="badge {{ $badge }}">{{ $artifact->type }}</span>
        <span class="pill-tag">Tone: {{ $artifact->tone }}</span>
      </div>
      <div class="col" style="text-align:right;">
        <div class="muted">Created {{ $artifact->created_at }}</div>
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
