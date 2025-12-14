@extends('layout')

@section('content')
  <h2>History Item</h2>

  <div class="card">
    <div><strong>Type:</strong> {{ $artifact->type }}</div>
    <div><strong>Project:</strong> {{ $artifact->project?->name }}</div>
    <div><strong>Created:</strong> {{ $artifact->created_at }}</div>
  </div>

  <div class="card">
    <label>Subject</label>
    <textarea id="subject" rows="2">{{ $artifact->subject }}</textarea>
    <button type="button" onclick="copyFrom('subject')">Copy Subject</button>

    <label style="margin-top:16px;">Body</label>
    <textarea id="body" rows="18">{{ $artifact->body_text }}</textarea>
    <button type="button" onclick="copyFrom('body')">Copy Body</button>
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
