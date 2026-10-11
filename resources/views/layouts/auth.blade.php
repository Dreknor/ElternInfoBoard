<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = $settings->app_name ?? 'Elterninfo';

        $faviconUrl = asset('img/app_logo.png');
        if ($settings->favicon !== 'app_logo.png' && \Illuminate\Support\Facades\Storage::disk('public')->exists('img/' . $settings->favicon)) {
            $faviconUrl = url('storage/img/' . $settings->favicon);
        }

        // Logo der Installation (wie im Hauptlayout)
        $logoUrl = ($settings->logo ?? 'logo.png') === 'logo.png'
            ? asset('img/logo.png')
            : url('storage/img/' . $settings->logo);

        // Browserleiste auf dem Smartphone an die Theme-Farbe anpassen
        $themeColor = app(\App\Services\ThemeService::class)->resolveActive()->variables()['--color-primary-dark'] ?? '#1d4ed8';
    @endphp
    <title>@yield('title') – {{ $appName }}</title>
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="image/x-icon">
    <meta name="theme-color" content="{{ $themeColor }}">

    {{-- x-cloak vor Alpine --}}
    <style>[x-cloak]{display:none!important}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Montserrat --}}
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link href="{{ asset('/css/all.css') }}?v=1" rel="stylesheet">

    {{-- Tailwind via Vite --}}
    @vite(['resources/css/app.css'])

    {{-- Theme CSS Custom Properties (Admin-gewähltes Theme) --}}
    <x-theme-vars />

    <style>
        /*
         * Anmelde-Seiten – alle Farben stammen aus dem aktiven Theme (x-theme-vars).
         * Die Fallbacks entsprechen dem Standard-Theme.
         */
        :root {
            --lg-primary:      var(--color-primary, #2563eb);
            --lg-primary-dark: var(--color-primary-dark, #1d4ed8);
            --lg-secondary:    var(--color-secondary, #6366f1);
            --lg-surface:      var(--color-card-bg, #ffffff);
            --lg-border:       var(--color-card-border, #e5e7eb);
            --lg-text:         var(--color-text-primary, #111827);
            --lg-muted:        var(--color-text-secondary, #6b7280);
            --lg-input-bg:     var(--color-input-bg, #ffffff);
            --lg-input-border: var(--color-input-border, #d1d5db);
            --lg-placeholder:  var(--color-input-placeholder, #9ca3af);
            /* Akzent-Textfarbe: Primärfarbe in Richtung Textfarbe gemischt – lesbar auf hellen und dunklen Themes */
            --lg-ink:          color-mix(in srgb, var(--lg-primary-dark) 70%, var(--lg-text));
            --lg-ring:         color-mix(in srgb, var(--lg-primary) 25%, transparent);
            --lg-radius:       14px;
        }

        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Montserrat', system-ui, -apple-system, sans-serif !important;
            background: var(--lg-primary-dark);
            color: var(--lg-text);
            -webkit-tap-highlight-color: transparent;
        }

        /* ===== Grundgerüst: eine durchgehende Fläche ===== */
        .lg-page {
            position: relative;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background:
                radial-gradient(120% 70% at 100% 0%, color-mix(in srgb, var(--lg-secondary) 55%, transparent) 0%, transparent 60%),
                radial-gradient(90% 60% at 0% 100%, color-mix(in srgb, var(--lg-primary-dark) 80%, #000) 0%, transparent 70%),
                linear-gradient(155deg, var(--lg-primary-dark) 0%, var(--lg-primary) 55%, color-mix(in srgb, var(--lg-primary) 55%, var(--lg-secondary)) 100%);
        }

        /* Dezentes Punkteraster */
        .lg-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.18) 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: linear-gradient(180deg, #000 0%, transparent 55%);
                    mask-image: linear-gradient(180deg, #000 0%, transparent 55%);
            pointer-events: none;
        }

        /* Weiche Lichtflecken */
        .lg-orb {
            position: absolute;
            border-radius: 9999px;
            filter: blur(60px);
            pointer-events: none;
        }
        .lg-orb--1 { width: 18rem; height: 18rem; top: -6rem; left: -6rem; background: rgba(255,255,255,.14); }
        .lg-orb--2 { width: 14rem; height: 14rem; top: 30%; right: -5rem; background: color-mix(in srgb, var(--lg-secondary) 60%, transparent); }

        /* ===== Markenbereich ===== */
        .lg-brand {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: #fff;
            padding: calc(1.75rem + env(safe-area-inset-top)) 1.5rem 1.5rem;
        }
        .lg-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .5rem .75rem;
            min-width: 4rem;
            min-height: 4rem;
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 10px 30px -8px rgba(0,0,0,.35), 0 0 0 6px rgba(255,255,255,.15);
            margin-bottom: .9rem;
        }
        .lg-logo img { max-height: 2.75rem; max-width: 11rem; width: auto; object-fit: contain; display: block; }
        .lg-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.15;
            text-shadow: 0 1px 2px rgba(0,0,0,.2);
        }
        .lg-tagline {
            margin: .35rem 0 0;
            font-size: .875rem;
            line-height: 1.5;
            color: rgba(255,255,255,.85);
            max-width: 22rem;
        }
        .lg-pills { display: none; }
        .lg-copy-desktop { display: none; }

        /* ===== Formular-Sheet ===== */
        .lg-sheet {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--lg-surface);
            border-radius: 1.75rem 1.75rem 0 0;
            box-shadow: 0 -12px 40px -12px rgba(0,0,0,.35);
            padding: .75rem 1.5rem calc(1.5rem + env(safe-area-inset-bottom));
            animation: lg-slide-up .5s cubic-bezier(.2,.8,.2,1) both;
        }
        .lg-handle {
            width: 2.5rem;
            height: .3rem;
            border-radius: 9999px;
            background: var(--lg-border);
            margin: 0 auto 1.25rem;
        }
        .lg-sheet-inner {
            width: 100%;
            max-width: 24rem;
            margin: 0 auto;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .lg-heading { margin: 0; font-size: 1.4rem; font-weight: 800; letter-spacing: -.01em; color: var(--lg-text); }
        .lg-sub { margin: .25rem 0 1.25rem; font-size: .875rem; color: var(--lg-muted); }

        /* ===== Formularelemente ===== */
        .lg-form { display: flex; flex-direction: column; gap: 1rem; }
        .lg-label { display: block; font-size: .8rem; font-weight: 600; color: var(--lg-text); margin-bottom: .4rem; }
        .lg-field { position: relative; }
        .lg-field-icon {
            position: absolute; inset-block: 0; left: 0;
            display: flex; align-items: center; padding-left: 1rem;
            color: var(--lg-placeholder); font-size: .9rem; pointer-events: none;
            transition: color .2s;
        }
        .lg-input {
            width: 100%;
            height: 3.25rem;
            padding: 0 1rem 0 2.75rem;
            font-size: 1rem; /* ≥16px verhindert Auto-Zoom unter iOS */
            font-family: inherit;
            color: var(--lg-text);
            background: var(--lg-input-bg);
            border: 1.5px solid var(--lg-input-border);
            border-radius: var(--lg-radius);
            outline: none;
            transition: border-color .2s, box-shadow .2s, background-color .2s;
        }
        .lg-input::placeholder { color: var(--lg-placeholder); }
        .lg-input:focus { border-color: var(--lg-primary); box-shadow: 0 0 0 4px var(--lg-ring); }
        .lg-field:focus-within .lg-field-icon { color: var(--lg-primary); }
        .lg-input--pw { padding-right: 3rem; }
        .lg-input.is-invalid { border-color: #ef4444; background: color-mix(in srgb, #ef4444 6%, var(--lg-input-bg)); }
        .lg-input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(239,68,68,.2); }
        .lg-error { margin: .4rem 0 0; font-size: .78rem; color: #dc2626; display: flex; align-items: center; gap: .35rem; }

        .lg-eye {
            position: absolute; inset-block: 0; right: 0;
            width: 3rem; display: flex; align-items: center; justify-content: center;
            background: none; border: 0; cursor: pointer;
            color: var(--lg-placeholder); border-radius: 0 var(--lg-radius) var(--lg-radius) 0;
            transition: color .2s;
        }
        .lg-eye:hover, .lg-eye:focus-visible { color: var(--lg-primary); outline: none; }

        .lg-row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-block: -.4rem; }
        .lg-check { display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; user-select: none; font-size: .8125rem; color: var(--lg-muted); min-height: 2.75rem; white-space: nowrap; }
        .lg-check input { width: 1.15rem; height: 1.15rem; accent-color: var(--lg-primary); cursor: pointer; }
        .lg-link { font-size: .8125rem; font-weight: 600; white-space: nowrap; color: var(--lg-ink); text-decoration: none; min-height: 2.75rem; display: inline-flex; align-items: center; }
        .lg-link:hover { text-decoration: underline; text-underline-offset: 3px; }

        /* ===== Buttons ===== */
        .lg-btn {
            width: 100%;
            height: 3.25rem;
            display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
            border-radius: var(--lg-radius);
            font-family: inherit; font-size: .95rem; font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s, box-shadow .2s, background-color .2s, color .2s, filter .2s;
        }
        .lg-btn:active { transform: scale(.98); }
        .lg-btn:focus-visible { outline: none; box-shadow: 0 0 0 4px var(--lg-ring); }

        .lg-btn--primary {
            border: 0;
            color: #fff;
            text-shadow: 0 1px 1px rgba(0,0,0,.2);
            background: linear-gradient(135deg, var(--lg-primary) 0%, var(--lg-primary-dark) 100%);
            box-shadow: 0 10px 20px -8px color-mix(in srgb, var(--lg-primary) 70%, transparent);
            margin-top: .25rem;
        }
        .lg-btn--primary:hover { filter: brightness(1.06); box-shadow: 0 14px 24px -10px color-mix(in srgb, var(--lg-primary) 80%, transparent); }

        .lg-btn--ghost {
            background: color-mix(in srgb, var(--lg-primary) 8%, var(--lg-surface));
            color: var(--lg-ink);
            border: 1.5px solid color-mix(in srgb, var(--lg-primary) 30%, transparent);
        }
        .lg-btn--ghost:hover { background: color-mix(in srgb, var(--lg-primary) 14%, var(--lg-surface)); }

        .lg-btn--neutral {
            border: 0;
            background: var(--lg-text);
            color: var(--lg-surface);
        }
        .lg-btn--neutral:hover { filter: brightness(1.15); }

        .lg-hint { margin: -.35rem 0 0; font-size: .75rem; text-align: center; color: var(--lg-muted); }

        .lg-divider { display: flex; align-items: center; gap: .75rem; margin: 1.5rem 0 1rem; }
        .lg-divider::before, .lg-divider::after { content: ""; flex: 1; height: 1px; background: var(--lg-border); }
        .lg-divider span { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--lg-muted); white-space: nowrap; }

        /* ===== Meldungen (semantische Farben, an Theme-Hintergrund angepasst) ===== */
        .lg-alert {
            --tone: #2563eb;
            display: flex; align-items: flex-start; gap: .75rem;
            padding: .85rem 1rem; margin-bottom: 1.25rem;
            border-radius: var(--lg-radius);
            font-size: .875rem; line-height: 1.45;
            background: color-mix(in srgb, var(--tone) 10%, var(--lg-surface));
            border: 1px solid color-mix(in srgb, var(--tone) 30%, transparent);
            color: color-mix(in srgb, var(--tone) 65%, var(--lg-text));
        }
        .lg-alert--success { --tone: #16a34a; }
        .lg-alert--danger  { --tone: #dc2626; }
        .lg-alert--warning { --tone: #d97706; }
        .lg-alert i.lg-alert-icon { color: var(--tone); margin-top: .15rem; }
        .lg-alert button { background: none; border: 0; color: inherit; opacity: .55; cursor: pointer; padding: 0 .1rem; }
        .lg-alert button:hover { opacity: 1; }

        .lg-copy { margin-top: auto; padding-top: 1.25rem; text-align: center; font-size: .75rem; color: var(--lg-muted); }

        @keyframes lg-slide-up { from { transform: translateY(40px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes lg-fade-in  { from { transform: translateX(24px); opacity: 0; } to { transform: none; opacity: 1; } }

        /* ===== Kleine Smartphones: Markenbereich kompakter ===== */
        @media (max-height: 700px) and (max-width: 1023px) {
            .lg-brand { padding-top: calc(1.25rem + env(safe-area-inset-top)); padding-bottom: 1.25rem; }
            .lg-logo { min-height: 3.5rem; min-width: 3.5rem; padding: .5rem .75rem; margin-bottom: .75rem; }
            .lg-logo img { max-height: 2.25rem; }
            .lg-tagline { display: none; }
        }

        /* ===== Desktop: gleiche Elemente, nebeneinander ===== */
        @media (min-width: 1024px) {
            .lg-page { flex-direction: row; }
            .lg-page::before { -webkit-mask-image: radial-gradient(circle at 30% 40%, #000 0%, transparent 70%); mask-image: radial-gradient(circle at 30% 40%, #000 0%, transparent 70%); }
            .lg-orb--1 { width: 28rem; height: 28rem; top: -8rem; left: -8rem; }
            .lg-orb--2 { width: 24rem; height: 24rem; top: auto; bottom: -6rem; right: 35%; }

            .lg-brand { flex: 1; justify-content: center; padding: 3rem; }
            .lg-logo { padding: 1.1rem 1.4rem; min-width: 6rem; min-height: 6rem; border-radius: 1.75rem; margin-bottom: 2rem; box-shadow: 0 20px 50px -12px rgba(0,0,0,.4), 0 0 0 10px rgba(255,255,255,.12); }
            .lg-logo img { max-height: 4.5rem; max-width: 15rem; }
            .lg-title { font-size: clamp(2.5rem, 3.5vw, 3.25rem); }
            .lg-tagline { font-size: 1.15rem; margin-top: 1rem; max-width: 28rem; }

            .lg-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin-top: 2.5rem; }
            .lg-pill {
                display: inline-flex; align-items: center; gap: .5rem;
                padding: .55rem 1.1rem; border-radius: 9999px;
                font-size: .875rem; font-weight: 500; color: rgba(255,255,255,.95);
                background: rgba(255,255,255,.12);
                border: 1px solid rgba(255,255,255,.22);
                -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
            }
            .lg-pill i { font-size: .8rem; opacity: .9; }

            .lg-copy-desktop { display: block; position: absolute; bottom: 1.5rem; left: 0; right: 0; text-align: center; font-size: .75rem; color: rgba(255,255,255,.6); }

            .lg-sheet {
                flex: 0 0 auto;
                width: min(42%, 34rem);
                justify-content: center;
                border-radius: 2.5rem 0 0 2.5rem;
                box-shadow: -20px 0 60px -20px rgba(0,0,0,.45);
                padding: 3rem 3.5rem;
                animation-name: lg-fade-in;
            }
            .lg-handle { display: none; }
            .lg-sheet-inner { flex: 0 0 auto; }
            .lg-heading { font-size: 1.75rem; }
            .lg-sub { margin-bottom: 2rem; }
            .lg-copy { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .lg-sheet { animation: none; }
            .lg-btn, .lg-input { transition: none; }
        }

        /* Info-Kachel im Markenbereich (Passwort-Seiten, nur Desktop) */
        .lg-info { display: none; }
        @media (min-width: 1024px) {
            .lg-info {
                display: inline-flex; align-items: center; gap: .9rem; text-align: left;
                margin-top: 2.5rem; padding: 1rem 1.4rem; border-radius: 1.25rem;
                background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22);
                -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
            }
            .lg-info-icon { width: 2.75rem; height: 2.75rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: .9rem; background: rgba(255,255,255,.2); font-size: 1.1rem; }
            .lg-info strong { display: block; font-size: .9rem; }
            .lg-info span { display: block; font-size: .8rem; color: rgba(255,255,255,.75); margin-top: .15rem; }
        }

        /* Sekundärer Link unter dem Formular (z. B. „Zurück zur Anmeldung“) */
        .lg-back { display: flex; justify-content: center; margin-top: 1.25rem; }
        .lg-back .lg-link { gap: .45rem; }
        .lg-field-hint { margin: .4rem 0 0; font-size: .75rem; color: var(--lg-muted); }
    </style>
</head>

<body class="antialiased">

<div class="lg-page">
    <span class="lg-orb lg-orb--1" aria-hidden="true"></span>
    <span class="lg-orb lg-orb--2" aria-hidden="true"></span>

    {{-- -------- Markenbereich (mobil oben, Desktop links) -------- --}}
    <header class="lg-brand">
        <div class="lg-logo">
            <img src="{{ $logoUrl }}" alt="{{ $appName }}">
        </div>

        <h1 class="lg-title">{{ $appName }}</h1>
        <p class="lg-tagline">Ihr digitales Kommunikationsportal für Schule und Elternhaus</p>

        {{-- Zusatzinhalt nur auf dem Desktop (Feature-Pills oder Info-Kachel) --}}
        @hasSection('brand')
            @yield('brand')
        @else
            <div class="lg-pills" aria-hidden="true">
                <span class="lg-pill"><i class="fas fa-newspaper"></i> Nachrichten</span>
                <span class="lg-pill"><i class="fas fa-calendar"></i> Termine</span>
                <span class="lg-pill"><i class="fas fa-reply"></i> Rückmeldungen</span>
            </div>
        @endif

        <p class="lg-copy-desktop">&copy; {{ date('Y') }} {{ $appName }}</p>
    </header>

    {{-- -------- Formular-Sheet (mobil unten, Desktop rechts) -------- --}}
    <main class="lg-sheet">
        <div class="lg-handle" aria-hidden="true"></div>

        <div class="lg-sheet-inner">
            @yield('content')

            <p class="lg-copy">&copy; {{ date('Y') }} {{ $appName }}</p>
        </div>
    </main>
</div>

</body>
</html>
