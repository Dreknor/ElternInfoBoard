<!DOCTYPE html>
<html>
<head>
    <title>Schickzeiten-Übersicht</title>
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
</head>
<body>

<div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    Schickzeitenübersicht
                </h6>
            </div>
            <div class="card-body">
                <p>
                    Liebe Eltern,
                </p>
                <p>
                    folgende Schickzeiten sind für @if (count($kinder) > 1) Ihr Kind  @else Ihre Kinder  @endif im {{config('app.name')}} für die kommende Woche vermerkt. Bitte ändern Sie die Zeiten sollten diese nicht mehr korrekt sein.
                </p>
            </div>
        </div>

            @foreach($kinder as $child)
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">
                                {{$child}}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="container-fluid">
                                <table class="table table-striped">
                                    <tr>
                                        <th width="30%">

                                        </th>
                                        <th width="23%">
                                            ab
                                        </th>
                                        <th width="23%">
                                            genau
                                        </th>
                                        <th width="23%">
                                            spätestens
                                        </th>

                                    </tr>
                                    @for($x=1;$x<6;$x++)
                                        <tr>
                                            <th>
                                                {{$weekdays[$x]}}
                                            </th>
                                            <td>
                                                @if($schickzeiten->where('weekday', $x)->where('child_name',$child)->where('type','ab')->first())
                                                    {{substr($schickzeiten->where('weekday', $x)->where('type','=','ab')->where('child_name',$child)->first()->time->format('H:i'), 0 ,5)}} Uhr
                                                @endif
                                            </td>
                                            <td>
                                                @if($schickzeiten->where('weekday', $x)->where('type','genau')->where('child_name',$child)->first())
                                                    {{substr($schickzeiten->where('weekday', $x)->where('type','genau')->where('child_name',$child)->first()->time->format('H:i'), 0 ,5)}} Uhr
                                                @endif
                                            </td>
                                            <td>
                                                @if($schickzeiten->where('weekday', $x)->where('type','spät.')->where('child_name',$child)->first())
                                                    {{substr($schickzeiten->where('weekday', $x)->where('type','=','spät.')->where('child_name',$child)->first()->time->format('H:i'), 0 ,5)}} Uhr
                                                @endif
                                            </td>
                                        </tr>
                                    @endfor

                                </table>
                            </div>

                        </div>
                    </div>
            @endforeach
</div>
</body>
</html>


