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
    a { color: var(--ink); text-decoration: none; }
    a:hover { text-decoration: underline; }
    .app-shell { display:flex; min-height:100vh; }
    .sidebar { width: 240px; background:#0b1021; color:#e2e8f0; padding:18px 16px; position:sticky; top:0; height:100vh; }
    .sidebar .brand { color:#fff; font-weight:800; display:flex; align-items:center; gap:8px; margin-bottom:18px; }
    .pill { background: var(--accent-2); color: #fff; padding: 2px 10px; border-radius: 999px; font-size: 12px; }
    .nav-section { margin-top:18px; }
    .nav-label { font-size:12px; letter-spacing:0.6px; color:#94a3b8; text-transform:uppercase; margin-bottom:8px; }
    .nav-link { display:flex; align-items:center; padding:10px 12px; border-radius:10px; color:#e2e8f0; }
    .nav-link:hover { background: rgba(255,255,255,0.06); text-decoration:none; }
    .main { flex:1; display:flex; flex-direction:column; min-height:100vh; }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 10;
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(6px);
      border-bottom: 1px solid var(--border);
      padding: 14px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap:16px;
    }
    .search-box { flex:1; display:flex; align-items:center; gap:10px; }
    .search-box input { width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border); }
    .top-actions { display:flex; align-items:center; gap:10px; }
    .dropdown { position:relative; }
    .dropdown-menu { position:absolute; top:calc(100% + 6px); right:0; background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow:0 18px 40px rgba(15,23,42,0.12); min-width:220px; display:none; padding:10px; }
    .dropdown-menu a { display:block; padding:8px 10px; border-radius:8px; color:var(--ink); }
    .dropdown-menu a:hover { background:#f1f5f9; text-decoration:none; }
    .dropdown.open .dropdown-menu { display:block; }
    main.content { width:100%; max-width:1100px; margin:24px auto 40px; padding: 0 24px 80px; }
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
    .page-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
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
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand">
        <span>PO Assist</span>
        <span class="pill">MVP</span>
      </div>
      <div class="nav-section">
        <div class="nav-label">Dashboards</div>
        <a class="nav-link" href="{{ route('dashboard') }}">Workspace</a>
        <a class="nav-link" href="{{ route('dashboard.product') }}">Product</a>
        <a class="nav-link" href="{{ route('dashboard.tasks') }}">Tasks</a>
        <a class="nav-link" href="{{ route('dashboard.workload') }}">Workload</a>
      </div>
      <div class="nav-section">
        <div class="nav-label">Projects</div>
        <a class="nav-link" href="{{ route('admin.projects.index') }}">All projects</a>
      </div>
      <div class="nav-section">
        <div class="nav-label">Clients</div>
        <a class="nav-link" href="{{ route('clients.customers.index') }}">Customers</a>
        <a class="nav-link" href="{{ route('clients.contacts.index') }}">Contacts</a>
        @if(auth()->user()?->role?->name === 'Admin')
          <a class="nav-link" href="{{ route('admin.config.email_templates.index') }}">Email templates</a>
        @endif
      </div>
    </aside>
    <div class="main">
      <div class="topbar">
        <form class="search-box" action="{{ route('search') }}" method="GET">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search projects or customers">
        </form>
        <div class="top-actions">
          <div class="dropdown" data-dropdown>
            <button class="btn secondary" type="button" data-dropdown-toggle>Setup</button>
            <div class="dropdown-menu">
              @if(auth()->user()?->role?->name === 'Admin')
                <a href="{{ route('admin.users.index') }}">Users</a>
              @endif
              @if(auth()->user()?->role?->name === 'Admin')
                <a href="{{ route('admin.config.sequences.index') }}">Sequences</a>
              @endif
              @if(auth()->user()?->role?->name === 'Admin')
                <a href="{{ route('admin.config.statuses.index') }}">Project statuses</a>
              @endif
              @if(auth()->user()?->role?->name === 'Admin')
                <a href="{{ route('admin.import-export.index') }}">Import/Export</a>
              @endif
            </div>
          </div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn secondary" type="submit">Logout</button>
          </form>
        </div>
      </div>
      <main class="content">
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
        @if (session('status'))
          <div class="card" style="border-color:#bfdbfe;">
            {{ session('status') }}
          </div>
        @endif

        @yield('content')
      </main>
    </div>
  </div>

  <div id="loadingOverlay" class="loading-overlay" hidden>
    <div class="loading-box">
      <div class="dot"></div><div class="dot"></div><div class="dot"></div>
      <div id="loadingOverlayText">Generating with AI...</div>
    </div>
  </div>

  <script>
    // Setup dropdown
    document.querySelectorAll('[data-dropdown]').forEach((dd) => {
      const toggle = dd.querySelector('[data-dropdown-toggle]');
      toggle?.addEventListener('click', () => dd.classList.toggle('open'));
      document.addEventListener('click', (e) => {
        if (!dd.contains(e.target)) dd.classList.remove('open');
      });
    });

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
