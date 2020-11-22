@push('head')

@endpush

@push('slider')
    <div class="carousel-item  centered">
        <canvas id="clock" width="1600" height="1600">
            Dieser Browser wird leider nicht unterstützt.
        </canvas>
    </div>

@endpush

@push('js')
<!--[if lt IE 9]>
<script type="text/javascript" src="{{asset('js/excanvas.js')}}"></script>
<![endif]-->

    <script type="text/javascript" src="{{asset('js/station-clock.js')}}"></script>
    <script type="text/javascript">

        var clock = new StationClock("clock");
        clock.body = StationClock.NoBody;
        clock.dial = StationClock.AustriaStrokeDial;
        clock.hourHand = StationClock.ViennaHourHand;
        clock.minuteHand = StationClock.ViennaMinuteHand;
        clock.secondHand = StationClock.NoSecondHand;
        clock.boss = StationClock.ViennaBoss;
        clock.minuteHandBehavoir = StationClock.CreepingMinuteHand;
        clock.secondHandBehavoir = StationClock.OverhastySecondHand;

        window.setInterval(function() { clock.draw() }, 50);

    </script>
@endpush
