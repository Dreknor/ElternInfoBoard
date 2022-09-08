<!DOCTYPE html>
<html>
<head>
    <title> Rückmeldungen zu {{$nachricht->header}}</title>
</head>
<body>

<h2>
    Rückmeldungen zu {{$nachricht->header}}
</h2>

<p>
    Stand: {{\Carbon\Carbon::now()->format('d.m.Y H:i')}}
</p>

@foreach($rueckmeldungen as $rueckmeldung)
    <div style="page-break-before : always;"></div>
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
