@extends('layout')

@section('content')
  <h2>Testing — {{ $project->name }}</h2>
  <p class="subhead">Manage test cases, runs, and log results (PASS/FAIL/BLOCKED).</p>

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

  <div class="grid two" style="margin-top:16px;">
    <div class="card stacked">
      <div class="section-title">Add test case</div>
      <form id="tcForm">
        <label>Title</label>
        <input name="title" required>
        <label>Steps</label>
        <textarea name="steps" required></textarea>
        <label>Expected result</label>
        <textarea name="expected_result" required></textarea>
        <button class="btn" type="submit" style="margin-top:8px;">Save</button>
      </form>
      <div id="tcStatus" class="muted"></div>
    </div>
    <div class="card stacked">
      <div class="section-title">Create test run</div>
      <form id="runForm">
        <label>Tester</label>
        <select name="tester_id" required>
          @foreach($testers as $t)
            <option value="{{ $t->id }}">{{ $t->name }}</option>
          @endforeach
        </select>
        <label>Run date</label>
        <input type="date" name="run_date" required>
        <label>Notes</label>
        <textarea name="notes"></textarea>
        <button class="btn" type="submit" style="margin-top:8px;">Create</button>
      </form>
      <div id="runStatus" class="muted"></div>
    </div>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Test cases</div>
    <ul class="muted">
      @forelse($testCases as $tc)
        <li>#{{ $tc->id }} {{ $tc->title }}</li>
      @empty
        <li>No test cases yet.</li>
      @endforelse
    </ul>
  </div>

  <div class="card stacked" style="margin-top:16px;">
    <div class="section-title">Test runs</div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr><th>ID</th><th>Date</th><th>Tester</th><th>Log Result</th></tr></thead>
        <tbody>
          @forelse($testRuns as $run)
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:6px;">#{{ $run->id }}</td>
              <td style="padding:6px;">{{ optional($run->run_date)->toDateString() }}</td>
              <td style="padding:6px;">{{ $run->tester->name ?? '' }}</td>
              <td style="padding:6px;">
                <form class="resultForm" data-run="{{ $run->id }}">
                  <label style="font-size:12px;">Test case ID</label>
                  <input type="number" name="test_case_id" min="1" required>
                  <label style="font-size:12px;">Status</label>
                  <select name="status">
                    <option>PASS</option><option>FAIL</option><option>BLOCKED</option>
                  </select>
                  <label style="font-size:12px;">Remarks</label>
                  <input name="remarks">
                  <button class="btn secondary" type="submit" style="margin-top:4px;">Add</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" style="padding:6px;">No test runs yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <script>
    const projectId = {{ $project->id }};
    function handleResponse(res){ if(!res.ok) return res.json().then(j=>{throw new Error(j.errors?.[0]?.message||'Request failed')}); return res.json();}
    function setStatus(el,msg,isError=false){ if(!el)return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#5b6475'; }
    document.getElementById('tcForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = new FormData(e.target);
      setStatus(document.getElementById('tcStatus'),'Saving...');
      fetch(`/api/projects/${projectId}/test-cases`,{method:'POST',body:data})
        .then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('tcStatus'),err.message,true));
    });
    document.getElementById('runForm')?.addEventListener('submit',(e)=>{
      e.preventDefault();
      const data = new FormData(e.target);
      setStatus(document.getElementById('runStatus'),'Saving...');
      fetch(`/api/projects/${projectId}/test-runs`,{method:'POST',body:data})
        .then(handleResponse).then(()=>location.reload())
        .catch(err=>setStatus(document.getElementById('runStatus'),err.message,true));
    });
    document.querySelectorAll('.resultForm').forEach(form=>{
      form.addEventListener('submit',(e)=>{
        e.preventDefault();
        const runId = form.dataset.run;
        const data = {
          results:[{
            test_case_id: form.test_case_id.value,
            status: form.status.value,
            remarks: form.remarks.value
          }]
        };
        fetch(`/api/projects/${projectId}/test-runs/${runId}/results`,{
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(data)
        }).then(handleResponse).then(()=>location.reload())
          .catch(err=>alert(err.message));
      });
    });
  </script>
@endsection
