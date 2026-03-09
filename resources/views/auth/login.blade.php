<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Anmelden – {{ $settings->app_name ?? 'Elterninfo' }}</title>

    @php
        $faviconPath = 'img/app_logo.png';
        $faviconUrl  = asset($faviconPath);
        if ($settings->favicon !== 'app_logo.png' && \Illuminate\Support\Facades\Storage::disk('public')->exists('img/' . $settings->favicon)) {
            $faviconUrl = url('storage/img/' . $settings->favicon);
        }
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="image/x-icon">

    {{-- x-cloak vor Alpine --}}
    <style>[x-cloak]{display:none!important}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Montserrat --}}
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link href="{{ asset('/css/all.css') }}?v=1" rel="stylesheet">

    {{-- Tailwind via Vite --}}
    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Montserrat', system-ui, -apple-system, sans-serif !important; }
    </style>
</head>

<body class="antialiased font-sans">

{{-- ===== FULLSCREEN SPLIT LAYOUT ===== --}}
<div class="min-h-screen flex">

    {{-- -------- LINKE SEITE: Branding -------- --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 relative bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 flex-col items-center justify-center p-12 overflow-hidden">

        {{-- Dekorative Kreise --}}
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-[32rem] h-[32rem] bg-indigo-900/30 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 right-0 w-64 h-64 bg-blue-500/20 rounded-full blur-2xl"></div>

        {{-- Inhalt --}}
        <div class="relative z-10 max-w-md text-center">
            {{-- Logo --}}
            <div class="mb-8 inline-flex items-center justify-center w-24 h-24 bg-white/10 backdrop-blur-sm rounded-3xl shadow-2xl ring-1 ring-white/20">
                <img src="{{ asset('img/app_logo.png') }}" alt="{{ $settings->app_name ?? 'Logo' }}"
                     class="w-14 h-14 object-contain drop-shadow-lg">
            </div>

            <h1 class="text-4xl xl:text-5xl font-extrabold text-white mb-4 leading-tight tracking-tight">
                {{ $settings->app_name ?? 'Elterninfo' }}
            </h1>
            <p class="text-blue-100 text-lg leading-relaxed mb-10">
                Ihr digitales Kommunikationsportal für Schule und Elternhaus
            </p>

            {{-- Feature-Pills --}}
            <div class="flex flex-wrap justify-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white/90 text-sm font-medium ring-1 ring-white/20">
                    <i class="fas fa-bell text-yellow-300 text-xs"></i> Benachrichtigungen
                </span>
                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white/90 text-sm font-medium ring-1 ring-white/20">
                    <i class="fas fa-calendar text-green-300 text-xs"></i> Veranstaltungen
                </span>
                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white/90 text-sm font-medium ring-1 ring-white/20">
                    <i class="fas fa-comments text-pink-300 text-xs"></i> Nachrichten
                </span>
            </div>
        </div>

        {{-- Copyright unten links --}}
        <p class="absolute bottom-6 left-0 right-0 text-center text-blue-200/60 text-xs">
            &copy; {{ date('Y') }} {{ $settings->app_name ?? 'Elterninfo' }}
        </p>
    </div>

    {{-- -------- RECHTE SEITE: Formular -------- --}}
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center bg-gray-50 px-6 py-12 sm:px-10">
        <div class="w-full max-w-sm">

            {{-- Mobile Logo (nur auf kleinen Screens) --}}
            <div class="lg:hidden text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl shadow-lg mb-3">
                    <img src="{{ asset('img/app_logo.png') }}" alt="Logo" class="w-10 h-10 object-contain">
                </div>
                <h1 class="text-2xl font-extrabold text-gray-800">{{ $settings->app_name ?? 'Elterninfo' }}</h1>
            </div>

            {{-- Überschrift --}}
            <div class="mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900">Willkommen zurück</h2>
                <p class="mt-1 text-sm text-gray-500">Bitte melden Sie sich mit Ihren Zugangsdaten an</p>
            </div>

            {{-- Flash-Meldung --}}
            @if(session('Meldung'))
            <div x-data="{ show: true }" x-show="show" x-cloak x-transition
                 class="mb-6 flex items-start gap-3 p-4 rounded-xl text-sm
                    @if(session('type') === 'success') bg-green-50 border border-green-200 text-green-800
                    @elseif(session('type') === 'danger' || session('type') === 'error') bg-red-50 border border-red-200 text-red-800
                    @elseif(session('type') === 'warning') bg-amber-50 border border-amber-200 text-amber-800
                    @else bg-blue-50 border border-blue-200 text-blue-800
                    @endif">
                <i class="fas flex-shrink-0 mt-0.5
                    @if(session('type') === 'success') fa-check-circle text-green-500
                    @elseif(session('type') === 'danger' || session('type') === 'error') fa-times-circle text-red-500
                    @elseif(session('type') === 'warning') fa-exclamation-triangle text-amber-500
                    @else fa-info-circle text-blue-500
                    @endif"></i>
                <span class="flex-1">{{ session('Meldung') }}</span>
                <button @click="show = false" class="opacity-50 hover:opacity-100 transition-opacity leading-none mt-0.5">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            @endif

            {{-- Formular --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- E-Mail --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        E-Mail-Adresse
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 text-sm"></i>
                        </span>
                        <input id="email" type="email" name="email"
                               value="{{ old('email') }}"
                               required autocomplete="email" autofocus
                               placeholder="ihre@email.de"
                               class="w-full pl-10 pr-4 py-3 rounded-xl border text-sm transition-all duration-200 outline-none
                                      @error('email')
                                          border-red-400 bg-red-50 text-red-900 focus:border-red-500 focus:ring-2 focus:ring-red-200
                                      @else
                                          border-gray-300 bg-white text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100
                                      @enderror">
                    </div>
                    @error('email')
                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                    @enderror
                </div>

                {{-- Passwort --}}
                <div x-data="{ show: false }">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Kennwort
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                            <i class="fas fa-lock text-gray-400 text-sm"></i>
                        </span>
                        <input id="password" :type="show ? 'text' : 'password'"
                               name="password"
                               required autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full pl-10 pr-12 py-3 rounded-xl border text-sm transition-all duration-200 outline-none
                                      @error('password')
                                          border-red-400 bg-red-50 text-red-900 focus:border-red-500 focus:ring-2 focus:ring-red-200
                                      @else
                                          border-gray-300 bg-white text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100
                                      @enderror">
                        <button type="button" @click="show = !show"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-blue-600 transition-colors"
                                :aria-label="show ? 'Verbergen' : 'Anzeigen'">
                            <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                    @error('password')
                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                    @enderror
                </div>

                {{-- Optionen-Zeile --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                        <span class="text-sm text-gray-600">Angemeldet bleiben</span>
                    </label>
                    @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                        Passwort vergessen?
                    </a>
                    @endif
                </div>

                {{-- Primär-Button --}}
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl
                               bg-gradient-to-r from-blue-600 to-indigo-600
                               hover:from-blue-700 hover:to-indigo-700
                               active:scale-[0.98] text-white font-semibold text-sm
                               shadow-md shadow-blue-200 hover:shadow-lg hover:shadow-blue-300
                               transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-sign-in-alt"></i>
                    Anmelden
                </button>

                {{-- Passwortloser Login --}}
                <button type="submit" name="submit" value="password-less"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl
                               border-2 border-teal-500 text-teal-600
                               hover:bg-teal-500 hover:text-white
                               active:scale-[0.98] font-semibold text-sm
                               transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2">
                    <i class="fas fa-magic"></i>
                    Passwortloser Login
                </button>
            </form>

            {{-- Keycloak / SSO --}}
            @if($keycloak == true)
            <div class="mt-6">
                <div class="relative flex items-center gap-3">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">oder weiter mit</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>
                <a href="{{ route('login.keycloak') }}"
                   class="mt-4 w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl
                          bg-gray-800 hover:bg-gray-900 active:scale-[0.98]
                          text-white font-semibold text-sm shadow-sm
                          transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2">
                    <i class="fas fa-key"></i>
                    {{ $keycloakButtonText ?? 'Login mit SSO' }}
                </a>
            </div>
            @endif

            {{-- Mobile Copyright --}}
            <p class="lg:hidden mt-10 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ $settings->app_name ?? 'Elterninfo' }}
            </p>

        </div>
    </div>
</div>

</body>
</html>
