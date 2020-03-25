<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{asset('img/favicon.ico')}}" type="image/x-icon">

    <title>ElternInfoBoard</title>


    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
    <link href="{{asset('css/paper-dashboard.css?v=2.0.0')}}" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />

    <!--<script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>-->
    <link href="{{asset('/css/all.css')}}" rel="stylesheet"> <!--load all styles -->
    @yield('css')

</head>

<body id="app-layout">
<div class="sidebar" data-color="white" data-active-color="danger">
    <div class="logo" style="word-wrap: normal;">
        <a href="https://www.esz-radebeul.de" class="simple-text">
            <div class="logo-image-small">
                <img src="{{asset('img/logo.png')}}">
            </div>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <ul class="nav">
            <li class="@if(request()->segment(1)=="" or request()->segment(1)=="home") active @endif">
                <a href="{{url('/')}}">
                    <i class="far fa-newspaper"></i>
                    <p>Nachrichten</p>
                </a>
            </li>

            <li class="@if(request()->segment(1)=="files" AND request()->segment(2)!='create' ) active @endif">
                <a href="{{url('/files')}}">
                    <i class="fa fa-download"></i>
                    <p>Downloads</p>
                </a>
            </li>
            <li class="@if(request()->segment(1)=="reinigung" AND request()->segment(2)!='create' ) active @endif">
                <a href="{{url('/reinigung')}}">
                    <i class="fas fa-broom"></i>
                    <p>Reinigungsplan</p>
                </a>
            </li>
            <li class="@if(request()->segment(1)=="listen" AND request()->segment(2)!='create' ) active @endif">
                <a href="{{url('/listen')}}">
                    <i class="far fa-list-alt"></i>
                    Listen
                </a>
            </li>

            @can('view elternrat')
                <li class="@if(request()->segment(1)=="elternrat") active @endif">
                    <a href="{{url('/elternrat')}}">
                        <i class="fas fa-user-friends"></i>
                        Elternrat
                    </a>
                </li>
            @endcan

            <li class="@if(request()->segment(1)=="feedback") active @endif">
                <a href="{{url('/feedback')}}">
                    <i class="far fa-comment" aria-hidden="true"></i>
                    <p>Feedback</p>
                </a>
            </li>
            <li class="border-bottom"></li>
            @if(auth()->user()->can('create posts'))
                <li class="@if(request()->segment(1)=="posts" AND request()->segment(2)=='create' ) active @endif">
                    <a href="{{url('/posts/create')}}">
                        <i class="fas fa-pen"></i>
                        <p>neue Nachricht</p>
                    </a>
                </li>
            @endif
            @if(auth()->user()->can('edit termin'))
                <li class="@if(request()->segment(1)=="termin" AND request()->segment(2)=='create' ) active @endif">
                    <a href="{{url('/termin/create')}}">
                        <i class="far fa-calendar-alt"></i>
                        <p>neuer Termin</p>
                    </a>
                </li>
            @endif

            @if(auth()->user()->can('create terminliste'))
                <li class="@if(request()->segment(1)=="listen" AND request()->segment(2)=='create' ) active @endif">
                    <a href="{{url('/listen/create')}}">
                        <i class="far fa-list-alt"></i>
                        <p>neue Liste</p>
                    </a>
                </li>
            @endif
            @if(auth()->user()->can('upload files'))
                <li class="@if(request()->segment(1)=='files' AND request()->segment(2)=='create' ) active @endif">
                    <a href="{{url('/files/create')}}">
                        <i class="fas fa-upload"></i>
                        <p>Datei hochladen</p>
                    </a>
                </li>
            @endif
            @if(auth()->user()->can('edit user'))
                <li class="@if(request()->segment(1)=="users" ) active @endif">
                    <a href="{{url('/users')}}">
                        <i class="fas fa-user"></i>
                        <p>Benutzerzugänge</p>
                    </a>
                </li>
            @endif
            @can('edit user')
                <li class="@if(request()->segment(1)=="roles" ) active @endif">
                    <a href="{{url('/roles')}}">
                        <i class="fas fa-user-tag"></i>
                        <p>Rollen</p>
                    </a>
                </li>
            @endcan
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
                <a class="navbar-brand" href="{{url('/')}}">{{env('APP_NAME')}}</a>
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
                                        <li>
                                            <a class="dropdown-item" href="{{url('einstellungen')}}">
                                                Einstellungen
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{url('changelog')}}">
                                                Changelog
                                            </a>
                                        </li>
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
</body>
</html>
