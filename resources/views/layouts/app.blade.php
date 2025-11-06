@include('layouts.elements.modules')
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapidPublicKey" content=" {{ config('webpush.vapid.public_key') }}">

    @if($settings->favicon == 'app_logo.png')
        <link rel="shortcut icon" href="{{asset('img/'.$settings->favicon)}}" type="image/x-icon">
    @else
        <link rel="shortcut icon" href="{{url('storage/img/'.$settings->favicon)}}" type="image/x-icon">
    @endif
    <title>{{$settings->app_name}} @yield('title')</title>

    <!-- Alpine.js x-cloak styling to prevent FOUC - MUST be before Alpine.js -->
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <!-- Alpine.js for Dropdown functionality -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet"/>
    <link href="{{asset('css/paper-dashboard.css')}}" rel="stylesheet"/>
    <!--    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet"/> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.1/font/bootstrap-icons.min.css">
    <!--<script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>-->
    <link href="{{asset('/css/all.css')}}?v=1" rel="stylesheet"> <!--load all styles -->
    <link href="{{asset('/css/palette-gradient.css')}}?v=1" rel="stylesheet">
    <link href="{{asset('/css/mobile.css')}}?v=1" rel="stylesheet">

    <link href="{{asset('/css/comments.css')}}" rel="stylesheet"><!--load all styles -->

    <!-- Vite Assets (Tailwind CSS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('css')

</head>

<body id="app-layout">

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay"></div>

<div class="d-lg-none">
    <nav class="mobile-bottom-nav fixed bottom-0 left-0 right-0 bg-white border-t-2 border-gray-200 shadow-2xl" style="z-index: 1040; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
        <div class="flex items-center justify-around h-16 px-2 safe-area-inset-bottom">
            <!-- Dashboard Icon -->
            <div class="mobile-bottom-nav_item flex-1 @if(request()->path() == 'dashboard' || request()->path() == '/' || request()->path() == '') mobile-bottom-nav_item--active @endif">
                <div class="mobile-bottom-nav_item-content">
                    <a href="{{url('/dashboard')}}" class="flex flex-col items-center justify-center gap-0.5 py-2 text-gray-600 hover:text-blue-600 active:text-blue-700 transition-all duration-200 group @if(request()->path() == 'dashboard' || request()->path() == '/' || request()->path() == '') text-blue-600 @endif">
                        <div class="relative">
                            <i class="fas fa-home text-2xl group-hover:scale-110 transition-transform duration-200"></i>
                        </div>
                        <span class="text-[10px] font-semibold mt-0.5">Home</span>
                    </a>
                </div>
            </div>

            @stack('bottom-nav')

            <!-- Menu Toggle Icon -->
            <div class="mobile-bottom-nav_item flex-1" id="toogleSidebarButton">
                <div class="mobile-bottom-nav_item-content">
                    <a href="#" class="flex flex-col items-center justify-center gap-0.5 py-2 text-gray-600 hover:text-blue-600 active:text-blue-700 transition-all duration-200 group">
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

<div class="sidebar bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 shadow-2xl" data-color="white" data-active-color="danger" style="z-index: 1010;">
    <!-- Sidebar Navigation -->
    <div class="sidebar-wrapper overflow-y-auto" id="sidebar" style="max-height: calc(100vh - 130px); margin-top: 70px;">
        <ul class="nav flex-column px-2 py-3 space-y-1">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="{{url('/dashboard')}}"
                   class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 hover:bg-blue-600 hover:text-white transition-all duration-200 @if(request()->path() == 'dashboard' || request()->path() == '/') bg-blue-600 text-white shadow-lg @endif group">
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

    <!-- User Info Footer -->
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

<div class="main-panel">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg border-b border-gray-200 fixed-top" style="z-index: 1030;">
        <div class="container-fluid">
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Left Section: Toggle & Brand -->
                <div class="flex items-center gap-3">
                    <!-- Sidebar Toggle Button -->
                    <button type="button" class="navbar-toggler inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200 lg:inline-flex">
                        <span class="sr-only">Toggle navigation</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Brand/Logo -->
                    <a href="{{url('/')}}" class="flex items-center gap-3 hover:opacity-80 transition-opacity duration-200">
                        <div class="h-10 flex items-center">
                            @if($settings->logo == 'logo.png')
                                <img src="{{asset('img/'.$settings->logo)}}" class="h-10 w-auto" alt="{{$settings->app_name}}">
                            @else
                                <img src="{{url('storage/img/'.$settings->logo)}}" class="h-10 w-auto" alt="{{$settings->app_name}}">
                            @endif
                        </div>
                        <span class="hidden md:inline text-lg font-bold text-gray-800">{{$settings->app_name}}</span>
                    </a>
                </div>

                <!-- Center Section: Search Bar (Hidden on small screens) -->
                <div class="hidden md:flex flex-1 max-w-xl mx-4">
                    <form class="w-full" role="search" method="post" action="{{url('search')}}" id="searchForm">
                        @csrf
                        <div class="relative">
                            <input type="text"
                                   name="suche"
                                   id="suchInput"
                                   placeholder="Suchen..."
                                   class="w-full pl-4 pr-12 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                            <button type="submit"
                                    class="absolute right-1 top-1 bottom-1 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Section: Notifications & User Menu -->
                <div class="flex items-center gap-2 md:gap-4">
                    <!-- Mobile Search Toggle -->
                    <button type="button"
                            class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200"
                            data-toggle="collapse"
                            data-target="#mobileSearch">
                        <i class="fas fa-search text-lg"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="relative">
                        @include('include.benachrichtigung')
                    </div>

                    @if (Auth::guest())
                        <!-- Guest User -->
                        <a href="{{ url('/login') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="hidden sm:inline">Login</span>
                        </a>
                    @else
                        <!-- Authenticated User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button type="button"
                                    @click="open = !open"
                                    @click.away="open = false"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span class="hidden md:inline font-medium text-gray-800">{{auth()->user()->name}}</span>
                                <i class="fas fa-chevron-down text-gray-600 text-sm transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
                                 style="display: none;"
                                 @click.away="open = false">

                                <!-- User Info Header -->
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <p class="text-sm font-semibold text-gray-800 mb-0">{{auth()->user()->name}}</p>
                                    <p class="text-xs text-gray-500 mb-0">{{auth()->user()->email}}</p>
                                </div>

                                <!-- User Menu Items -->
                                <div class="py-1">
                                    @stack('nav-user')
                                </div>

                                <!-- Logout -->
                                <div class="border-t border-gray-200 mt-1 pt-1">
                                    <a href="#"
                                       onclick="event.preventDefault();document.getElementById('logout-form').submit();"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
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

            <!-- Mobile Search Bar (Collapsible) -->
            <div class="collapse md:hidden px-4 pb-3" id="mobileSearch">
                <form role="search" method="post" action="{{url('search')}}">
                    @csrf
                    <div class="relative">
                        <input type="text"
                               name="suche"
                               placeholder="Suchen..."
                               class="w-full pl-4 pr-12 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
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
    <div class="content">

        @if(session()->has('ownID'))
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <p>Eingeloggt als: {{auth()->user()->name}}</p>
                            <p>
                                <a href="{{url('logoutAsUser')}}" class="btn btn-info">zum eigenen Account wechseln</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('Meldung'))
            <div class="container">
                <div class="row">
                    <div class="col-12" >
                        <div class="alert alert-{{session('type')}} alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            {{session('Meldung')}}

                        </div>
                    </div>
                </div>
            </div>
        @endif

            @yield('content')
    </div>


