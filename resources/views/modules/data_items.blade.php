@extends('layout')

@section('content')
  <h2>Data Collection — {{ $project->name }}</h2>
  <p class="subhead">Track data items, due dates, and upload received files to Drive.</p>

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
    <div class="section-title">Add data item</div>
    <form id="dataItemForm">
      <label>Name</label>
      <input name="name" required>
      <label>Category</label>
      <input name="category" required>
      <label>Expected format</label>
      <input name="expected_format">
      <label>Owner</label>
      <input name="owner" required>
      <label>Due date</label>
      <input name="due_date" type="date">
      <label>Status</label>
      <select name="status" required>
        <option>PENDING</option><option>RECEIVED</option><option>VALIDATED</option><option>REJECTED</option>
      </select>
      <label>Notes</label>
      <textarea name="notes"></textarea>
      <button class="btn" type="submit" style="margin-top:8px;">Save</button>
    </form>
    <div id="dataItemStatus" class="muted"></div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Items</div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr><th>Name</th><th>Owner</th><th>Due</th><th>Status</th><th>Upload</th></tr></thead>
        <tbody>
          @foreach($items as $item)
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:6px;">{{ $item->name }}</td>
              <td style="padding:6px;">{{ $item->owner }}</td>
              <td style="padding:6px;">{{ optional($item->due_date)->toDateString() }}</td>
              <td style="padding:6px;">{{ $item->status }}</td>
              <td style="padding:6px;">
                <form class="uploadForm" data-id="{{ $item->id }}" enctype="multipart/form-data">
                  <input type="file" name="file" required>
                  <button class="btn secondary" type="submit" style="margin-top:4px;">Upload</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message || 'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('dataItemForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = new FormData(e.target);
      setStatus(document.getElementById('dataItemStatus'),'Saving...');
      fetch(`/api/projects/${projectId}/data-items`,{method:'POST',body:data})
        .then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('dataItemStatus'),err.message,true));
    });
    document.querySelectorAll('.uploadForm').forEach(form=>{
      form.addEventListener('submit',(e)=>{
        e.preventDefault();
        const id = form.dataset.id;
        const data = new FormData(form);
        fetch(`/api/projects/${projectId}/data-items/${id}/upload`,{method:'POST',body:data})
          .then(handleResponse).then(()=>location.reload())
          .catch(err=>alert(err.message));
      });
    });
  </script>
@endsection
