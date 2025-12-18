<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>PO Assist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

  <style>
    :root {
      --ink: #0f172a;
      --muted: #64748b;
      --accent: #0ea5e9;
      --accent-2: #0f766e;
      --bg: #f6f8fb;
      --card: #ffffff;
      --border: #e2e8f0;
      --sidebar-bg: #0b1021;
      --sidebar-border: rgba(255,255,255,0.08);
      --shadow: 0 10px 30px rgba(15,23,42,0.06);
    }
    * { box-sizing: border-box; }
    body { margin:0; font-family: 'Segoe UI', 'Helvetica Neue', sans-serif; background: var(--bg); color: var(--ink); }
    a { color: var(--ink); text-decoration: none; }
    a:hover { text-decoration: underline; }
    .app-shell { display:flex; min-height:100vh; }
    .sidebar { width: 260px; background: var(--sidebar-bg); color:#e2e8f0; padding:18px 16px; position:sticky; top:0; height:100vh; border-right: 1px solid var(--sidebar-border); }
    .sidebar .brand { color:#fff; font-weight:800; display:flex; align-items:center; gap:8px; margin-bottom:18px; }
    .brand-pill { background: rgba(14,165,233,0.18); color: #e0f2fe; padding: 2px 10px; border-radius: 999px; font-size: 12px; border: 1px solid rgba(125,211,252,0.25); }
    .nav-section { margin-top:18px; }
    .nav-label { font-size:12px; letter-spacing:0.6px; color:#94a3b8; text-transform:uppercase; margin-bottom:8px; }
    .nav-link { display:flex; align-items:center; padding:10px 12px; border-radius:12px; color:#e2e8f0; gap:10px; }
    .nav-link:hover { background: rgba(255,255,255,0.06); text-decoration:none; }
    .nav-link.active { background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.12); }
    .nav-icon { width:18px; height:18px; flex-shrink:0; opacity:0.9; }
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
    .icon-btn {
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:42px;
      height:42px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: #fff;
      cursor: pointer;
    }
    .icon-btn:hover { background:#f8fafc; }
    .sidebar-toggle { display:none; }
    .sidebar-backdrop { position:fixed; inset:0; background:rgba(15,23,42,0.45); opacity:0; pointer-events:none; transition: opacity .18s ease; z-index:15; }
    .sidebar-backdrop.show { opacity:1; pointer-events:auto; }
    .dropdown { position:relative; }
    .dropdown-menu { position:absolute; top:calc(100% + 6px); right:0; background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow:0 18px 40px rgba(15,23,42,0.12); min-width:220px; display:none; padding:10px; }
    .dropdown-menu a { display:block; padding:8px 10px; border-radius:8px; color:var(--ink); }
    .dropdown-menu a:hover { background:#f1f5f9; text-decoration:none; }
    .dropdown.open .dropdown-menu { display:block; }
    main.content { width:100%; max-width:1240px; margin:24px auto 40px; padding: 0 24px 80px; }
    main.content.wide { max-width: 1380px; }
    h1, h2, h3 { margin: 0 0 10px; }
    .subhead { color: var(--muted); margin-bottom: 24px; }
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 18px; box-shadow: var(--shadow); }
    .card + .card { margin-top: 16px; }
    .grid { display: grid; gap: 16px; }
    .grid.two { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
    label { font-weight: 700; display:block; margin-bottom: 6px; }
    .helper { color: var(--muted); font-size: 13px; margin-bottom: 10px; display:block; }
    input, select, textarea {
      width: 100%;
      padding: 11px 12px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: #fefefe;
      font-size: 14px;
      font-family: inherit;
      transition: border-color .12s ease, box-shadow .12s ease, background .12s ease;
      min-height: 42px;
      line-height: 1.3;
    }
    input:focus, select:focus, textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(14,165,233,0.16);
      outline: none;
      background: #fff;
    }
    select {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2362748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m4 6 4 4 4-4'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 34px;
      cursor: pointer;
      background-color: #fefefe;
    }
    select::-ms-expand { display: none; }
    select option {
      padding: 8px 10px;
      border-radius: 8px;
      margin: 4px;
    }
    select option:hover,
    select option:focus,
    select option:checked {
      background: #e0f2fe;
      color: #0f172a;
    }
    input[type="date"] {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='%2362748b' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4.5' width='12' height='10' rx='1.5'/%3E%3Cpath d='M3 7.5h12M6.5 3v3m5-3v3'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 38px;
      cursor: pointer;
    }
    input[type="date"]::-webkit-calendar-picker-indicator {
      opacity: 0;
      cursor: pointer;
    }
    input[type="date"]::-webkit-datetime-edit {
      padding: 6px 0;
      border-radius: 8px;
    }
    input[type="date"]::-webkit-datetime-edit-fields-wrapper {
      background: #f8fafc;
      padding: 4px 6px;
      border-radius: 8px;
    }
    input[type="date"]::-webkit-datetime-edit-text {
      color: #475569;
      padding: 0 2px;
    }
    input[type="date"]::-webkit-inner-spin-button {
      display: none;
    }
    textarea { min-height: 120px; resize: vertical; }
    .row { display:flex; gap: 16px; flex-wrap: wrap; }
    .col { flex:1; min-width: 240px; }

    /* Pills (used for project sub-nav, quick filters, and small labels) */
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--ink);
      font-weight: 800;
      font-size: 13px;
      cursor: pointer;
      transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
    }
    .pill:hover { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(2,6,23,0.08); text-decoration: none; }
    .pill.ghost { background:#f8fafc; color:#475569; }
    .pill.active { background:#e0f2fe; border-color:#bae6fd; color:#075985; }
    .pill.sm { padding: 6px 10px; font-size: 12px; }

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

    /* Tables */
    .table-wrap { overflow:auto; border: 1px solid var(--border); border-radius: 14px; box-shadow: none; }
    table.table { width:100%; border-collapse: separate; border-spacing: 0; }
    table.table th, table.table td { padding: 10px 12px; border-bottom: 1px solid var(--border); text-align:left; vertical-align: top; font-size: 14px; }
    table.table th { position: sticky; top: 0; background: #f8fafc; font-weight: 900; z-index: 1; }
    table.table tbody tr:hover { background: #f8fafc; }

    /* Sub navigation (project module nav) */
    .subnav { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin: 10px 0 16px; }
    .subnav a { text-decoration:none; }

    /* Modals */
    .modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.35); backdrop-filter: blur(2px); z-index:40; display:none; }
    .modal-backdrop.show { display:block; }
    .modal { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); width: min(560px, 94vw); background:#fff; border:1px solid var(--border); border-radius:16px; box-shadow:0 24px 60px rgba(15,23,42,.18); padding:18px; z-index:45; display:none; }
    .modal.show { display:block; }
    .modal header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; gap:10px; }

    /* Pagination */
    .pagination { display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
    .pagination a, .pagination span {
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width: 40px;
      height: 40px;
      padding: 0 12px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--ink);
      font-weight: 800;
      text-decoration:none;
    }
    .pagination a:hover { background:#f8fafc; }
    .pagination .active { background:#e0f2fe; border-color:#bae6fd; color:#075985; }
    .pagination .disabled { color: var(--muted); background:#f8fafc; }

    /* Select2 polish */
    .select2-container--default .select2-selection--single {
      height: 42px;
      border-radius: 10px;
      border: 1px solid var(--border);
      padding: 6px 10px;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 30px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; right: 10px; }
    .select2-dropdown { border: 1px solid var(--border); border-radius: 12px !important; box-shadow: 0 20px 40px rgba(15,23,42,.12); overflow: hidden; }
    .select2-results__option { padding: 8px 12px; border-radius: 8px; margin: 4px 6px; }
    .select2-results__option--highlighted { background: #e0f2fe !important; color: #0f172a !important; }

    /* Alerts */
    .alert { border-radius: 14px; border: 1px solid var(--border); background: #fff; padding: 12px 14px; }
    .alert.error { border-color:#fecdd3; background:#fff1f2; color:#9f1239; }
    .alert.info { border-color:#bfdbfe; background:#eff6ff; color:#075985; }

    /* Responsive */
    @media (max-width: 960px) {
      .sidebar { position: fixed; left: 0; top: 0; transform: translateX(-105%); transition: transform .18s ease; z-index: 30; }
      .sidebar.open { transform: translateX(0); }
      .sidebar-toggle { display:inline-flex; }
      .search-box { display:none; }
      main.content { padding: 0 16px 80px; }
    }
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
  @stack('styles')
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <span>PO Assist</span>
        <span class="brand-pill">MVP</span>
      </div>
      <div class="nav-section">
        <div class="nav-label">Dashboards</div>
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9"/><path d="M9 21V9h6v12"/></svg>
          Workspace
        </a>
        <a class="nav-link {{ request()->routeIs('dashboard.product') ? 'active' : '' }}" href="{{ route('dashboard.product') }}" @if(request()->routeIs('dashboard.product')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15v2"/><path d="M11 11v6"/><path d="M15 7v10"/><path d="M19 9v8"/></svg>
          Product
        </a>
        <a class="nav-link {{ request()->routeIs('dashboard.tasks') ? 'active' : '' }}" href="{{ route('dashboard.tasks') }}" @if(request()->routeIs('dashboard.tasks')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Tasks
        </a>
        <a class="nav-link {{ request()->routeIs('dashboard.workload') ? 'active' : '' }}" href="{{ route('dashboard.workload') }}" @if(request()->routeIs('dashboard.workload')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"/><path d="M3 12h18"/><path d="M7 7h10v10H7z"/></svg>
          Workload
        </a>
      </div>
      <div class="nav-section">
        <div class="nav-label">Projects</div>
        <a class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}" href="{{ route('admin.projects.index') }}" @if(request()->routeIs('admin.projects.*')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"/><path d="M3 12h18"/><path d="M3 17h18"/></svg>
          All projects
        </a>
      </div>
      <div class="nav-section">
        <div class="nav-label">Clients</div>
        <a class="nav-link {{ request()->routeIs('clients.customers.*') ? 'active' : '' }}" href="{{ route('clients.customers.index') }}" @if(request()->routeIs('clients.customers.*')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M4 21v-2a4 4 0 0 1 3-3.87"/><circle cx="12" cy="7" r="4"/></svg>
          Customers
        </a>
        <a class="nav-link {{ request()->routeIs('clients.contacts.*') ? 'active' : '' }}" href="{{ route('clients.contacts.index') }}" @if(request()->routeIs('clients.contacts.*')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
          Contacts
        </a>
        @if(auth()->user()?->role?->name === 'Admin')
          <a class="nav-link {{ request()->routeIs('admin.config.email_templates.*') ? 'active' : '' }}" href="{{ route('admin.config.email_templates.index') }}" @if(request()->routeIs('admin.config.email_templates.*')) aria-current="page" @endif>
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M4 8h16"/><path d="M8 4v16"/></svg>
            Email templates
          </a>
        @endif
      </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <div class="main">
      <div class="topbar">
        <button class="icon-btn sidebar-toggle" type="button" data-sidebar-toggle aria-label="Open menu">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/>
          </svg>
        </button>
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
          <div class="alert error">
            <strong>Validation errors</strong>
            <ul style="margin:8px 0 0; padding-left:18px;">
              @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        @if (session('status'))
          <div class="alert info" style="margin-top:12px;">
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
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    (function() {
      const initSelect2 = () => {
        if (!window.jQuery || !window.jQuery.fn?.select2) return;
        jQuery('.select2').select2({ width: '100%', minimumResultsForSearch: 5 });
      };
      if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initSelect2, 0);
      } else {
        document.addEventListener('DOMContentLoaded', initSelect2);
      }
    })();
  </script>
  <script>
    // Mobile sidebar toggle.
    (function() {
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      const toggleBtn = document.querySelector('[data-sidebar-toggle]');
      if (!sidebar || !backdrop || !toggleBtn) return;

      const open = () => { sidebar.classList.add('open'); backdrop.classList.add('show'); };
      const close = () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); };

      toggleBtn.addEventListener('click', () => {
        if (sidebar.classList.contains('open')) close(); else open();
      });

      backdrop.addEventListener('click', close);
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    })();
  </script>
  @stack('scripts')
</body>
</html>
