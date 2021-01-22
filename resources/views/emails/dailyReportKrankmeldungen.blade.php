<!DOCTYPE html>
<html>
<head>
    <title>Übersicht Krankmeldungen</title>
</head>
<body>
<p>Folgende Krankmeldungen liegen für heute vor:</p>
<p>
@foreach($krankmeldungen as $krankmeldung)
    {{$krankmeldung->name}}: {{$krankmeldung->start->format('d.m.Y')}} - {{$krankmeldung->ende->format('d.m.Y')}} <br>
@endforeach
</p>

</body>
</html>
