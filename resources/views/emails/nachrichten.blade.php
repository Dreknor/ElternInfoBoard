<!DOCTYPE html>
<html>
<head>
    <title>Aktuelle Informationen aus dem Schulzentrum</title>
</head>
<body>

<p>Liebe/r {{$name}}</p>
<p>
    Folgende neue Nachrichten liegen für Sie im Eltern-Bereich des Schulzentrums vor:
</p>

<p>
<ul>
    @foreach($nachrichten as $nachricht)
        <li>
            {{$nachricht->header}}
        </li>
    @endforeach
</ul>
</p>

<p>
    Für genauere Inhalte loggen Sie sich bitte ein.
    <a href="eltern.esz-radebeul.de">eltern.esz-radebeul.de</a>
</p>

</body>
</html>