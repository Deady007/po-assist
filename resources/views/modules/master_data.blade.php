@extends('layout')

@section('content')
  <h2>Master Data / DB Changes — {{ $project->name }}</h2>
  <p class="subhead">Capture master data modifications tied to requirements.</p>

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
    <div class="section-title">Add change</div>
    <form id="changeForm">
      <label>Requirement ID (optional)</label>
      <input name="requirement_id" type="number" min="1">
      <label>Object name</label>
      <input name="object_name" required>
      <label>Field name</label>
      <input name="field_name" required>
      <label>Change type</label>
      <input name="change_type" required>
      <label>Description</label>
      <textarea name="description"></textarea>
      <button class="btn" type="submit" style="margin-top:8px;">Save</button>
    </form>
    <div id="changeStatus" class="muted"></div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Changes</div>
    <ul class="muted">
      @forelse($changes as $c)
        <li>#{{ $c->id }} {{ $c->object_name }}.{{ $c->field_name }} ({{ $c->change_type }})</li>
      @empty
        <li>No changes recorded.</li>
      @endforelse
    </ul>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message||'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('changeForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target).entries());
      data.requirement_id = data.requirement_id || null;
      setStatus(document.getElementById('changeStatus'),'Saving...');
      fetch(`/api/projects/${projectId}/master-data-changes`,{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(data)
      }).then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('changeStatus'),err.message,true));
    });
  </script>
@endsection
