<!DOCTYPE html>
<html>
<head>
    <title>Aktuelle Informationen aus dem Schulzentrum</title>
</head>
<body>

<h2>
    Aktuelle Nachrichten aus dem Schulzentrum
</h2>

<p>
    Datum: {{\Carbon\Carbon::now()->format('d.m.Y H:i')}}
</p>

@foreach($nachrichten as $nachricht)


    <h4>
        {{$nachricht->header}}
    </h4>
    <p>
        {!! $nachricht->news !!}
    </p>
    <br><br>
@endforeach

</body>
</html>