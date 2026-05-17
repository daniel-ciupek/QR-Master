<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Restricted — {{ $team->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 480px; width: 100%; padding: 2.5rem; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; }
        p { color: #64748b; font-size: 0.95rem; line-height: 1.6; margin-bottom: 0.75rem; }
        .ip { font-family: monospace; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem 1rem; font-size: 0.9rem; display: inline-block; margin: 0.5rem 0; }
        a { color: #3b82f6; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔒</div>
        <h1>Access Restricted</h1>
        <p>Your IP address is not in the allowlist for the <strong>{{ $team->name }}</strong> workspace.</p>
        <div class="ip">{{ $ip }}</div>
        <p>Contact your workspace administrator to add your IP address.</p>
        <a href="{{ route('login') }}">← Return to login</a>
    </div>
</body>
</html>
