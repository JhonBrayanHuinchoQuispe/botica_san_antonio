<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin:0; font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background:#0f172a; color:#e5e7eb; }
        .space-bg { position: fixed; inset:0; background:
            radial-gradient( circle at 20% 30%, rgba(37,99,235,.15), transparent 40%),
            radial-gradient( circle at 80% 20%, rgba(16,185,129,.12), transparent 45%),
            radial-gradient( circle at 30% 80%, rgba(244,63,94,.12), transparent 40%),
            #0f172a; }
        .container { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; position:relative; z-index:1; }
        .card { max-width:740px; width:100%; background:#0b1220; border:1px solid #1f2937; border-radius:18px; padding:40px 36px; box-shadow: 0 20px 40px rgba(0,0,0,.45); text-align:center; }
        .illust { margin:0 auto 12px; width:120px; height:120px; }
        .code-big { font-size:64px; font-weight:800; letter-spacing:2px; color:#93c5fd; margin:10px 0 6px; }
        h1 { margin:0 0 8px; font-size:20px; color:#f3f4f6; }
        p { margin:6px 0 0; font-size:14px; color:#cbd5e1; }
        .actions { margin-top:22px; display:flex; gap:12px; justify-content:center; }
        .btn { background:#2563eb; color:#fff; border:none; border-radius:12px; padding:12px 18px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .footer { margin-top:22px; font-size:12px; color:#94a3b8; }
    </style>
    @yield('head')
    </head>
<body>
    <div class="space-bg"></div>
    <div class="container">
        <div class="card">
            <div class="illust">
                @yield('illustration')
            </div>
            <div class="code-big">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <div class="actions">
                @yield('actions')
            </div>
            @hasSection('footer')
                <div class="footer">@yield('footer')</div>
            @endif
        </div>
    </div>
</body>
</html>