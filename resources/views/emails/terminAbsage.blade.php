<!DOCTYPE html>
<html>
<head>
    <title>Terminabsage</title>
</head>
<body>

<p>Liebe/r {{$empfaenger}},</p>
<p>
 leider musste Ihr Termin am {{$termin->format('d.m.Y')}} um {{$termin->format('H:i')}} Uhr für {{$liste->listenname}} abgesagt werden.
</p>
<p>
    Herzliche Grüße<br>
    {{$user->name}}
</p>

</body>
</html>