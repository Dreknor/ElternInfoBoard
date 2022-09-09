<!DOCTYPE html>
<html>
<head>
    <title> Rückmeldungen zu {{$nachricht->header}}</title>
</head>
<body>

<h2>
    @if($rueckmeldungen->count() > 1)
        Rückmeldungen
    @else
        Rückmeldung
    @endif zu {{$nachricht->header}}
</h2>
@if($rueckmeldungen->count() > 1)
    <p>
        Stand: {{\Carbon\Carbon::now()->format('d.m.Y H:i')}}
    </p>
@endif
@foreach($rueckmeldungen as $rueckmeldung)
    @if($rueckmeldungen->count() > 1)
        <div style="page-break-before : always;"></div>
    @endif
    <p>
        von: <b>{{$rueckmeldung->user->name}}</b>
    </p>
    <p>
        Zeitpunkt: <b>{{$rueckmeldung->created_at->format('d.m.Y H:i')}}</b>
    </p>
    <p>
        {!! $rueckmeldung->text !!}
    </p>

@endforeach

</body>
</html>
