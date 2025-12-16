<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login | PO Assist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { margin:0; font-family: 'Segoe UI', 'Helvetica Neue', sans-serif; background: linear-gradient(135deg,#e0f2fe,#f8fafc); color:#0f172a; }
    .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .card { background:#fff; border-radius:16px; box-shadow:0 18px 48px rgba(15,23,42,0.12); max-width:420px; width:100%; padding:28px; border:1px solid #e2e8f0; }
    h1 { margin:0 0 6px; font-size:24px; }
    p { margin:0 0 16px; color:#475569; }
    label { display:block; font-weight:700; margin-bottom:6px; }
    input { width:100%; padding:11px 12px; border:1px solid #e2e8f0; border-radius:12px; font-size:14px; }
    .btn { width:100%; margin-top:16px; padding:12px 14px; border:none; border-radius:12px; background:linear-gradient(135deg,#0ea5e9,#0284c7); color:#fff; font-weight:700; cursor:pointer; }
    .error { background:#fef2f2; color:#b91c1c; border:1px solid #fecdd3; padding:10px 12px; border-radius:12px; margin-bottom:12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Welcome back</h1>
      <p>Sign in to manage projects, customers, and contacts.</p>

      @if ($errors->any())
        <div class="error">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <div style="margin-bottom:12px;">
          <label>Email</label>
          <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div style="margin-bottom:6px;">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <button class="btn" type="submit">Sign in</button>
      </form>
    </div>
  </div>
</body>
</html>
