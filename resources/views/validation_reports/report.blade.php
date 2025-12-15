<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; color: #0f172a; }
    h1, h2, h3 { margin-bottom: 6px; }
    .section { margin-top: 18px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #e2e8f0; padding: 6px; text-align: left; }
    .muted { color: #64748b; }
  </style>
</head>
<body>
  <h1>Validation Report</h1>
  <div class="muted">Generated at: {{ $report['generated_at'] }}</div>

  <div class="section">
    <h2>Project</h2>
    <div>{{ $report['project']['name'] }} @if(!empty($report['project']['client_name'])) ({{ $report['project']['client_name'] }}) @endif</div>
  </div>

  <div class="section">
    <h2>Phases</h2>
    <table>
      <thead><tr><th>Phase</th><th>Status</th><th>Planned</th><th>Actual</th></tr></thead>
      <tbody>
        @foreach ($report['phases'] as $p)
          <tr>
            <td>{{ $p['phase_key'] }}</td>
            <td>{{ $p['status'] }}</td>
            <td>{{ $p['planned_start_date'] }} → {{ $p['planned_end_date'] }}</td>
            <td>{{ $p['actual_start_date'] }} → {{ $p['actual_end_date'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <h2>Requirements</h2>
    <div>By status: {{ json_encode($report['requirements']['totals_by_status']) }}</div>
    <div>By priority: {{ json_encode($report['requirements']['totals_by_priority']) }}</div>
    <div>Change requests: {{ $report['requirements']['change_requests'] }}</div>
    @if(!empty($report['requirements']['delivered']))
      <h3>Delivered</h3>
      <ul>
        @foreach ($report['requirements']['delivered'] as $r)
          <li>{{ $r['req_code'] }} — {{ $r['title'] }} ({{ $r['delivered_at'] }})</li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="section">
    <h2>Data Collection</h2>
    <div>By status: {{ json_encode($report['data_collection']['totals_by_status']) }}</div>
    @if(!empty($report['data_collection']['pending_past_due']))
      <h3>Pending Past Due</h3>
      <ul>
        @foreach ($report['data_collection']['pending_past_due'] as $d)
          <li>{{ $d['name'] }} (owner {{ $d['owner'] }}), due {{ $d['due_date'] }}</li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="section">
    <h2>Master Data Changes</h2>
    <div>By type: {{ json_encode($report['master_data']['counts_by_change_type']) }}</div>
    @if(!empty($report['master_data']['items']))
      <ul>
        @foreach ($report['master_data']['items'] as $m)
          <li>{{ $m['object_name'] }}.{{ $m['field_name'] }} ({{ $m['change_type'] }}) @if($m['version_tag']) [{{ $m['version_tag'] }}] @endif</li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="section">
    <h2>Bugs</h2>
    <div>By status: {{ json_encode($report['bugs']['counts_by_status']) }}</div>
    <div>By severity: {{ json_encode($report['bugs']['counts_by_severity']) }}</div>
    @if(!empty($report['bugs']['open_critical_high']))
      <h3>Open Critical/High</h3>
      <ul>
        @foreach ($report['bugs']['open_critical_high'] as $b)
          <li>#{{ $b['id'] }} {{ $b['title'] }} ({{ $b['severity'] }} / {{ $b['status'] }})</li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="section">
    <h2>Testing</h2>
    <div>Test runs: {{ $report['testing']['test_run_count'] }}</div>
    <div>Result counts: {{ json_encode($report['testing']['result_counts']) }}</div>
    <div>Coverage: {{ $report['testing']['coverage_percent'] }}%</div>
  </div>

  <div class="section">
    <h2>Delivery & Tokens</h2>
    <div>Latest delivery date: {{ $report['delivery']['latest_delivery_date'] ?? 'n/a' }}</div>
    <div>Wallet: {{ json_encode($report['tokens']['wallet']) }}</div>
    @if(!empty($report['tokens']['open_requests']))
      <h3>Open token requests</h3>
      <ul>
        @foreach ($report['tokens']['open_requests'] as $t)
          <li>#{{ $t['id'] }} {{ $t['title'] }} ({{ $t['status'] }}) — {{ $t['tokens_estimated'] }} tokens</li>
        @endforeach
      </ul>
    @endif
  </div>

  @if(!empty($report['warnings']))
    <div class="section">
      <h2>Warnings</h2>
      <ul>
        @foreach ($report['warnings'] as $w)
          <li><strong>{{ $w['code'] }}</strong> ({{ $w['severity'] }}): {{ $w['message'] }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(!empty($report['executive_summary']))
    <div class="section">
      <h2>Executive Summary (AI)</h2>
      <div>{{ $report['executive_summary'] }}</div>
    </div>
  @endif
</body>
</html>
