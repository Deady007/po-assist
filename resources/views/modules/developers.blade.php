@extends('layout')

@section('content')
  <h2>Developers</h2>
  <p class="subhead">Maintain the developer directory for assignments and bugs.</p>

  <div class="card stacked">
    <div class="section-title">Add developer</div>
    <form id="devForm">
      <label>Name</label>
      <input name="name" required>
      <label>Email</label>
      <input name="email" type="email">
      <button class="btn" type="submit" style="margin-top:8px;">Save</button>
    </form>
    <div id="devStatus" class="muted"></div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Directory</div>
    <ul class="muted">
      @forelse($developers as $d)
        <li>{{ $d->name }} @if($d->email) ({{ $d->email }}) @endif</li>
      @empty
        <li>No developers yet.</li>
      @endforelse
    </ul>
  </div>

  <script>
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message||'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('devForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = new FormData(e.target);
      setStatus(document.getElementById('devStatus'),'Saving...');
      fetch('/api/developers',{method:'POST',body:data})
        .then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('devStatus'),err.message,true));
    });
  </script>
@endsection
