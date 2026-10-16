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
    <title>Öffentliche Termine – {{ $appName }}</title>
    <meta name="description" content="Aktuelle öffentliche Termine und Veranstaltungen von {{ $appName }} – auch als Kalender zum Abonnieren.">
    <link rel="canonical" href="{{ $pageUrl }}">
    <link rel="alternate" type="text/calendar" title="Öffentliche Termine – {{ $appName }}" href="{{ $icsUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Öffentliche Termine – {{ $appName }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $logoUrl }}">
    <meta property="og:locale" content="de_DE">
    {{-- schema.org/Event – wird von Suchmaschinen (Google) als Veranstaltung ausgewertet --}}
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT) !!}</script>
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
        ol.termine { list-style: none; margin: 0; padding: 0; background: var(--pt-surface);
                     border: 1px solid var(--pt-border); border-radius: 12px; overflow: hidden; }
        ol.termine > li { border-top: 1px solid var(--pt-border); }
        ol.termine > li:first-child { border-top: 0; }
        ol.termine > li:target { background: color-mix(in srgb, var(--pt-primary) 6%, var(--pt-surface)); }
        article.termin { display: flex; gap: 14px; padding: 12px 14px; }
        .date { flex: 0 0 52px; text-align: center; border-radius: 10px; padding: 4px 0;
                background: color-mix(in srgb, var(--pt-primary) 10%, var(--pt-surface)); color: var(--pt-primary-dark); }
        .date .d { display: block; font-size: 20px; font-weight: 700; line-height: 1.1; }
        .date .w { display: block; font-size: 12px; text-transform: uppercase; }
        .info { min-width: 0; }
        .info h3 { margin: 0; font-size: 16px; overflow-wrap: anywhere; }
        .info p { margin: 0; font-size: 14px; color: var(--pt-muted); }
        .ort { margin: -8px 0 16px; font-size: 14px; color: var(--pt-muted); }
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
    @if($settings->public_termine_location_name || $settings->public_termine_location_city)
        <p class="ort">
            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
            {{ collect([
                $settings->public_termine_location_name,
                $settings->public_termine_location_street,
                trim($settings->public_termine_location_postal_code.' '.$settings->public_termine_location_city),
            ])->filter()->implode(', ') }}
        </p>
    @endif

    <nav class="actions" aria-label="Kalender">
        <a class="btn btn--primary" href="{{ $webcalUrl }}"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Kalender abonnieren</a>
        <a class="btn" href="{{ $icsUrl }}" type="text/calendar" download><i class="fas fa-download" aria-hidden="true"></i> Kalender-Datei (.ics)</a>
    </nav>

    @forelse($termineNachMonat as $monat => $termine)
        @php $monatId = 'monat-'.$termine->first()->start->timezone('Europe/Berlin')->format('Y-m'); @endphp
        <section aria-labelledby="{{ $monatId }}">
            <h2 id="{{ $monatId }}">{{ $monat }}</h2>
            <ol class="termine">
                @foreach($termine as $termin)
                    @php
                        $start = $termin->start->timezone('Europe/Berlin')->locale('de');
                        $ende = $termin->ende->timezone('Europe/Berlin')->locale('de');
                        $mehrtaegig = ! $start->isSameDay($ende);
                        // Maschinenlesbare Zeitangaben: ganztägig nur Datum, sonst ISO 8601 mit Offset
                        $startIso = $termin->fullDay ? $start->toDateString() : $start->toIso8601String();
                        $endeIso = $termin->fullDay ? $ende->toDateString() : $ende->toIso8601String();
                    @endphp
                    <li id="termin-{{ $termin->id }}">
                        <article class="termin" aria-labelledby="termin-{{ $termin->id }}-titel"
                                 data-start="{{ $startIso }}" data-end="{{ $endeIso }}"
                                 data-all-day="{{ $termin->fullDay ? 'true' : 'false' }}">
                            <div class="date" aria-hidden="true">
                                <span class="d">{{ $start->format('d') }}</span>
                                <span class="w">{{ $start->isoFormat('dd') }}</span>
                            </div>
                            <div class="info">
                                <h3 id="termin-{{ $termin->id }}-titel">{{ $termin->terminname }}</h3>
                                <p>
                                    @if($termin->fullDay)
                                        <time datetime="{{ $startIso }}">{{ $start->isoFormat('dddd, D. MMMM YYYY') }}</time>@if($mehrtaegig) – <time datetime="{{ $endeIso }}">{{ $ende->isoFormat('dddd, D. MMMM YYYY') }}</time>@endif
                                        · ganztägig
                                    @elseif($mehrtaegig)
                                        <time datetime="{{ $startIso }}">{{ $start->isoFormat('dd, D. MMMM YYYY, HH:mm') }} Uhr</time> –
                                        <time datetime="{{ $endeIso }}">{{ $ende->isoFormat('dd, D. MMMM YYYY, HH:mm') }} Uhr</time>
                                    @else
                                        <time datetime="{{ $startIso }}">{{ $start->isoFormat('dddd, D. MMMM YYYY') }} · {{ $start->format('H:i') }}</time>–<time datetime="{{ $endeIso }}">{{ $ende->format('H:i') }}</time> Uhr
                                    @endif
                                </p>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ol>
        </section>
    @empty
        <p class="empty">Derzeit sind keine öffentlichen Termine geplant.</p>
    @endforelse
</main>

<footer>&copy; {{ date('Y') }} {{ $appName }}</footer>
</body>
</html>
