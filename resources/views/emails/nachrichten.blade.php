<!DOCTYPE html>
<html>
<head>
    <title>Aktuelle Informationen aus dem Schulzentrum</title>
</head>
<body>

<p>Liebe/r {{$name}}</p>
<p>
    Folgende neue Nachrichten liegen für Sie im {{config('app.name')}} vor:
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

@if(count($discussionen)>0)
    <p>
        Im Elternratsbereich liegen folgende bearbeitete Themen vor:
    </p>
    <p>
        <ul>
            @foreach($discussionen as $Diskussion)
                <li>
                    {{$Diskussion->header}}
                </li>
            @endforeach
        </ul>
    </p>
@endif


@if(count($termine)>0)
    <p>
        Folgende Termine wurden hinzugefügt:
    </p>
    <p>
        <ul>
            @foreach($termine as $termin)
                <li>
                    {{$termin->terminname}} ({{$termin->start->format('d.m.Y')}})
                </li>
            @endforeach
        </ul>
    </p>
@endif

@if(count($media)>0)
    <p>
        Folgende Dateien wurden im Downloads-Bereich hinzugefügt:
    </p>
    <p>
        <ul>
            @foreach($media as $file)
                <li>
                    {{$file->name}}
                </li>
            @endforeach
        </ul>
    </p>
@endif
<p>
    Für genauere Inhalte loggen Sie sich bitte ein.
</p>

<p>
    <a href="https://eltern.esz-radebeul.de">eltern.esz-radebeul.de</a>
</p>

</body>
</html>
