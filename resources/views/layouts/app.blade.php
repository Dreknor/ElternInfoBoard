@include('layouts.elements.modules')

@php
    /**
     * Dark Mode: Prüft ob der eingeloggte Nutzer "dark" als Theme gewählt hat.
     * Setzt die "dark"-Klasse auf dem <html>-Element für Tailwind Dark Mode.
     */
    $htmlDarkClass = '';
    if (auth()->check()) {
        $us = \App\Model\UserAppSettings::where('user_id', auth()->id())->first();
        $userTheme = data_get($us?->settings, 'theme', '');
        if ($userTheme === 'dark') {
            $htmlDarkClass = 'dark';
        }
    }
@endphp

<!DOCTYPE html>
<html lang="de" class="{{ $htmlDarkClass }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapidPublicKey" content=" {{ config('webpush.vapid.public_key') }}">

    @php
        $faviconPath = 'img/app_logo.png';
        $faviconVersion = time();

        if ($settings->favicon == 'app_logo.png') {
            $faviconPath = 'img/' . $settings->favicon;
            $faviconUrl = asset($faviconPath);
            if (file_exists(public_path($faviconPath))) {
                $faviconVersion = @filemtime(public_path($faviconPath)) ?: time();
            }
        } else {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists('img/' . $settings->favicon)) {
                $faviconPath = 'storage/img/' . $settings->favicon;
                $faviconUrl = url($faviconPath);
                try {
                    $faviconVersion = \Illuminate\Support\Facades\Storage::disk('public')->lastModified('img/' . $settings->favicon);
                } catch (\Exception $e) {
                    $faviconVersion = time();
                }
            } else {
                $faviconPath = 'img/app_logo.png';
                $faviconUrl = asset($faviconPath);
                $faviconVersion = @filemtime(public_path($faviconPath)) ?: time();
            }
        }
    @endphp
    <link rel="shortcut icon" href="{{$faviconUrl}}?v={{$faviconVersion}}" type="image/x-icon">
    <title>{{$settings->app_name}} @yield('title')</title>

    <!-- Alpine.js x-cloak – verhindert FOUC -->
    <style>[x-cloak] { display: none !important; }</style>

    <!--
     | CSS-Stack (Bootstrap wurde vollständig entfernt):
     | 1. FontAwesome Icons (statisch, kein Bootstrap-Abhängigkeit)
     | 2. Bootstrap Icons (CDN)
     | 3. Vite: app.css mit Tailwind + Bootstrap-Kompat-Layer
     | 4. Seitenspezifisches CSS via @yield('css')
    -->
    <link href="{{asset('/css/all.css')}}?v=1" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.1/font/bootstrap-icons.min.css">
    <link href="{{asset('/css/comments.css')}}" rel="stylesheet">
    <link href="{{asset('/css/palette-gradient.css')}}?v=1" rel="stylesheet">

    <!-- Vite Assets (Tailwind CSS + Bootstrap-Compat-Layer) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('css')
    @stack('css')
</head>

{{-- Body: dark: prefix für Tailwind Dark Mode, bg via CSS-Var --}}
<body id="app-layout" class="bg-[var(--app-bg)] text-[var(--app-text)]">

<!-- Sidebar Overlay für Mobile -->
<div class="sidebar-overlay"></div>

<!-- Mobile Bottom Navigation -->
<div class="lg:hidden">
    <nav class="mobile-bottom-nav fixed bottom-0 left-0 right-0 border-t-2 border-gray-200 dark:border-gray-700 shadow-2xl"
         style="z-index: 1040; background: var(--color-mobile-nav-bg, rgba(255,255,255,0.95)); backdrop-filter: blur(10px);">
        <div class="flex items-center justify-around h-16 px-2 safe-area-inset-bottom">
            <!-- Dashboard Icon -->
            <div class="mobile-bottom-nav_item flex-1 @if(request()->path() == 'dashboard' || request()->path() == '/' || request()->path() == '') mobile-bottom-nav_item--active @endif">
                <div class="mobile-bottom-nav_item-content">
                    <a href="{{url('/dashboard')}}"
                       class="flex flex-col items-center justify-center gap-0.5 py-2 text-gray-600 dark:text-gray-300 hover:text-blue-600 active:text-blue-700 transition-all duration-200 group
                              @if(request()->path() == 'dashboard' || request()->path() == '/' || request()->path() == '') text-blue-600 dark:text-blue-400 @endif">
                        <div class="relative">
                            <i class="fas fa-home text-2xl group-hover:scale-110 transition-transform duration-200"></i>
                        </div>
                        <span class="text-[10px] font-semibold mt-0.5">Home</span>
                    </a>
                </div>
            </div>

            @stack('bottom-nav')

            <!-- Hilfe-Button (Mobile) -->
            @auth
                <x-help-button variant="mobile" />
            @endauth

            <!-- Menu Toggle Icon -->
            <div class="mobile-bottom-nav_item flex-1" id="toogleSidebarButton">
                <div class="mobile-bottom-nav_item-content">
                    <a href="#" class="flex flex-col items-center justify-center gap-0.5 py-2 text-gray-600 dark:text-gray-300 hover:text-blue-600 transition-all duration-200 group">
                        <div class="relative">
                            <i class="fas fa-bars text-2xl group-hover:scale-110 transition-transform duration-200"></i>
                        </div>
                        <span class="text-[10px] font-semibold mt-0.5">Menü</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</div>

