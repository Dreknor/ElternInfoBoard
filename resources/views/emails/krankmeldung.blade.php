<!DOCTYPE html>
<html>
<head>
    <title>Krankmeldung von {{$name}}</title>
</head>
<body>

<p>Für das Kind {{$NameDesKindes}} wurde eine neue Krankmeldung erstellt.</p>
<p>Zeitraum:  {{$krankVon}} - {{$krankBis}}</p>
<p>Nachricht:<br>
{!! $bemerkung !!}
</p>
<p>Krankmeldung gesendet von {{$name}}</p>
</body>
</html>
