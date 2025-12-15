@extends('layout')

@section('content')
  <h2>HR End-of-Day Update</h2>
  <p class="subhead">Non-technical daily signal for leadership and people ops.</p>

  <form class="card stacked" method="POST" action="{{ route('emails.hr.generate') }}" data-loading-text="Generating HR update...">
    @csrf

    <div class="row">
      <div class="col">
        @php $projectChoice = old('project_id', $selectedProjectId); @endphp
        <label>Project</label>
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
        <select name="tone" required>
          <option value="formal" @if($toneChoice === 'formal') selected @endif>Formal</option>
          <option value="executive" @if($toneChoice === 'executive') selected @endif>Executive</option>
          <option value="neutral" @if($toneChoice === 'neutral') selected @endif>Neutral</option>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>Date</label>
        <input name="date" required value="{{ old('date', date('Y-m-d')) }}">
      </div>
      <div class="col">
        <label>Status per project (Green/Amber/Red)</label>
        <textarea name="status_per_project" rows="3" required placeholder="Project X: Green - on plan\nProject Y: Amber - resourcing gap">{{ old('status_per_project') }}</textarea>
      </div>
    </div>

    <label>Projects summary (plain language)</label>
    <textarea name="projects_summary" rows="4" required placeholder="High-level wins, milestones, or blockers.">{{ old('projects_summary') }}</textarea>

    <label>People or timeline risks</label>
    <textarea name="people_or_timeline_risks" rows="3" required placeholder="- Hiring backfill in progress\n- Client approvals delayed">{{ old('people_or_timeline_risks') }}</textarea>

    <label>Tomorrow's plan</label>
    <textarea name="tomorrow_plan" rows="3" required placeholder="- Close UAT feedback\n- Kick off onboarding for new PM">{{ old('tomorrow_plan') }}</textarea>

    <div class="row" style="align-items:center;">
      <div class="col">
        <span class="muted">We keep a copy in history as HR_UPDATE (no email is sent).</span>
      </div>
      <div class="col" style="text-align:right;">
        <button class="btn" type="submit">Generate with Gemini</button>
      </div>
    </div>
  </form>
@endsection