<!-- Sidebar -->
<div class="sidebar shadow-sidebar"
     data-color="white"
     data-active-color="danger"
     style="background: linear-gradient(to bottom, var(--color-sidebar-bg, #111827), var(--color-sidebar-bg-mid, #1f2937), var(--color-sidebar-bg, #111827)); z-index: 1010;">

    <!-- Sidebar Navigation -->
    <div class="sidebar-wrapper overflow-y-auto" id="sidebar" style="max-height: calc(100vh - 130px); margin-top: 70px;">
        <ul class="nav flex-column px-2 py-3 space-y-1">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="{{url('/dashboard')}}"
                   class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 group
                          @if(request()->path() == 'dashboard' || request()->path() == '/') bg-blue-600 text-white shadow-lg @else text-gray-300 hover:bg-blue-600 hover:text-white @endif">
                    <i class="fas fa-home text-base group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
            </li>

            @stack('nav')

            <!-- Divider -->
            <li class="border-t border-gray-700 my-2"></li>

            @stack('adm-nav')
        </ul>
    </div>

    <!-- User Info Footer in Sidebar -->
    <div class="absolute bottom-0 left-0 right-0 px-3 py-2 bg-gray-950 border-t border-gray-700">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-xs font-medium truncate mb-0">{{auth()->user()->name ?? 'User'}}</p>
                <p class="text-gray-400 text-xs truncate mb-0">
                    <i class="fas fa-circle text-green-500 text-[6px] mr-1"></i>
                    Online
                </p>
            </div>
        </div>
    </div>

</div>

