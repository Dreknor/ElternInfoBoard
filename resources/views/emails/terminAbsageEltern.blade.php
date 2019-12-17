<!DOCTYPE html>
<html>
<head>
    <title>Terminabsage</title>
</head>
<body>
<p>
    {{$user->name}} hat den Termin  am {{$termin->format('d.m.Y')}} um {{$termin->format('H:i')}} Uhr für {{$liste->listenname}} abgesagt.
</p>
</body>
</html>