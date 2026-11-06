@php
    $appName = $settings->app_name ?: config('app.name');
    $primary = app(\App\Services\ThemeService::class)->resolveActive()->variables()['--color-primary'] ?? '#2563eb';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $primary }}">
    <title>Chat – {{ $appName }}</title>
    <style>
        :root { color-scheme: light dark; --primary: {{ $primary }}; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #111827; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #e5e7eb; } }
        main { max-width: 28rem; padding: 1.5rem; text-align: center; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; color: var(--primary); }
        p { margin: 0; font-size: .95rem; opacity: .8; line-height: 1.5; }
    </style>
</head>
<body>
    <main>
        <h1>Chat</h1>
        <p>Der Chat von {{ $appName }} wird geladen. Falls er sich nicht von selbst öffnet, tippen Sie auf das Chat-Symbol am Bildschirmrand.</p>
    </main>
{!! $snippet !!}
</body>
</html>