<!-- Main Panel -->
<div class="main-panel">
    <!-- Navbar -->
    <nav class="shadow-lg border-b fixed-top"
         style="background: var(--color-navbar-bg, #ffffff); border-color: var(--color-navbar-border, #e5e7eb);"
         x-data="{ mobileSearchOpen: false }">
        <div class="container-fluid">
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Links: Toggle & Brand -->
                <div class="flex items-center gap-3">
                    <!-- Sidebar Toggle -->
                    <button type="button"
                            class="navbar-toggler inline-flex items-center justify-center p-2 rounded-lg transition-all duration-200 lg:inline-flex"
                            style="color: var(--color-navbar-text, #1f2937)">
                        <span class="sr-only">Toggle navigation</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Brand / Logo -->
                    <a href="{{url('/')}}" class="flex items-center gap-3 hover:opacity-80 transition-opacity duration-200">
                        <div class="h-10 flex items-center">
                            @if($settings->logo == 'logo.png')
                                <img src="{{asset('img/'.$settings->logo)}}" class="h-10 w-auto" alt="{{$settings->app_name}}">
                            @else
                                <img src="{{url('storage/img/'.$settings->logo)}}" class="h-10 w-auto" alt="{{$settings->app_name}}">
                            @endif
                        </div>
                        <span class="hidden md:inline text-lg font-bold" style="color: var(--color-navbar-text, #1f2937)">{{$settings->app_name}}</span>
                    </a>
                </div>

                <!-- Mitte: Suche (Desktop) -->
                <div class="hidden md:flex flex-1 max-w-xl mx-4">
                    <form class="w-full" role="search" method="post" action="{{url('search')}}" id="searchForm">
                        @csrf
                        <div class="relative">
                            <input type="text"
                                   name="suche"
                                   id="suchInput"
                                   placeholder="Suchen..."
                                   class="w-full pl-4 pr-12 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg
                                          focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none
                                          bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                          placeholder-gray-400 dark:placeholder-gray-500">
                            <button type="submit"
                                    class="absolute right-1 top-1 bottom-1 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Rechts: Benachrichtigungen & User-Menü -->
                <div class="flex items-center gap-2 md:gap-4">
                    <!-- Mobile Search Toggle -->
                    <button type="button"
                            @click="mobileSearchOpen = !mobileSearchOpen"
                            class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700 transition-all duration-200">
                        <i class="fas fa-search text-lg"></i>
                    </button>

                    <!-- Benachrichtigungen -->
                    <div class="relative">
                        @include('include.benachrichtigung')
                    </div>

                    <!-- Hilfe-Button (Desktop) -->
                    @auth
                        <x-help-button variant="navbar" />
                    @endauth

                    @if (Auth::guest())
                        <a href="{{ url('/login') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="hidden sm:inline">Login</span>
                        </a>
                    @else
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button type="button"
                                    @click="open = !open"
                                    @click.away="open = false"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg transition-colors duration-200
                                           bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span class="hidden md:inline font-medium text-gray-800 dark:text-gray-100">{{auth()->user()->name}}</span>
                                <i class="fas fa-chevron-down text-gray-600 dark:text-gray-400 text-sm transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-64 rounded-lg shadow-xl border py-2 z-50
                                        bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                                 style="display: none;"
                                 @click.away="open = false">

                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-0">{{auth()->user()->name}}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0">{{auth()->user()->email}}</p>
                                </div>

                                <div class="py-1">
                                    @stack('nav-user')
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 mt-1 pt-1">
                                    <a href="#"
                                       onclick="event.preventDefault();document.getElementById('logout-form').submit();"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Logout</span>
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                        @csrf
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Mobile Suchleiste (einklappbar) -->
            <div x-show="mobileSearchOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="transform opacity-0 -translate-y-2"
                 x-transition:enter-end="transform opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="transform opacity-100 translate-y-0"
                 x-transition:leave-end="transform opacity-0 -translate-y-2"
                 style="display: none;"
                 class="md:hidden px-4 pb-3">
                <form role="search" method="post" action="{{url('search')}}">
                    @csrf
                    <div class="relative">
                        <input type="text"
                               name="suche"
                               placeholder="Suchen..."
                               class="w-full pl-4 pr-12 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none
                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <button type="submit"
                                class="absolute right-1 top-1 bottom-1 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>
    <!-- End Navbar -->

    <!-- Content -->
    <div class="content">

        @if(session()->has('ownID'))
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <p>Eingeloggt als: {{auth()->user()->name}}</p>
                            <p>
                                <a href="{{url('logoutAsUser')}}" class="btn btn-info btn-sm">zum eigenen Account wechseln</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="container-fluid pt-3">
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if(session('Meldung'))
            <div class="container-fluid pt-3">
                <div class="alert alert-{{session('type')}} alert-dismissible" role="alert"
                     x-data="{ show: true }" x-show="show" x-cloak>
                    <button type="button" class="close" @click.prevent="show = false" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    {{session('Meldung')}}
                </div>
            </div>
        @endif

        @yield('content')
    </div>

</div>
<!-- End Main Panel -->

<!-- ═══ JavaScript ════════════════════════════════════════════════
     Bootstrap CSS + JS wurden vollständig entfernt. jQuery bleibt
     vorerst bestehen (graduelle Migration). Modals, Tabs und
     Dropdowns werden schrittweise durch Alpine.js ersetzt.
     ════════════════════════════════════════════════════════════ -->

{{-- jQuery – wird noch von bestehenden Inline-Scripts genutzt --}}
<script src="{{asset('js/core/jquery.min.js')}}"></script>

