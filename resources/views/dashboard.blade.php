@extends('layout')

@section('content')
  <h2>Dashboard</h2>
  <p class="muted">Select a project and generate your Product Update email.</p>

  <div class="card">
    <label>Select Project</label>
    <select id="projectSelect">
      <option value="">-- choose --</option>
      @foreach ($projects as $p)
        <option value="{{ $p->id }}">{{ $p->name }}@if($p->client_name) ({{ $p->client_name }}) @endif</option>
      @endforeach
    </select>

    <button onclick="goProductUpdate()">Generate Product Update</button>
  </div>

  <script>
    function goProductUpdate() {
      const id = document.getElementById('projectSelect').value;
      if (!id) return alert('Please select a project first.');
      window.location.href = "{{ route('emails.product.form') }}" + "?project_id=" + encodeURIComponent(id);
    }
  </script>
@endsection
