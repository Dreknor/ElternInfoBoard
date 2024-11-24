<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{asset('img/'.$settings->favicon)}}" type="image/x-icon">

    @if($settings->favicon == 'app_logo.png')
        <link rel="shortcut icon" href="{{asset('img/'.$settings->favicon)}}" type="image/x-icon">
    @else
        <link rel="shortcut icon" href="{{url('storage/img/'.$settings->favicon)}}" type="image/x-icon">
    @endif

    @stack('header')
    <title>{{$settings->app_name}}</title>


    <!-- CSS Files -->
    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet"/>
    <link href="{{asset('css/paper-dashboard.css')}}" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet"/>

    <!--<script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>-->
    <link href="{{asset('/css/all.css')}}?v=1" rel="stylesheet"> <!--load all styles -->
    <link href="{{asset('/css/app.css')}}?v=2" rel="stylesheet"> <!--load all styles -->
    <link href="{{asset('/css/mobile.css')}}?v=1" rel="stylesheet">
    <script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>

    @stack('head')

</head>

<body id="app-layout">
<div class="main-panel" style="width: 100%">

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-absolute fixed-top navbar-transparent">
            <div class="container-fluid">
                <div class="navbar-wrapper">
                    <a class="navbar-brand" href="{{url('/')}}">

                        {{$settings->app_name}}
                    </a>
                </div>
            </div>
        </nav>

    <div class="content">

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

    @stack('js')
</body>
</html>
