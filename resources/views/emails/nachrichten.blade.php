<!DOCTYPE html>
<html>
<head>
    <title>Aktuelle Informationen aus dem Schulzentrum</title>
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />

</head>
<body>
<div class="container-fluid">
    <p>Liebe/r {{$name}}</p>


    <div class="card">
        <div class="card-header">
            <p>
                Folgende neue Nachrichten liegen für Sie im {{config('app.name')}} vor:
            </p>
        </div>
        <div class="card-body">
            <ul class="list-group">
                @foreach($nachrichten as $nachricht)
                        <li class="list-group-item">
                            {{$nachricht->header}} @if($nachricht->external == 0) (externes Angebot) @endif
                        </li>
                @endforeach
            </ul>
        </div>
    </div>



    @if(count($discussionen)>0)
        <div class="card">
            <div class="card-header">
                <p>
                    Im Elternratsbereich liegen folgende bearbeitete Themen vor:
                </p>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($discussionen as $Diskussion)
                        <li class="list-group-item">
                            {{$Diskussion->header}}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

    @endif


    @if(isset($listen) and count($listen)>0)
        <div class="card">
            <div class="card-header">
                <p>
                    Folgende Listen wurden veröffentlicht:
                </p>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($listen as $liste)
                        <li class="list-group-item">
                            {{$liste->listenname}}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
    @if(isset($termine) and count($termine)>0)
        <div class="card">
            <div class="card-header">
                <p>
                    Folgende Termine wurden angelegt:
                </p>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($termine as $termin)
                        <li class="list-group-item">
                            {{$termin->terminname}} ({{$termin->start->format('d.m.Y')}})
                        </li>
                    @endforeach
                </ul>
            </div>
    @endif
    <p>
        Für genauere Inhalte loggen Sie sich bitte ein.
    </p>

    <p>
        <a href="{{config('app.url')}}">{{config('app.url')}}</a>
    </p>
</div>
</body>
</html>
