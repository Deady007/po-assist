@extends('layout')

@section('content')
  <h2>Delivery & Tokens — {{ $project->name }}</h2>
  <p class="subhead">Record delivery, manage token wallet, and track token requests.</p>

  <div class="card stacked">
    <div class="section-title">Token wallet</div>
    <form id="walletForm">
      <label>Total tokens</label>
      <input type="number" name="total_tokens" min="0" value="{{ $wallet->total_tokens ?? 0 }}">
      <label>Used tokens</label>
      <input type="number" name="used_tokens" min="0" value="{{ $wallet->used_tokens ?? 0 }}">
      <button class="btn" type="submit" style="margin-top:8px;">Save</button>
    </form>
    <div id="walletStatus" class="muted"></div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Token requests</div>
    <form id="tokenForm">
      <label>Type</label>
      <input name="type" required>
      <label>Title</label>
      <input name="title" required>
      <label>Description</label>
      <textarea name="description" required></textarea>
      <label>Tokens estimated</label>
      <input type="number" name="tokens_estimated" min="0" value="0" required>
      <label>Status</label>
      <select name="status">
        <option>PENDING</option><option>APPROVED</option><option>REJECTED</option><option>DONE</option>
      </select>
      <button class="btn" type="submit" style="margin-top:8px;">Create</button>
    </form>
    <ul class="muted" style="margin-top:12px;">
      @forelse($requests as $r)
        <li>#{{ $r->id }} {{ $r->title }} ({{ $r->status }}) — {{ $r->tokens_estimated }} tokens</li>
      @empty
        <li>No token requests.</li>
      @endforelse
    </ul>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message||'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('walletForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target).entries());
      setStatus(document.getElementById('walletStatus'),'Saving...');
      fetch(`/api/projects/${projectId}/token-wallet`,{
        method:'PUT',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(data)
      }).then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('walletStatus'),err.message,true));
    });
    document.getElementById('tokenForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target).entries());
      fetch(`/api/projects/${projectId}/token-requests`,{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(data)
      }).then(handleResponse).then(()=>location.reload())
        .catch(err=>alert(err.message));
    });
  </script>
@endsection
