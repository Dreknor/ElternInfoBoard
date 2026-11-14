<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Wartungsarbeiten – {{ config('app.name', 'ElternInfo') }}</title>
    {{-- Bewusst ohne externe CSS/JS: während eines Updates sind Assets evtl. nicht verfügbar. --}}
    <style>
        :root {
            --bg-from: #667eea;
            --bg-to: #764ba2;
            --card: #ffffff;
            --text: #1a202c;
            --muted: #64748b;
            --track: #e2e8f0;
            --pill-bg: #f1f5f9;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-from: #2d3561;
                --bg-to: #3b2552;
                --card: #1e2130;
                --text: #f1f5f9;
                --muted: #a0aec0;
                --track: #2d3348;
                --pill-bg: #272b3d;
            }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg-from) 0%, var(--bg-to) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: var(--text);
        }
        .card {
            background: var(--card);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            padding: 2.75rem 2.25rem 2.25rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.5s ease both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .logo {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            object-fit: contain;
            display: block;
            margin: 0 auto 1.5rem;
        }
        .gears {
            width: 96px;
            height: 96px;
            margin: 0 auto 1.25rem;
            position: relative;
        }
        .gears svg { position: absolute; }
        .gear-big   { width: 64px; height: 64px; left: 0; top: 8px; animation: spin 6s linear infinite; }
        .gear-small { width: 40px; height: 40px; right: 2px; top: 0; animation: spin 4s linear infinite reverse; }
        .gear-big path, .gear-small path { fill: url(#g); }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
        }
        .progress {
            height: 6px;
            border-radius: 999px;
            background: var(--track);
            overflow: hidden;
            margin: 1.75rem 0 1.25rem;
        }
        .progress span {
            display: block;
            height: 100%;
            width: 40%;
            border-radius: inherit;
            background: linear-gradient(90deg, #667eea, #764ba2);
            animation: slide 1.6s ease-in-out infinite;
        }
        @keyframes slide {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--pill-bg);
            color: var(--muted);
            font-size: 0.85rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse { 50% { opacity: 0.35; } }
        .btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.7rem 1.75rem;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            font: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .footer {
            margin-top: 1.75rem;
            font-size: 0.8rem;
            color: var(--muted);
            opacity: 0.8;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; }
        }
        @media (max-width: 420px) {
            .card { padding: 2rem 1.25rem 1.75rem; }
            h1 { font-size: 1.35rem; }
        }
    </style>
</head>
<body>
<main class="card">
    <img src="{{ asset('img/'.config('app.logo_small', 'app_logo.png')) }}" alt="{{ config('app.name', 'ElternInfo') }}"
         class="logo" onerror="this.style.display='none'">

    <div class="gears" aria-hidden="true">
        <svg width="0" height="0" style="position:absolute">
            <defs>
                <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#667eea"/>
                    <stop offset="1" stop-color="#764ba2"/>
                </linearGradient>
            </defs>
        </svg>
        <svg class="gear-big" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96a7.03 7.03 0 0 0-1.62-.94l-.36-2.54a.48.48 0 0 0-.48-.41h-3.84a.48.48 0 0 0-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.48.48 0 0 0-.59.22L2.74 8.87a.47.47 0 0 0 .12.61l2.03 1.58c-.04.31-.07.64-.07.94s.02.63.06.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.47.47 0 0 0-.12-.61l-2.01-1.58zM12 15.6A3.6 3.6 0 1 1 12 8.4a3.6 3.6 0 0 1 0 7.2z"/></svg>
        <svg class="gear-small" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96a7.03 7.03 0 0 0-1.62-.94l-.36-2.54a.48.48 0 0 0-.48-.41h-3.84a.48.48 0 0 0-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.48.48 0 0 0-.59.22L2.74 8.87a.47.47 0 0 0 .12.61l2.03 1.58c-.04.31-.07.64-.07.94s.02.63.06.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.47.47 0 0 0-.12-.61l-2.01-1.58zM12 15.6A3.6 3.6 0 1 1 12 8.4a3.6 3.6 0 0 1 0 7.2z"/></svg>
    </div>

    <h1>Wir aktualisieren gerade das System</h1>
    <p>
        {{ config('app.name', 'ElternInfo') }} wird in diesem Moment auf eine neue Version gebracht.
        Das dauert meist nur wenige Minuten – Ihre Daten sind dabei sicher.
    </p>

    <div class="progress" aria-hidden="true"><span></span></div>

    <div class="status" role="status" aria-live="polite">
        <span class="dot"></span>
        <span id="status-text">Wir prüfen automatisch, ob wir wieder da sind …</span>
    </div>

    <div>
        <button type="button" class="btn" onclick="location.reload()">Jetzt erneut versuchen</button>
    </div>

    <div class="footer">Vielen Dank für Ihre Geduld!</div>
</main>

<script>
    (function () {
        var interval = 30, remaining = interval;
        var text = document.getElementById('status-text');

        function check() {
            fetch(location.href, { method: 'HEAD', cache: 'no-store' })
                .then(function (r) { if (r.status !== 503) { location.reload(); } })
                .catch(function () {});
        }

        setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                remaining = interval;
                text.textContent = 'Prüfe Verfügbarkeit …';
                check();
                return;
            }
            text.textContent = 'Nächste automatische Prüfung in ' + remaining + ' Sek.';
        }, 1000);
    })();
</script>
</body>
</html>
