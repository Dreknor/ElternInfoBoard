<!DOCTYPE html>
<html>
<head>
    <title>Übersicht über Empfänger</title>
</head>
<body>

<p>
    Folgende {{count($empfaenger)}} Empfänger wurden informiert:
</p>

<p>
    <ul>
        @foreach($empfaenger as $Empfaenger)
            <li>

                    {{$Empfaenger->email}}
            </li>
        @endforeach
    </ul>
</p>


<p>
    <a href="https://eltern.esz-radebeul.de">eltern.esz-radebeul.de</a>
</p>

</body>
</html>