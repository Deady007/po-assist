@extends('layout')

@section('content')
  <h2>Validation Reports — {{ $project->name }}</h2>
  <p class="subhead">Generate consolidated delivery status and push to Drive.</p>

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
    <div class="section-title">Generate report</div>
    <label><input type="checkbox" id="aiSummary" checked> Include AI executive summary</label>
    <button class="btn" id="genBtn" style="margin-top:8px;">Generate</button>
    <div id="genStatus" class="muted"></div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Reports</div>
    <ul class="muted">
      @forelse($reports as $r)
        <li>
          #{{ $r->id }} ({{ optional($r->generated_at)->format('Y-m-d H:i') }})
          <button class="btn secondary uploadBtn" data-id="{{ $r->id }}" style="margin-left:8px;">Upload to Drive</button>
        </li>
      @empty
        <li>No reports yet.</li>
      @endforelse
    </ul>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message||'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('genBtn')?.addEventListener('click',()=>{
      const include_ai_summary = document.getElementById('aiSummary')?.checked;
      setStatus(document.getElementById('genStatus'),'Generating...');
      fetch(`/api/projects/${projectId}/validation-reports/generate`,{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({include_ai_summary})
      }).then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('genStatus'),err.message,true));
    });
    document.querySelectorAll('.uploadBtn').forEach(btn=>{
      btn.addEventListener('click',()=>{
        const id = btn.dataset.id;
        setStatus(document.getElementById('genStatus'),'Uploading to Drive...');
        fetch(`/api/projects/${projectId}/validation-reports/${id}/upload-to-drive`,{method:'POST'})
          .then(handleResponse).then(()=>setStatus(document.getElementById('genStatus'),'Uploaded to Drive'))
          .catch(err=>setStatus(document.getElementById('genStatus'),err.message,true));
      });
    });
  </script>
@endsection
