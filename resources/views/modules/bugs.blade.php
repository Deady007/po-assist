@extends('layout')

@section('content')
  <h2>Bugs — {{ $project->name }}</h2>
  <p class="subhead">Track issues across development and testing.</p>

  @if(!empty($warnings))
    <div class="card">
      <div class="section-title">Warnings</div>
      <ul class="muted">
        @foreach($warnings as $w)
          <li><strong>{{ $w['code'] }}</strong> ({{ $w['severity'] }}): {{ $w['message'] }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card stacked">
    <div class="section-title">Log bug</div>
    <form id="bugForm">
      <label>Title</label>
      <input name="title" required>
      <label>Description</label>
      <textarea name="description"></textarea>
      <label>Requirement (optional)</label>
      <select name="requirement_id">
        <option value="">--</option>
        @foreach($requirements as $r)
          <option value="{{ $r->id }}">{{ $r->req_code }}</option>
        @endforeach
      </select>
      <label>Severity</label>
      <select name="severity" required>
        <option>CRITICAL</option><option>HIGH</option><option>MEDIUM</option><option>LOW</option>
      </select>
      <label>Status</label>
      <select name="status" required>
        <option>OPEN</option><option>IN_PROGRESS</option><option>FIXED</option><option>CLOSED</option>
      </select>
      <label>Assign to</label>
      <select name="assigned_to_developer_id">
        <option value="">--</option>
        @foreach($developers as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>
      <button class="btn" type="submit" style="margin-top:8px;">Save</button>
    </form>
    <div id="bugStatus" class="muted"></div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Bugs</div>
    <ul class="muted">
      @forelse($bugs as $b)
        <li>#{{ $b->id }} {{ $b->title }} ({{ $b->severity }}/{{ $b->status }})</li>
      @empty
        <li>No bugs logged.</li>
      @endforelse
    </ul>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message||'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('bugForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target).entries());
      data.assigned_to_developer_id = data.assigned_to_developer_id || null;
      data.requirement_id = data.requirement_id || null;
      setStatus(document.getElementById('bugStatus'),'Saving...');
      fetch(`/api/projects/${projectId}/bugs`,{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(data)
      }).then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('bugStatus'),err.message,true));
    });
  </script>
@endsection
