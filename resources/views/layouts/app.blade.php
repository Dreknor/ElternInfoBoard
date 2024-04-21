@include('layouts.elements.modules')
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{asset('img/'.config('app.favicon'))}}" type="image/x-icon">
    <title>{{config('app.name')}} @yield('title')</title>


    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet"/>
    <link href="{{asset('css/paper-dashboard.css')}}" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet"/>

    <!--<script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>-->
    <link href="{{asset('/css/all.css')}}?v=1" rel="stylesheet"> <!--load all styles -->
    <link href="{{asset('/css/app.css')}}?v=2" rel="stylesheet"> <!--load all styles -->
    <link href="{{asset('/css/mobile.css')}}?v=1" rel="stylesheet">

    <link href="{{asset('/css/comments.css')}}" rel="stylesheet"><!--load all styles -->
    @yield('css')

</head>

<body id="app-layout">
<div class="d-lg-none">
    <nav class="mobile-bottom-nav">
        @stack('bottom-nav')
        <div class="mobile-bottom-nav_item" id="toogleSidebarButton">
            <div class="mobile-bottom-nav_item-content">
                <a href="#">
                    <i class="fas fa-ellipsis-h"></i>
                </a>
            </div>
        </div>
    </nav>
</div>

<div class="sidebar" data-color="white" data-active-color="danger">
    <div class="logo" style="word-wrap: normal;">
        <a href="{{config('app.url')}}" class="simple-text">
            <div class="logo-image-small">
                <img src="{{asset('img/'.config('app.logo'))}}" class="p-0">
            </div>
        </a>
    </div>

    <div class="sidebar-wrapper " id="sidebar">
        <ul class="nav">
            @if(config('app.mitarbeiterboard') != "" and auth()->user()->can('view Mitarbeiterboard'))
                <li class="">
                    <a href="{{config('app.mitarbeiterboard')}}">
                        <i class="fa fa-external-link-alt"></i>
                        <p>MitarbeiterBoard</p>
                    </a>
                </li>
            @endif
            @stack('nav')

            <li class="border-bottom"></li>
            @stack('adm-nav')

        </ul>

    </div>

</div>

<div class="main-panel">
    @include('layouts.elements.header-nav')



    <div class="content">
        @if(session()->has('ownID'))
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <p>Eingeloggt als: {{auth()->user()->name}}</p>
                            <p>
                                <a href="{{url('logoutAsUser')}}" class="btn btn-info">
                                    ausloggen
                                </a>
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
    $('#toogleSidebarButton').on('click', function () {
        $('html').toggleClass('nav-open')
    })




    $('input[type=submit]').click(function (){
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


</body>
</html>
