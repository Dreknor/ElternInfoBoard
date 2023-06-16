<!DOCTYPE html>
<html>
<head>
    <title>Krankmeldungen am {{\Carbon\Carbon::now()->format('d.m.Y')}} -
        Stand: {{\Carbon\Carbon::now()->format('H:i')}}</title>
</head>
<body>

<h2>
    Krankmeldungen am {{\Carbon\Carbon::now()->format('d.m.Y')}} - Stand: {{\Carbon\Carbon::now()->format('H:i')}}
</h2>


<ul>
    @foreach($meldungen as $Meldung)
        <li>{{$Meldung->name}}</li>
    @endforeach
</ul>

@foreach($meldungen as $Meldung)
    @if($meldungen->count() > 1)
        <div style="page-break-before : always;"></div>
    @endif
    <p>
        Schüler: <b>{{$Meldung->name}}</b>
    </p>
    <p>
        Zeitpunkt: <b>{{$Meldung->created_at->format('d.m.Y H:i')}}</b>
    </p>
    <p>
        Von: {{$Meldung->start->format('d.m.Y')}} Bis: {{$Meldung->ende->format('d.m.Y')}}
    </p>
    <p>
        {!! $Meldung->kommentar !!}
    </p>

@endforeach

</body>
</html>