</div>
<!-- JavaScripts -->

    <script src="{{asset('js/core/jquery.min.js')}}"></script>
    <script src="{{asset('js/core/popper.min.js')}}"></script>
    <script src="{{asset('js/core/bootstrap.min.js')}}"></script>
    <script src="{{asset('js/plugins/perfect-scrollbar.jquery.min.js')}}"></script>


    <!-- Chart JS
    <script src="{{asset('js/plugins/chartjs.min.js')}}"></script>
    -->

    <!--  Notifications Plugin    -->
    <script src="{{asset('js/plugins/bootstrap-notify.js')}}"></script>

    <!-- Control Center for Now Ui Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="{{asset('js/paper-dashboard.min.js?v=2.0.0')}}"></script>

<script>
    // Toggle sidebar for mobile bottom nav button
    $('#toogleSidebarButton').on('click', function () {
        $('html').toggleClass('nav-open');
    });

    // Toggle sidebar for header navbar button
    $('.navbar-toggler').on('click', function () {
        $('html').toggleClass('nav-open');
    });

    // Close sidebar when clicking on overlay
    $('.sidebar-overlay').on('click', function() {
        $('html').removeClass('nav-open');
    });

    // Close sidebar when clicking outside
    $(document).on('click', function(e) {
        if ($('html').hasClass('nav-open')) {
            if (!$(e.target).closest('.sidebar, .navbar-toggler, #toogleSidebarButton').length) {
                $('html').removeClass('nav-open');
            }
        }
    });

    $('#suchInput').on('focus', function () {
        $('#searchForm').addClass('w-75');
        $('#searchForm').removeClass('w-auto');
    });

    $('button[type=submit]').click(function () {
        this.form.submit();
        this.disabled=true;
        this.value='wird bearbeitet…';

    });

    $(function () {
        $('[data-toggle="popover"]').popover()
    });

    $('.popover-dismiss').popover({
        trigger: 'focus'
    });
</script>
    @yield('js')
    @stack('js')

@auth
    <script src="{{asset('js/enable-push.js')}}" defer></script>
@endauth
</body>
</html>