{{-- Alpine.js Modal-Shim: ersetzt Bootstrap's $.fn.modal() durch Alpine.js-Stores --}}
<script>
/**
 * Alpine.js Modal-Shim
 * Ersetzt Bootstrap's jQuery-Plugin $.fn.modal() durch Alpine.js-Stores.
 * Wird für schrittweise Migration benötigt.
 */
(function () {
    // Warte auf Alpine.js (wird von Livewire geladen)
    document.addEventListener('alpine:init', function () {
        Alpine.store('modals', {
            open: {},
            show(id) { this.open[id] = true; document.body.style.overflow = 'hidden'; },
            hide(id) { this.open[id] = false; document.body.style.overflow = ''; },
            toggle(id) { this.open[id] ? this.hide(id) : this.show(id); },
        });
    });

    // jQuery-kompatibler Shim: $('#myModal').modal('show')
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ !== 'undefined') {
            $.fn.modal = function (action) {
                var id = this.attr('id') || this.data('target')?.replace('#', '');
                if (!id) return this;
                var el = document.getElementById(id);
                if (!el) return this;

                if (action === 'show' || action === undefined) {
                    el.classList.add('show');
                    el.style.display = 'block';
                    // Alpine.js Store aktualisieren falls verfügbar
                    if (typeof Alpine !== 'undefined' && Alpine.store('modals')) {
                        Alpine.store('modals').show(id);
                    }
                    // Backdrop hinzufügen
                    if (!document.querySelector('.modal-backdrop')) {
                        var backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop show';
                        backdrop.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:40;';
                        backdrop.onclick = function () { $('#' + id).modal('hide'); };
                        document.body.appendChild(backdrop);
                    }
                } else if (action === 'hide') {
                    el.classList.remove('show');
                    el.style.display = 'none';
                    var bd = document.querySelector('.modal-backdrop');
                    if (bd) bd.remove();
                    document.body.style.overflow = '';
                    if (typeof Alpine !== 'undefined' && Alpine.store('modals')) {
                        Alpine.store('modals').hide(id);
                    }
                } else if (action === 'toggle') {
                    el.style.display === 'none' ? $(this).modal('show') : $(this).modal('hide');
                }
                return this;
            };

            // data-dismiss="modal" Handler
            $(document).on('click', '[data-dismiss="modal"]', function () {
                $(this).closest('.modal').modal('hide');
            });

            // data-toggle="modal" Handler
            $(document).on('click', '[data-toggle="modal"]', function (e) {
                e.preventDefault();
                var target = $(this).data('target') || $(this).attr('href');
                $(target).modal('show');
            });

            // Popover Shim (kein Bootstrap mehr nötig)
            $.fn.popover = function () { return this; };

            // Tab Shim für ältere Views mit data-toggle="tab"
            $.fn.tab = function (action) {
                if (action === 'show') {
                    var targetSelector = $(this).data('target') || $(this).attr('href');
                    if (!targetSelector) return this;
                    var parent = $(this).closest('[id]').parent();
                    // Tabs deaktivieren
                    parent.find('.nav-link').removeClass('active');
                    parent.find('.tab-pane').removeClass('active show');
                    // Aktiven Tab setzen
                    $(this).addClass('active');
                    $(targetSelector).addClass('active show');
                }
                return this;
            };

            // Bootstrap-kompatible Tab-Events via data-toggle
            $(document).on('click', '[data-toggle="tab"]', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });

            // Bootstrap-kompatible Tab-Events via data-target (Bootstrap 4)
            $(document).on('click', '.nav-tabs .nav-link', function (e) {
                var target = $(this).data('target') || $(this).attr('href');
                if (target && target.startsWith('#')) {
                    e.preventDefault();
                    // Nav-Links
                    $(this).closest('.nav-tabs').find('.nav-link').removeClass('active');
                    $(this).addClass('active');
                    // Tab-Panes
                    var tabContent = $(this).closest('.card-body, .p-4, .tab-area').find('.tab-content');
                    if (!tabContent.length) tabContent = $(this).closest('.card').find('.tab-content');
                    if (!tabContent.length) tabContent = $(target).parent();
                    tabContent.find('.tab-pane').removeClass('active show');
                    $(target).addClass('active show');
                }
            });
        }
    });
})();
</script>

@yield('js')
@stack('js')

@auth
    <script src="{{asset('js/enable-push.js')}}" defer></script>
@endauth

@auth
    @livewire('help.help-drawer')
@endauth

<script>
    // Sidebar Toggle
    document.getElementById('toogleSidebarButton')?.addEventListener('click', function () {
        document.documentElement.classList.toggle('nav-open');
    });
    document.querySelector('.navbar-toggler')?.addEventListener('click', function () {
        document.documentElement.classList.toggle('nav-open');
    });
    document.querySelector('.sidebar-overlay')?.addEventListener('click', function () {
        document.documentElement.classList.remove('nav-open');
    });
    document.addEventListener('click', function (e) {
        if (document.documentElement.classList.contains('nav-open')) {
            if (!e.target.closest('.sidebar, .navbar-toggler, #toogleSidebarButton')) {
                document.documentElement.classList.remove('nav-open');
            }
        }
    });

    // jQuery-spezifische Initialisierungen (falls jQuery geladen)
    if (typeof $ !== 'undefined') {
        $(function () {
            $('#suchInput').on('focus', function () {
                $('#searchForm').addClass('w-75').removeClass('w-auto');
            });

            $('button[type=submit]').click(function () {
                if (this.form) {
                    this.form.submit();
                    this.disabled = true;
                }
            });
        });
    }
</script>

</body>
</html>
