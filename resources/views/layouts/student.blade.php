{{-- Oberfläche der Schülerkonten (docs/konzept-schuelerkonten.md §4). Eigenes, schlankes Layout:
     keine Elternnavigation, keine Composer mit Elterndaten, nur `student.*`-Links. --}}
@php
    $htmlDarkClass = (isset($activeTheme) && ($activeTheme->id() === 'dark' || str_contains($activeTheme->bodyClasses(), 'dark-mode'))) ? 'dark' : '';
    $studentNav = [
        ['route' => 'student.home', 'icon' => 'fa-home', 'label' => 'Start'],
        ['route' => 'student.posts', 'icon' => 'fa-newspaper', 'label' => 'Nachrichten'],
        ['route' => 'student.termine', 'icon' => 'fa-calendar-alt', 'label' => 'Termine'],
        ['route' => 'student.vertretungsplan', 'icon' => 'fa-exchange-alt', 'label' => 'Vertretung'],
        ['route' => 'student.stundenplan', 'icon' => 'fa-table', 'label' => 'Stundenplan'],
        ['route' => 'student.ags', 'icon' => 'fa-users', 'label' => 'AGs'],
    ];
    $studentChat = \App\Services\Students\StudentMessenger::enabled();
    if ($studentChat) {
        $studentNav[] = ['route' => 'student.messenger', 'icon' => 'fa-comments', 'label' => 'Chat'];
    }
    $isActive = fn (string $route) => request()->routeIs($route) || request()->routeIs($route.'.*');
@endphp
<!DOCTYPE html>
<html lang="de" class="{{ $htmlDarkClass }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#1f6feb">
    <link rel="shortcut icon" href="{{ asset('img/app_logo.png') }}" type="image/x-icon">
    <title>{{ $settings->app_name }} @yield('title')</title>
    <link href="{{ asset('/css/all.css') }}?v=1" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-theme-vars />
    <style>
        .student-card { background: var(--color-card-bg, #fff); border: 1px solid var(--color-card-border, #e2e8f0); }
        .student-muted { color: var(--color-text-secondary, #64748b); }
        .student-prose img { max-width: 100%; height: auto; }
        .student-prose table { display: block; max-width: 100%; overflow-x: auto; }
        .student-prose a { color: var(--color-primary); text-decoration: underline; }
    </style>
    @stack('css')
</head>
<body class="min-h-screen bg-[var(--app-bg)] text-[var(--app-text)] {{ isset($activeTheme) ? $activeTheme->bodyClasses() : '' }}">

<header class="sticky top-0 z-30 border-b shadow-sm" style="background: var(--color-card-bg, #fff); border-color: var(--color-card-border, #e2e8f0);">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
        <a href="{{ route('student.home') }}" class="flex min-w-0 items-center gap-2 font-semibold" style="color: var(--color-primary);">
            <i class="fas fa-user-graduate"></i>
            <span class="truncate">{{ $settings->app_name }}</span>
        </a>
        <div class="flex items-center gap-2">
            @if($studentChat)
                <a href="{{ route('student.messenger') }}" class="flex items-center rounded-lg px-3 py-2 text-sm hover:bg-black/5 dark:hover:bg-white/10 md:hidden {{ $isActive('student.messenger') ? 'font-semibold' : '' }}" aria-label="Chat mit Lehrkräften">
                    <i class="fas fa-comments text-lg"></i>
                </a>
            @endif
            <a href="{{ route('student.profile') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-black/5 dark:hover:bg-white/10 {{ $isActive('student.profile') ? 'font-semibold' : '' }}" aria-label="Mein Zugang">
                <i class="fas fa-user-circle text-lg"></i>
                <span class="hidden max-w-[12rem] truncate sm:inline">{{ auth()->user()->name }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-black/5 dark:hover:bg-white/10" aria-label="Abmelden">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                    <span class="hidden sm:inline">Abmelden</span>
                </button>
            </form>
        </div>
    </div>
    {{-- Desktop/Tablet: Reiter --}}
    <nav class="mx-auto hidden max-w-5xl gap-1 overflow-x-auto px-2 md:flex" aria-label="Hauptnavigation">
        @foreach($studentNav as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2 text-sm {{ $isActive($item['route']) ? 'font-semibold' : 'border-transparent student-muted' }}"
               @if($isActive($item['route'])) style="border-color: var(--color-primary); color: var(--color-primary);" aria-current="page" @endif>
                <i class="fas {{ $item['icon'] }}"></i>{{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</header>

<main class="mx-auto max-w-5xl px-4 pb-24 pt-4 md:pb-8">
    @if(session('Meldung'))
        <div class="mb-4 rounded-lg border px-4 py-3 text-sm {{ session('type') === 'danger' ? 'border-red-300 bg-red-50 text-red-800' : (session('type') === 'warning' ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-green-300 bg-green-50 text-green-800') }}" role="status">
            {{ session('Meldung') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

{{-- Handy: Leiste unten (5 Einträge, AGs über „Start“) --}}
<nav class="fixed inset-x-0 bottom-0 z-30 border-t md:hidden" style="background: var(--color-card-bg, #fff); border-color: var(--color-card-border, #e2e8f0);" aria-label="Hauptnavigation">
    <div class="grid h-16 grid-cols-5">
        @foreach(array_slice($studentNav, 0, 5) as $item)
            <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center gap-0.5 text-[11px] {{ $isActive($item['route']) ? 'font-semibold' : 'student-muted' }}"
               @if($isActive($item['route'])) style="color: var(--color-primary);" aria-current="page" @endif>
                <i class="fas {{ $item['icon'] }} text-xl"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
@stack('js')
</body>
</html>
