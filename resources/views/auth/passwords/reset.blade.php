<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Neues Passwort vergeben – {{ $settings->app_name ?? 'Elterninfo' }}</title>

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
    <x-theme-vars />

    <style>
        body { font-family: 'Montserrat', system-ui, -apple-system, sans-serif !important; }
        .login-brand-panel {
            background: linear-gradient(to bottom right, var(--color-primary-dark, #1d4ed8), var(--color-primary, #2563eb), var(--color-secondary, #6366f1));
        }
        .login-btn-primary {
            background: linear-gradient(to right, var(--color-primary, #2563eb), var(--color-secondary, #6366f1));
            box-shadow: 0 4px 6px -1px color-mix(in srgb, var(--color-primary, #2563eb) 30%, transparent);
        }
        .login-btn-primary:hover {
            background: linear-gradient(to right, var(--color-primary-dark, #1d4ed8), color-mix(in srgb, var(--color-secondary, #6366f1) 85%, black));
        }
        .login-btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 40%, transparent);
        }
        .login-link-primary { color: var(--color-primary, #2563eb); }
        .login-link-primary:hover { color: var(--color-primary-dark, #1d4ed8); }
        .login-mobile-logo {
            background: linear-gradient(to bottom right, var(--color-primary, #2563eb), var(--color-secondary, #6366f1));
        }
        .login-input:focus {
            border-color: var(--color-primary, #2563eb) !important;
            --tw-ring-color: color-mix(in srgb, var(--color-primary, #2563eb) 20%, transparent) !important;
        }
        .login-toggle-btn:hover { color: var(--color-primary, #2563eb); }
    </style>
</head>

<body class="antialiased font-sans">
<div class="min-h-screen flex">

    <!-- Linke Seite: Branding -->
    <div class="login-brand-panel hidden lg:flex lg:w-1/2 xl:w-3/5 relative flex-col items-center justify-center p-12 overflow-hidden">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-[32rem] h-[32rem] bg-black/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 right-0 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 max-w-md text-center">
            <div class="mb-8 inline-flex items-center justify-center w-24 h-24 bg-white/10 backdrop-blur-sm rounded-3xl shadow-2xl ring-1 ring-white/20">
                <img src="{{ asset('img/app_logo.png') }}" alt="{{ $settings->app_name ?? 'Logo' }}" class="w-14 h-14 object-contain drop-shadow-lg">
            </div>
            <h1 class="text-4xl xl:text-5xl font-extrabold text-white mb-4 leading-tight tracking-tight">
                {{ $settings->app_name ?? 'Elterninfo' }}
            </h1>
            <p class="text-white/80 text-lg leading-relaxed mb-10">
                Ihr digitales Kommunikationsportal für Schule und Elternhaus
            </p>
            <div class="inline-flex items-center gap-3 px-6 py-4 bg-white/10 backdrop-blur-sm rounded-2xl ring-1 ring-white/20 text-left">
                <div class="w-10 h-10 flex-shrink-0 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-key text-white text-lg"></i>
                </div>
                <div>
                    <p class="font-semibold text-white text-sm">Neues Passwort</p>
                    <p class="text-white/70 text-xs mt-0.5">Vergeben Sie ein sicheres, neues Passwort.</p>
                </div>
            </div>
        </div>

        <p class="absolute bottom-6 left-0 right-0 text-center text-white/50 text-xs">
            &copy; {{ date('Y') }} {{ $settings->app_name ?? 'Elterninfo' }}
        </p>
    </div>

    <!-- Rechte Seite: Formular -->
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center bg-gray-50 px-6 py-12 sm:px-10">
        <div class="w-full max-w-sm">

            <!-- Mobile Logo -->
            <div class="lg:hidden text-center mb-8">
                <div class="login-mobile-logo inline-flex items-center justify-center w-16 h-16 rounded-2xl shadow-lg mb-3">
                    <img src="{{ asset('img/app_logo.png') }}" alt="Logo" class="w-10 h-10 object-contain">
                </div>
                <h1 class="text-2xl font-extrabold text-gray-800">{{ $settings->app_name ?? 'Elterninfo' }}</h1>
            </div>

            <!-- Überschrift -->
            <div class="mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900">Neues Passwort vergeben</h2>
                <p class="mt-1 text-sm text-gray-500">Wählen Sie ein sicheres, neues Passwort für Ihr Konto.</p>
            </div>

            <!-- Formular -->
            <form method="POST" action="{{ route('password.update') }}" class="space-y-5"
                  x-data="{ showPw: false, showPwConfirm: false }">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <!-- E-Mail -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        E-Mail-Adresse
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 text-sm"></i>
                        </span>
                        <input id="email" type="email" name="email"
                               value="{{ $email ?? old('email') }}"
                               required autocomplete="email" autofocus
                               placeholder="ihre@email.de"
                               class="login-input w-full pl-10 pr-4 py-3 rounded-xl border text-sm transition-all duration-200 outline-none
                                      @error('email')
                                          border-red-400 bg-red-50 text-red-900
                                      @else
                                          border-gray-300 bg-white text-gray-900 focus:ring-2
                                      @enderror">
                    </div>
                    @error('email')
                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                    @enderror
                </div>

                <!-- Neues Passwort -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Neues Passwort
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                            <i class="fas fa-lock text-gray-400 text-sm"></i>
                        </span>
                        <input id="password" :type="showPw ? 'text' : 'password'"
                               name="password"
                               required autocomplete="new-password"
                               placeholder="••••••••"
                               class="login-input w-full pl-10 pr-12 py-3 rounded-xl border text-sm transition-all duration-200 outline-none
                                      @error('password')
                                          border-red-400 bg-red-50 text-red-900
                                      @else
                                          border-gray-300 bg-white text-gray-900 focus:ring-2
                                      @enderror">
                        <button type="button" @click="showPw = !showPw"
                                class="login-toggle-btn absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors"
                                :aria-label="showPw ? 'Verbergen' : 'Anzeigen'">
                            <i :class="showPw ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                    @error('password')
                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                    @enderror
                </div>

                <!-- Passwort bestätigen -->
                <div>
                    <label for="password-confirm" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Passwort bestätigen
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                            <i class="fas fa-lock text-gray-400 text-sm"></i>
                        </span>
                        <input id="password-confirm" :type="showPwConfirm ? 'text' : 'password'"
                               name="password_confirmation"
                               required autocomplete="new-password"
                               placeholder="••••••••"
                               class="login-input w-full pl-10 pr-12 py-3 rounded-xl border border-gray-300 bg-white text-gray-900 text-sm transition-all duration-200 outline-none focus:ring-2">
                        <button type="button" @click="showPwConfirm = !showPwConfirm"
                                class="login-toggle-btn absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors"
                                :aria-label="showPwConfirm ? 'Verbergen' : 'Anzeigen'">
                            <i :class="showPwConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="login-btn-primary w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl
                               active:scale-[0.98] text-white font-semibold text-sm transition-all duration-200">
                    <i class="fas fa-check"></i>
                    Passwort zurücksetzen
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="login-link-primary text-sm font-medium transition-colors inline-flex items-center gap-1.5">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Zurück zur Anmeldung
                </a>
            </div>

            <p class="lg:hidden mt-10 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ $settings->app_name ?? 'Elterninfo' }}
            </p>
        </div>
    </div>
</div>
</body>
</html>
