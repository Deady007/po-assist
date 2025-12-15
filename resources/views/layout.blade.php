<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>PO Assist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root {
      --ink: #0f172a;
      --muted: #5b6475;
      --accent: #0ea5e9;
      --accent-2: #0f766e;
      --bg: #f6f8fb;
      --card: #ffffff;
      --border: #e2e8f0;
    }
    * { box-sizing: border-box; }
    body { margin:0; font-family: 'Segoe UI', 'Helvetica Neue', sans-serif; background: var(--bg); color: var(--ink); }
    a { color: var(--accent); text-decoration: none; }
    a:hover { text-decoration: underline; }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 10;
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(6px);
      border-bottom: 1px solid var(--border);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .brand { font-weight: 700; letter-spacing: 0.3px; display: flex; align-items: center; gap: 10px; }
    .pill { background: var(--accent-2); color: #fff; padding: 2px 10px; border-radius: 999px; font-size: 12px; }
    .topnav { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
    .topnav a { padding: 8px 12px; border-radius: 10px; font-weight: 600; color: var(--ink); border: 1px solid transparent; }
    .topnav a:hover { background: #eef7ff; border-color: var(--border); text-decoration: none; }
    main.container { max-width: 1100px; margin: 32px auto 48px; padding: 0 24px; }
    h1, h2, h3 { margin: 0 0 10px; }
    .subhead { color: var(--muted); margin-bottom: 24px; }
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px; box-shadow: 0 10px 30px rgba(15,23,42,0.05); }
    .card + .card { margin-top: 16px; }
    .grid { display: grid; gap: 16px; }
    .grid.two { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
    label { font-weight: 700; display:block; margin-bottom: 6px; }
    .helper { color: var(--muted); font-size: 13px; margin-bottom: 10px; display:block; }
    input, select, textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: #fefefe;
      font-size: 14px;
      font-family: inherit;
    }
    textarea { min-height: 120px; resize: vertical; }
    .row { display:flex; gap: 16px; flex-wrap: wrap; }
    .col { flex:1; min-width: 240px; }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 11px 16px;
      border-radius: 12px;
      border: 1px solid var(--accent);
      color: #fff;
      background: linear-gradient(135deg, var(--accent), #0284c7);
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 12px 30px rgba(14,165,233,0.25);
    }
    .btn.secondary { background: #fff; color: var(--ink); border-color: var(--border); box-shadow: none; }
    .btn.ghost { background: transparent; color: var(--ink); border-color: transparent; padding-left: 0; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .badge.blue { background: #e0f2fe; color: #075985; }
    .badge.green { background: #e2fbe8; color: #166534; }
    .badge.amber { background: #fef3c7; color: #92400e; }
    .badge.rose { background: #ffe4e6; color: #9f1239; }
    .muted { color: var(--muted); font-size: 14px; }
    .pill-tag { background: #f1f5f9; color: var(--muted); padding: 2px 10px; border-radius: 999px; font-weight: 600; font-size: 12px; }
    .section-title { font-size: 16px; font-weight: 800; margin-bottom: 10px; }
    .stacked > * + * { margin-top: 18px; }
    .stepper { display:flex; gap:14px; align-items:center; margin-bottom:18px; flex-wrap:wrap; }
    .step { display:flex; align-items:center; gap:8px; }
    .step-circle { width:28px; height:28px; border-radius:50%; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-weight:700; }
    .step.active .step-circle { background: var(--accent); color:#fff; border-color: var(--accent); }
    .step.completed .step-circle { background: #22c55e; color:#fff; border-color:#22c55e; }
    .loading-overlay[hidden] { display: none !important; }
    .loading-overlay {
      position: fixed; inset:0; background: rgba(15,23,42,0.55);
      display:flex; align-items:center; justify-content:center;
      color:#fff; font-weight:700; font-size:18px; z-index:100;
    }
    .loading-box { background: rgba(15,23,42,0.8); padding:16px 22px; border-radius:12px; display:flex; align-items:center; gap:10px; }
    .dot { width:8px; height:8px; border-radius:50%; background:#fff; animation: pulse 1.2s infinite; }
    .dot:nth-child(2){ animation-delay:0.2s;}
    .dot:nth-child(3){ animation-delay:0.4s;}
    @keyframes pulse { 0%{opacity:0.3;} 50%{opacity:1;} 100%{opacity:0.3;} }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="brand">
      <span>PO Assist</span>
      <span class="pill">AI</span>
    </div>
    <div class="topnav">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <a href="{{ route('emails.product.form') }}" data-attach-project>Product Update</a>
      <a href="{{ route('emails.meeting.form') }}" data-attach-project>Meeting Schedule</a>
      <a href="{{ route('emails.mom.draft.form') }}" data-attach-project>MoM</a>
      <a href="{{ route('emails.hr.form') }}" data-attach-project>HR End-of-Day</a>
      <a href="#" data-project-path="/projects/{id}/requirements">Workflow</a>
      <a href="{{ route('drive.connect') }}">Drive</a>
      <a href="{{ route('history') }}" data-attach-project>History</a>
    </div>
  </div>

  <main class="container">
    @if ($errors->any())
      <div class="card" style="border-color:#fecdd3;">
        <strong>Validation errors:</strong>
        <ul>
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @yield('content')
  </main>

  <div id="loadingOverlay" class="loading-overlay" hidden>
    <div class="loading-box">
      <div class="dot"></div><div class="dot"></div><div class="dot"></div>
      <div id="loadingOverlayText">Generating with AI...</div>
    </div>
  </div>

  <script>
    // Persist project selection across pages (query param -> localStorage -> forms).
    (function() {
      const params = new URLSearchParams(window.location.search);
      const queryProject = params.get('project_id');
      const storedProject = window.localStorage.getItem('poa_project_id');
      const initial = queryProject || storedProject || '';
      window.__poaProjectId = initial;

      const updateNavLinks = (val) => {
        if (!val) return;
        document.querySelectorAll('[data-attach-project]').forEach((link) => {
          const url = new URL(link.href, window.location.origin);
          url.searchParams.set('project_id', val);
          link.href = url.toString();
        });
        document.querySelectorAll('[data-project-path]').forEach((link) => {
          const tmpl = link.getAttribute('data-project-path');
          if (tmpl && tmpl.includes('{id}')) {
            link.href = tmpl.replace('{id}', val);
          }
        });
      };

      const syncProject = (val) => {
        if (val) {
          window.localStorage.setItem('poa_project_id', val);
          window.__poaProjectId = val;
          updateNavLinks(val);
        }
      };

      document.querySelectorAll('select[data-project-persist]').forEach((sel) => {
        if (!sel.value && initial) {
          sel.value = initial;
        }
        sel.addEventListener('change', (e) => syncProject(e.target.value));
      });

      updateNavLinks(initial);
    })();

    // Global loading indicator for forms.
    (function() {
      const overlay = document.getElementById('loadingOverlay');
      const text = document.getElementById('loadingOverlayText');
      const hideOverlay = () => { if (overlay) overlay.hidden = true; };
      // Ensure overlay is hidden on load or when coming back via browser back/forward cache.
      document.addEventListener('DOMContentLoaded', hideOverlay);
      window.addEventListener('pageshow', hideOverlay);

      document.querySelectorAll('form[data-loading-text]').forEach((form) => {
        form.addEventListener('submit', () => {
          if (text) text.textContent = form.dataset.loadingText || 'Generating with AI...';
          if (overlay) overlay.hidden = false;
        });
      });
    })();
  </script>
</body>
</html>
