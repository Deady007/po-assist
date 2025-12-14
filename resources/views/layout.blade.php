<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>PO Assist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    .nav a { margin-right: 12px; }
    .card { border: 1px solid #ddd; padding: 16px; border-radius: 8px; margin: 16px 0; }
    label { font-weight: 600; display:block; margin-top: 12px; }
    input, select, textarea { width: 100%; padding: 8px; margin-top: 6px; }
    textarea { font-family: inherit; }
    button { padding: 10px 14px; margin-top: 12px; cursor: pointer; }
    .row { display:flex; gap: 16px; }
    .col { flex:1; }
    .muted { color:#666; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="nav">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('emails.product.form') }}">Product Update</a>
    <a href="{{ route('history') }}">History</a>
  </div>

  <hr>

  @if ($errors->any())
    <div class="card" style="border-color:#e99;">
      <strong>Validation errors:</strong>
      <ul>
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @yield('content')
</body>
</html>
