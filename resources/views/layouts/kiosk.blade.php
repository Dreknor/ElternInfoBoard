@foreach($module as $modul)
    @include("kiosk.module.blade.php.$modul")
@endforeach
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!--<meta http-equiv="refresh" content="7200">-->
    <meta http-equiv="refresh" content="{{$refresh}}">

    <link rel="shortcut icon" href="{{asset('img/'.config('app.favicon'))}}" type="image/x-icon">
    @stack('header')
    <title>{{config('app.name')}}</title>


    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
    <link href="{{asset('css/paper-dashboard.css?v=2.0.0')}}" rel="stylesheet" />
    <link href="{{asset('css/kiosk/kiosk.css')}}" rel="stylesheet" />


    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Quicksand">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" crossorigin="anonymous">

    <script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>

    @stack('head')

</head>

<body id="">
    <div class="container-fluid">
        <div id="carousel" class="carousel slide " data-ride="carousel" data-interval="12000">
            <div class="carousel-inner">
                @stack('slider')
            </div>
        </div>
    </div>

    <!-- JavaScripts -->

    <script src="{{asset('js/core/jquery.min.js')}}"></script>
    <script src="{{asset('js/core/popper.min.js')}}"></script>
    <script src="{{asset('js/core/bootstrap.min.js')}}"></script>
    <script src="{{asset('js/plugins/perfect-scrollbar.jquery.min.js')}}"></script>

    <!-- Control Center for Now Ui Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="{{asset('js/paper-dashboard.min.js?v=2.0.0')}}"></script>

   <script>
       document.querySelector('.carousel-item:first-child').classList.add("active");
console.log(document.querySelector('.carousel-item:first-child'));
       console.log($('#carousel').closest('.carousel-item'))
   </script>
    @stack('js')
</body>
</html>
