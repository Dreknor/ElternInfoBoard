<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $appName = $settings->app_name ?? config('app.name');
        $logoUrl = ($settings->logo ?? 'logo.png') === 'logo.png'
            ? asset('img/logo.png')
            : url('storage/img/' . $settings->logo);
        $webcalUrl = preg_replace('#^https?://#', 'webcal://', $icsUrl);
    @endphp
    <title>Termine – {{ $appName }}</title>
    <link rel="alternate" type="text/calendar" title="Termine – {{ $appName }}" href="{{ $icsUrl }}">
    <link href="{{ asset('/css/all.css') }}?v=1" rel="stylesheet">
    <x-theme-vars />
    <style>
        :root {
            --pt-primary: var(--color-primary, #2563eb);
            --pt-primary-dark: var(--color-primary-dark, #1d4ed8);
            --pt-bg: var(--color-page-bg, #f3f4f6);
            --pt-surface: var(--color-card-bg, #ffffff);
            --pt-border: var(--color-card-border, #e5e7eb);
            --pt-text: var(--color-text-primary, #111827);
            --pt-muted: var(--color-text-secondary, #6b7280);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: var(--pt-bg);
               color: var(--pt-text); line-height: 1.5; }
        header { background: linear-gradient(135deg, var(--pt-primary-dark), var(--pt-primary)); color: #fff;
                 padding: 24px 16px; }
        .wrap { max-width: 760px; margin: 0 auto; }
        header .wrap { display: flex; align-items: center; gap: 16px; }
        header img { max-height: 48px; max-width: 140px; background: #fff; border-radius: 10px; padding: 6px 8px; }
        header h1 { margin: 0; font-size: 22px; }
        header p { margin: 2px 0 0; opacity: .85; font-size: 14px; }
        main { padding: 16px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 20px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px;
               font-size: 14px; font-weight: 600; text-decoration: none; border: 1.5px solid var(--pt-primary);
               color: var(--pt-primary-dark); background: var(--pt-surface); }
        .btn--primary { background: var(--pt-primary); color: #fff; }
        h2 { font-size: 15px; text-transform: uppercase; letter-spacing: .05em; color: var(--pt-muted); margin: 24px 0 8px; }
        ul.termine { list-style: none; margin: 0; padding: 0; background: var(--pt-surface);
                     border: 1px solid var(--pt-border); border-radius: 12px; overflow: hidden; }
        ul.termine li { display: flex; gap: 14px; padding: 12px 14px; border-top: 1px solid var(--pt-border); }
        ul.termine li:first-child { border-top: 0; }
        .date { flex: 0 0 52px; text-align: center; border-radius: 10px; padding: 4px 0;
                background: color-mix(in srgb, var(--pt-primary) 10%, var(--pt-surface)); color: var(--pt-primary-dark); }
        .date .d { display: block; font-size: 20px; font-weight: 700; line-height: 1.1; }
        .date .w { display: block; font-size: 12px; text-transform: uppercase; }
        .info { min-width: 0; }
        .info strong { display: block; overflow-wrap: anywhere; }
        .info span { font-size: 14px; color: var(--pt-muted); }
        .empty { background: var(--pt-surface); border: 1px solid var(--pt-border); border-radius: 12px; padding: 24px;
                 text-align: center; color: var(--pt-muted); }
        footer { text-align: center; font-size: 12px; color: var(--pt-muted); padding: 24px 16px; }
    </style>
</head>
<body>
<header>
    <div class="wrap">
        <img src="{{ $logoUrl }}" alt="">
        <div>
            <h1>Öffentliche Termine</h1>
            <p>{{ $appName }}</p>
        </div>
    </div>
</header>

<main class="wrap">
    <div class="actions">
        <a class="btn btn--primary" href="{{ $webcalUrl }}"><i class="fas fa-calendar-plus"></i> Kalender abonnieren</a>
        <a class="btn" href="{{ $icsUrl }}"><i class="fas fa-download"></i> Kalender-Datei (.ics)</a>
    </div>

    @forelse($termineNachMonat as $monat => $termine)
        <h2>{{ $monat }}</h2>
        <ul class="termine">
            @foreach($termine as $termin)
                @php
                    $start = $termin->start->timezone('Europe/Berlin')->locale('de');
                    $ende = $termin->ende->timezone('Europe/Berlin')->locale('de');
                    $mehrtaegig = ! $start->isSameDay($ende);
                @endphp
                <li>
                    <div class="date" aria-hidden="true">
                        <span class="d">{{ $start->format('d') }}</span>
                        <span class="w">{{ $start->isoFormat('dd') }}</span>
                    </div>
                    <div class="info">
                        <strong>{{ $termin->terminname }}</strong>
                        <span>
                            @if($termin->fullDay)
                                {{ $start->isoFormat('dddd, D. MMMM YYYY') }}@if($mehrtaegig) – {{ $ende->isoFormat('dddd, D. MMMM YYYY') }}@endif
                                · ganztägig
                            @elseif($mehrtaegig)
                                {{ $start->isoFormat('dd, D. MMMM YYYY, HH:mm') }} Uhr – {{ $ende->isoFormat('dd, D. MMMM YYYY, HH:mm') }} Uhr
                            @else
                                {{ $start->isoFormat('dddd, D. MMMM YYYY') }} · {{ $start->format('H:i') }}–{{ $ende->format('H:i') }} Uhr
                            @endif
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    @empty
        <div class="empty">Derzeit sind keine öffentlichen Termine geplant.</div>
    @endforelse
</main>

<footer>&copy; {{ date('Y') }} {{ $appName }}</footer>
</body>
</html>
