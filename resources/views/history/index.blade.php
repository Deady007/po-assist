@extends('layout')

@section('content')
  <h2>History (Last 50)</h2>

  @foreach($items as $it)
    <div class="card">
      <div><strong>{{ $it->type }}</strong> ({{ $it->tone }})</div>
      <div class="muted">
        Project: {{ $it->project?->name }} |
        {{ $it->created_at }}
      </div>
      <div style="margin-top:8px;">
        <a href="{{ route('history.show', $it->id) }}">Open</a>
      </div>
    </div>
  @endforeach
@endsection
