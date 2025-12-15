@extends('layout')

@section('content')
  <h2>Workspace</h2>
  <p class="subhead">Pick a project, then jump into the generator you need.</p>

  <div class="card stacked">
    <div class="row">
      <div class="col">
        <label>Project</label>
        <select id="projectSelect" data-project-persist>
          <option value="">-- choose --</option>
          @foreach ($projects as $p)
            <option value="{{ $p->id }}">{{ $p->name }}@if($p->client_name) ({{ $p->client_name }}) @endif</option>
          @endforeach
        </select>
        <span class="helper">Selection persists across pages. Switch projects anytime.</span>
      </div>
      <div class="col" style="align-self:flex-end;">
        <div class="pill-tag">AI Generators</div>
        <div class="muted">Product updates, meeting scheduling, MoM pipeline, HR daily notes.</div>
      </div>
    </div>
  </div>

  <div class="grid two" style="margin-top:18px;">
    <div class="card stacked">
      <div class="section-title">Product Update</div>
      <p class="muted">Crisp stakeholder update: completed, in-progress, risks, and review topics.</p>
      <button class="btn" data-route="{{ route('emails.product.form') }}" onclick="goToFlow(event)">Create</button>
    </div>

    <div class="card stacked">
      <div class="section-title">Meeting Schedule</div>
      <p class="muted">Send a professional scheduling email with agenda, attendees, and logistics.</p>
      <button class="btn" data-route="{{ route('emails.meeting.form') }}" onclick="goToFlow(event)">Create</button>
    </div>

    <div class="card stacked">
      <div class="section-title">Minutes of Meeting (3-step)</div>
      <p class="muted">Draft raw notes, refine them, then generate the final MoM email.</p>
      <button class="btn" data-route="{{ route('emails.mom.draft.form') }}" onclick="goToFlow(event)">Create</button>
    </div>

    <div class="card stacked">
      <div class="section-title">HR End-of-Day</div>
      <p class="muted">Non-technical daily update focused on delivery health and people impact.</p>
      <button class="btn" data-route="{{ route('emails.hr.form') }}" onclick="goToFlow(event)">Create</button>
    </div>
  </div>

  <script>
    function goToFlow(e) {
      e.preventDefault();
      const btn = e.currentTarget;
      const base = btn.getAttribute('data-route');
      const sel = document.getElementById('projectSelect');
      const pid = sel?.value || '';
      if (!pid) {
        return alert('Please pick a project first.');
      }
      const url = base + '?project_id=' + encodeURIComponent(pid);
      window.location.href = url;
    }
  </script>
@endsection
