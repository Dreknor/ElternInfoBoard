@include('layouts.elements.modules')
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{asset('img/favicon.ico')}}" type="image/x-icon">
    <title>{{config('app.name')}}</title>


    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
    <link href="{{asset('css/paper-dashboard.css?v=2.0.0')}}" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />

    <!--<script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>-->
    <link href="{{asset('/css/all.css')}}" rel="stylesheet"> <!--load all styles -->
    <link href="{{asset('/css/floatingButton.css')}}" rel="stylesheet"><!--load all styles -->
    <link href="{{asset('/css/comments.css')}}" rel="stylesheet"><!--load all styles -->
    @yield('css')

</head>

<body id="app-layout">
<div class="sidebar" data-color="white" data-active-color="danger">
    <div class="logo" style="word-wrap: normal;">
        <a href="{{config('app.url')}}" class="simple-text">
            <div class="logo-image-small">
                <img src="{{asset('img/'.config('app.logo'))}}" class="p-0">
            </div>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <ul class="nav">

            @stack('nav')

            <li class="border-bottom"></li>
            @stack('adm-nav')

        </ul>

    </div>

</div>
<div class="main-panel">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-absolute fixed-top navbar-transparent">
        <div class="container-fluid">
            <div class="navbar-wrapper">
                <div class="navbar-toggle">
                    <button type="button" class="navbar-toggler">
                        <span class="navbar-toggler-bar bar1"></span>
                        <span class="navbar-toggler-bar bar2"></span>
                        <span class="navbar-toggler-bar bar3"></span>
                    </button>
                </div>
                <a class="navbar-brand" href="{{url('/')}}">{{config('app.name')}}</a>
            </div>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation" aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-bar navbar-kebab"></span>
                <span class="navbar-toggler-bar navbar-kebab"></span>
                <span class="navbar-toggler-bar navbar-kebab"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navigation">

                <form class="form-inline mr-4 w-auto" role="search" method="post" action="{{url('search')}}" id="searchForm">
                    @csrf
                    <div class="input-group w-100">
                        <input type="text" class="form-control border border-info  border-right-0 mr-0 my-auto" placeholder="Suchen" name="suche"  id="suchInput">
                        <div class="input-group-append">
                            <button class="btn btn-info border border-info  border-left-0 ml-0" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <ul class="navbar-nav mr-4">
                    <div class="nav-item">
                       @include('include.benachrichtigung')
                    </div>

                </ul>

                <ul class="nav-item navbar-nav nav-bar-right w-auto">

                            @if (Auth::guest())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url('/login') }}">Login</a>
                                </li>
                            @else
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <i class="far fa-user"></i>
                                        <p>{{auth()->user()->name}}</p>
                                    </a>
                                    <ul class="dropdown-menu">

                                        @stack('nav-user')

                                        <li>
                                            <a class="dropdown-item" href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                                Logout
                                            </a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                                @csrf
                                            </form>
                                        </li>

                                    </ul>
                                </li>
                            @endif

                    <!-- Authentication Links -->


                </ul>
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

            @stack('home-view-top')
            @stack('home-view')
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
    $('#suchInput').on('focus',function () {
       $('#searchForm').addClass('w-75');
       $('#searchForm').removeClass('w-auto');
    });

    $('#suchInput').blur(function () {
       $('#searchForm').addClass('w-auto');
       $('#searchForm').removeClass('w-75');
    });


</script>
    @yield('js')
    @stack('js')

@auth
    <script src="{{asset('js/enable-push.js')}}" defer></script>
@endauth
</body>
</html>
