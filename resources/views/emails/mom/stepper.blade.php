@extends('layout')

@section('content')
  <h2>Minutes of Meeting Pipeline</h2>
  <p class="subhead">Draft raw notes, refine them, then generate the final MoM email with copy buttons.</p>

  <div class="stepper">
    <div class="step {{ $draft ? 'completed' : 'active' }}">
      <div class="step-circle">1</div><div>Draft</div>
    </div>
    <div class="step {{ $refined ? 'completed' : ($draft ? 'active' : '') }}">
      <div class="step-circle">2</div><div>Refine</div>
    </div>
    <div class="step {{ $final ? 'completed' : ($refined ? 'active' : '') }}">
      <div class="step-circle">3</div><div>Final Email</div>
    </div>
  </div>

  {{-- Step 1: Draft --}}
  <div class="card stacked" style="margin-bottom:14px;">
    <div class="section-title">Step 1 - Draft MoM</div>

    @if (!$draft)
      <form method="POST" action="{{ route('emails.mom.draft.generate') }}" data-loading-text="Drafting MoM...">
        @csrf
        <div class="row">
          <div class="col">
            @php $projectChoice = old('project_id', $selectedProjectId); @endphp
            <label>Project</label>
            <select name="project_id" required data-project-persist>
              <option value="">-- choose --</option>
              @foreach ($projects as $p)
                <option value="{{ $p->id }}" @if((string)$projectChoice === (string)$p->id) selected @endif>{{ $p->name }}</option>
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
            <label>Meeting title</label>
            <input name="meeting_title" required placeholder="Sprint review" value="{{ old('meeting_title') }}">
          </div>
          <div class="col">
            <label>Meeting date & time</label>
            <input name="meeting_datetime" required placeholder="2025-12-15 16:00 UTC" value="{{ old('meeting_datetime') }}">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label>Attendees</label>
            <textarea name="attendees" rows="3" required placeholder="PM, Eng Lead, QA, Client Partner">{{ old('attendees') }}</textarea>
          </div>
          <div class="col">
            <label>Agenda</label>
            <textarea name="agenda" rows="3" required placeholder="- Demo highlights\n- Blockers\n- Next sprint goals">{{ old('agenda') }}</textarea>
          </div>
        </div>

        <label>Notes or transcript</label>
        <textarea name="notes_or_transcript" rows="5" required placeholder="Raw notes, actions, quotes...">{{ old('notes_or_transcript') }}</textarea>

        <div style="text-align:right;">
          <button class="btn" type="submit">Generate Draft</button>
        </div>
      </form>
    @else
      <div class="row" style="gap:20px;">
        <div class="col">
          <div class="muted">Project: <strong>{{ $draft->project?->name }}</strong></div>
          <div class="muted">Captured: {{ $draft->created_at }}</div>
          <div class="muted">Tone: {{ $draft->tone }}</div>
        </div>
        <div class="col" style="text-align:right;">
          <a class="btn secondary" href="{{ route('emails.mom.draft.form', ['project_id' => $draft->project_id]) }}">Start a new draft</a>
        </div>
      </div>

      <label>Draft MoM (saved as MOM_DRAFT)</label>
      <textarea id="draftText" rows="10" readonly>{{ $draft->body_text }}</textarea>
      <div style="text-align:right;">
        <button class="btn secondary" type="button" onclick="copyContent('draftText')">Copy Draft</button>
      </div>
    @endif
  </div>

  {{-- Step 2: Refine --}}
  <div class="card stacked" style="margin-bottom:14px;">
    <div class="section-title">Step 2 - Refine MoM</div>

    @if (!$draft)
      <p class="muted">Generate a draft first to unlock this step.</p>
    @else
      @if ($refined)
        <label>Refined MoM (saved as MOM_REFINED)</label>
        <textarea id="refinedText" rows="10" readonly>{{ $refined->body_text }}</textarea>
        <div class="row" style="align-items:center;">
          <div class="col"><span class="muted">Created: {{ $refined->created_at }}</span></div>
          <div class="col" style="text-align:right;">
            <button class="btn secondary" type="button" onclick="copyContent('refinedText')">Copy Refined</button>
          </div>
        </div>
        @if (!$final)
          <div class="muted" style="margin-top:10px;">Need another pass? Use the form below to refine again.</div>
        @endif
      @endif

      @if (!$final)
        <form method="POST" action="{{ route('emails.mom.refine.generate', ['draft' => $draft->id]) }}" data-loading-text="Refining MoM...">
          @csrf
          <div class="row">
            @php $refineTone = old('tone', 'formal'); @endphp
            <div class="col">
              <label>Tone</label>
              <select name="tone" required>
                <option value="formal" @if($refineTone === 'formal') selected @endif>Formal</option>
                <option value="executive" @if($refineTone === 'executive') selected @endif>Executive</option>
                <option value="neutral" @if($refineTone === 'neutral') selected @endif>Neutral</option>
              </select>
            </div>
            <div class="col">
              <label>Product update context (optional)</label>
              <textarea name="product_update_context" rows="3" placeholder="Latest release, roadmap context, etc.">{{ old('product_update_context') }}</textarea>
            </div>
          </div>

          <label>Raw MoM to refine</label>
          <textarea name="raw_mom" rows="8" required>{{ old('raw_mom', $refined?->body_text ?? $draft->body_text) }}</textarea>

          <div style="text-align:right;">
            <button class="btn" type="submit">Refine MoM</button>
          </div>
        </form>
      @endif
    @endif
  </div>

  {{-- Step 3: Final Email --}}
  <div class="card stacked">
    <div class="section-title">Step 3 - Final Email</div>

    @if (!$refined)
      <p class="muted">Refine the MoM to unlock the email-ready version.</p>
    @else
      @if ($final)
        <div class="muted">Saved as MOM_FINAL | {{ $final->created_at }}</div>
        <label>Subject</label>
        <textarea id="finalSubject" rows="2" readonly>{{ $final->subject }}</textarea>
        <label>Body</label>
        <textarea id="finalBody" rows="10" readonly>{{ $final->body_text }}</textarea>
        <div class="row" style="justify-content:flex-end;">
          <button class="btn secondary" type="button" onclick="copyContent('finalSubject')">Copy Subject</button>
          <button class="btn secondary" type="button" onclick="copyContent('finalBody')">Copy Body</button>
        </div>
      @endif

      <form method="POST" action="{{ route('emails.mom.final.generate', ['refined' => $refined->id]) }}" data-loading-text="Building final email...">
        @csrf
        <div class="row">
          <div class="col">
            <label>Meeting title</label>
            <input name="meeting_title" required value="{{ old('meeting_title', $draft?->input_json['meeting_title'] ?? 'Meeting') }}">
          </div>
          <div class="col">
            <label>Date</label>
            <input name="date" required value="{{ old('date', $draft?->input_json['meeting_datetime'] ?? date('Y-m-d')) }}">
          </div>
          <div class="col">
            @php $finalTone = old('tone', 'formal'); @endphp
            <label>Tone</label>
            <select name="tone" required>
              <option value="formal" @if($finalTone === 'formal') selected @endif>Formal</option>
              <option value="executive" @if($finalTone === 'executive') selected @endif>Executive</option>
              <option value="neutral" @if($finalTone === 'neutral') selected @endif>Neutral</option>
            </select>
          </div>
        </div>
        <div style="text-align:right; margin-top:12px;">
          <button class="btn" type="submit">{{ $final ? 'Regenerate Final Email' : 'Generate Final Email' }}</button>
        </div>
      </form>
    @endif
  </div>

  <script>
    async function copyContent(id) {
      const el = document.getElementById(id);
      if (!el) return;
      try {
        await navigator.clipboard.writeText(el.value);
        alert('Copied!');
      } catch (_) {
        el.select();
        document.execCommand('copy');
        alert('Copied!');
      }
    }
  </script>
@endsection
