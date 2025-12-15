@extends('layout')

@section('content')
  <h2>History</h2>
  <p class="subhead">Grouped by project and email type (latest first).</p>

  @if($grouped->isEmpty())
    <div class="card">
      <div class="muted">Nothing yet. Generate any email to see it here.</div>
    </div>
  @endif

  @foreach($grouped as $projectName => $types)
    <div class="card stacked" style="margin-bottom:16px;">
      <div class="section-title">{{ $projectName }}</div>
      @foreach($types as $type => $items)
        @php
          $colors = [
            'PRODUCT_UPDATE' => 'blue',
            'MEETING_SCHEDULE' => 'green',
            'MOM_FINAL' => 'amber',
            'HR_UPDATE' => 'rose',
          ];
          $badge = $colors[$type] ?? 'blue';
        @endphp
        <div class="row" style="align-items:center; margin-top:8px;">
          <div class="col">
            <span class="badge {{ $badge }}">{{ $type }}</span>
            <span class="muted">{{ $items->count() }} saved</span>
          </div>
        </div>

        @foreach($items as $it)
          <div class="card stacked" style="border:1px dashed #e5e7eb; margin-top:8px;">
            <div class="row" style="align-items:center;">
              <div class="col">
                <div><strong>{{ $it->subject ?: 'No subject - body only' }}</strong></div>
                <div class="muted">{{ \Illuminate\Support\Str::limit($it->body_text, 120) }}</div>
                <div class="muted">Tone: {{ $it->tone }} | {{ $it->created_at }}</div>
              </div>
              <div class="col" style="text-align:right;">
                <a class="btn secondary" href="{{ route('history.show', $it->id) }}">Open</a>
              </div>
            </div>
          </div>
        @endforeach
      @endforeach
    </div>
  @endforeach
@endsection
