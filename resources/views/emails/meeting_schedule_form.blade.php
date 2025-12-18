@extends('layout')

@section('content')
  <h2>Meeting Schedule Email</h2>
  <p class="subhead">Share agenda, time, and logistics in a clean invitation.</p>

  <form class="card stacked" method="POST" action="{{ route('emails.meeting.generate') }}" data-loading-text="Generating schedule email...">
    @csrf

    <div class="row">
      <div class="col">
        <label>Project</label>
        @php $projectChoice = old('project_id', $selectedProjectId); @endphp
        <select name="project_id" required data-project-persist>
          <option value="">-- choose --</option>
          @foreach ($projects as $p)
            <option value="{{ $p->id }}" @if((string)$projectChoice === (string)$p->id) selected @endif>
              {{ $p->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col">
        @php $toneChoice = old('tone', 'formal'); @endphp
        <label>Tone</label>
        <select name="tone">
          <option value="formal" @if($toneChoice === 'formal') selected @endif>Formal</option>
          <option value="executive" @if($toneChoice === 'executive') selected @endif>Executive</option>
          <option value="neutral" @if($toneChoice === 'neutral') selected @endif>Neutral</option>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Meeting title</label>
        <input name="meeting_title" placeholder="Sprint 18 Planning / Kickoff" value="{{ old('meeting_title') }}">
        <span class="helper">Optional (AI can generate a title).</span>
      </div>
      <div class="col">
        <label>Meeting date & time</label>
        <input name="meeting_datetime" required placeholder="2025-12-15 10:00 AM UTC" value="{{ old('meeting_datetime') }}">
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Duration</label>
        <input name="duration" placeholder="45 minutes" value="{{ old('duration') }}">
        <span class="helper">Optional (defaults to 60 minutes).</span>
      </div>
      <div class="col">
        <label>Location or link</label>
        <input name="meeting_location_or_link" placeholder="Zoom: https://..." value="{{ old('meeting_location_or_link') }}">
        <span class="helper">Optional.</span>
      </div>
    </div>

    <label>Attendees</label>
    <textarea name="attendees" rows="3" placeholder="PM, Tech Lead, QA Lead, Design, Client Partner">{{ old('attendees') }}</textarea>
    <span class="helper">Optional (AI can infer key roles).</span>

    <label>Agenda topics</label>
    <textarea name="agenda_topics" rows="4" placeholder="- Objectives\n- Dependencies\n- Approvals needed">{{ old('agenda_topics') }}</textarea>
    <span class="helper">Optional (AI can propose agenda from project warnings).</span>

    <div class="row" style="align-items:center;">
      <div class="col">
        <span class="muted">We store the generated invite in history as MEETING_SCHEDULE.</span>
      </div>
      <div class="col" style="text-align:right;">
        <button class="btn" type="submit">Generate with Gemini</button>
      </div>
    </div>
  </form>
@endsection
