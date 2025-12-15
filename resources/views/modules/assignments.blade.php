@extends('layout')

@section('content')
  <h2>Requirement Assignments — {{ $project->name }}</h2>
  <p class="subhead">Assign requirements to developers with status and ETA.</p>

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
    <div class="section-title">New assignment</div>
    <form id="assignForm">
      <label>Requirement</label>
      <select name="requirement_id" required>
        @foreach($requirements as $r)
          <option value="{{ $r->id }}">{{ $r->req_code }} - {{ $r->title }}</option>
        @endforeach
      </select>
      <label>Developer</label>
      <select name="developer_id" required>
        @foreach($developers as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>
      <label>Status</label>
      <select name="status" required>
        <option>ASSIGNED</option><option>IN_PROGRESS</option><option>DONE</option>
      </select>
      <label>ETA date</label>
      <input type="date" name="eta_date">
      <label>Notes</label>
      <textarea name="notes"></textarea>
      <button class="btn" type="submit" style="margin-top:8px;">Assign</button>
    </form>
    <div id="assignStatus" class="muted"></div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Assignments</div>
    <ul class="muted">
      @forelse($assignments as $a)
        <li>{{ $a->requirement->req_code ?? 'REQ' }} -> {{ $a->developer->name ?? '' }} ({{ $a->status }})</li>
      @empty
        <li>No assignments yet.</li>
      @endforelse
    </ul>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message||'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('assignForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = new FormData(e.target);
      setStatus(document.getElementById('assignStatus'),'Saving...');
      fetch(`/api/projects/${projectId}/assignments`,{method:'POST',body:data})
        .then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('assignStatus'),err.message,true));
    });
  </script>
@endsection
