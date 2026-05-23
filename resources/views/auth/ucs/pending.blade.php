<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Auto-Refresh nach 60 Sekunden: erneuter Login-Versuch --}}
    <meta http-equiv="refresh" content="60;url={{ route('login') }}">
    <title>Konto wird vorbereitet – {{ $settings->app_name ?? 'Elterninfo' }}</title>

    @php
        $faviconPath = 'img/app_logo.png';
        $faviconUrl  = asset($faviconPath);
        if ($settings->favicon !== 'app_logo.png' && \Illuminate\Support\Facades\Storage::disk('public')->exists('img/' . $settings->favicon)) {
            $faviconUrl = url('storage/img/' . $settings->favicon);
        }
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="image/x-icon">

    <style>[x-cloak]{display:none!important}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('/css/all.css') }}?v=1" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Montserrat', system-ui, -apple-system, sans-serif !important; }
    </style>
</head>

<body class="antialiased font-sans bg-gray-50 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-md text-center">

    {{-- Icon --}}
    <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-6">
        <i class="fas fa-spinner fa-spin text-blue-600 text-3xl" id="spinner"></i>
        <i class="fas fa-check text-blue-600 text-3xl hidden" id="check-icon"></i>
    </div>

    {{-- Überschrift --}}
    <h1 class="text-2xl font-extrabold text-gray-900 mb-3">
        Konto wird vorbereitet
    </h1>

    {{-- Beschreibung --}}
    <p class="text-gray-600 text-sm leading-relaxed mb-6">
        Ihr Konto wird gerade mit dem Schulverwaltungssystem synchronisiert.
        Bitte haben Sie einen kurzen Moment Geduld.
    </p>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-8 text-sm text-blue-800 text-left space-y-1">
        <p class="flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-500 flex-shrink-0"></i>
            Diese Seite leitet Sie in <span class="font-semibold" id="countdown">60</span> Sekunden automatisch
            zur Anmeldeseite zurück.
        </p>
        <p class="flex items-center gap-2 text-blue-700">
            <i class="fas fa-redo text-blue-400 flex-shrink-0"></i>
            Falls Ihr Konto danach immer noch nicht verfügbar ist, wenden Sie sich bitte an die Schule.
        </p>
    </div>

    {{-- Zurück-Button --}}
    <a href="{{ route('login') }}"
       class="inline-flex items-center gap-2 py-3 px-6 rounded-xl
              bg-blue-600 hover:bg-blue-700 active:scale-[0.98]
              text-white font-semibold text-sm shadow-md shadow-blue-200
              transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <i class="fas fa-arrow-left"></i>
        Erneut versuchen
    </a>

</div>

<script>
    // Countdown
    let seconds = 60;
    const el = document.getElementById('countdown');
    const tick = setInterval(() => {
        seconds--;
        if (el) el.textContent = seconds;
        if (seconds <= 0) clearInterval(tick);
    }, 1000);
</script>

</body>
</html>

