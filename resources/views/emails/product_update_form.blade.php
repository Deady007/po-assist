@extends('layout')

@section('content')
  <h2>Product Update Email</h2>
  <p class="subhead">Crisp progress snapshot for your stakeholders.</p>

  <form class="card stacked" method="POST" action="{{ route('emails.product.generate') }}" data-loading-text="Generating with AI...">
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
        <span class="helper">Pre-selects from your last choice.</span>
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
        <label>Date</label>
        <input name="date" value="{{ old('date', date('Y-m-d')) }}">
        <span class="helper">Optional (defaults to today).</span>
      </div>
      <div class="col">
        <label>Topics for review meeting</label>
        <textarea name="review_topics" rows="3" placeholder="- Release readiness\n- Risks to go-live">{{ old('review_topics') }}</textarea>
        <span class="helper">Optional (leave blank to infer from DB warnings).</span>
      </div>
    </div>

    <label>Highlights (optional)</label>
    <textarea name="highlights" rows="3" placeholder="- Key milestone achieved\n- Stakeholder update">{{ old('highlights') }}</textarea>
    <span class="helper">Optional short bullets.</span>

    <label>Completed (one per line)</label>
    <textarea name="completed" rows="4" placeholder="- Shipped X\n- Closed Y">{{ old('completed') }}</textarea>
    <span class="helper">Optional (leave blank to infer from recent activity).</span>

    <label>In Progress (one per line)</label>
    <textarea name="in_progress" rows="4" placeholder="- Hardening\n- QA cycle">{{ old('in_progress') }}</textarea>
    <span class="helper">Optional.</span>

    <label>Risks / Blockers</label>
    <textarea name="risks" rows="3" placeholder="None">{{ old('risks') }}</textarea>
    <span class="helper">Optional.</span>

    <div class="row" style="align-items:center;">
      <div class="col">
        <span class="muted">We keep copies in history (no email is sent).</span>
      </div>
      <div class="col" style="text-align:right;">
        <button type="submit" class="btn">Generate with Gemini</button>
      </div>
    </div>
  </form>
@endsection
