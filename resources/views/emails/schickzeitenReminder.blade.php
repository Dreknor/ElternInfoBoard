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
                                {{$child->first_name}} {{$child->last_name}}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="container-fluid">
                                <table class="table table-striped">
                                    <tr>
                                        <th width="30%">

                                        </th>
                                        <th width="23%">
                                            Zeitpunkt
                                        </th>
                                    </tr>
                                    @for($x=1;$x<6;$x++)
                                        <tr>
                                            <th>
                                                {{$weekdays[$x]}}
                                            </th>
                                            <td>
                                                @if($child->schickzeiten->where('weekday',$x)->count() > 0)
                                                    @if($child->schickzeiten->where('weekday',$x)->first()->type == "ab")
                                                        @if($child->schickzeiten->where('weekday',$x)->first()->time_ab)
                                                            ab {{$child->schickzeiten->where('weekday',$x)->first()->time_ab->format('H:i')}}
                                                        @endif
                                                        @if($child->schickzeiten->where('weekday',$x)->first()->time_spaet)
                                                            bis {{$child->schickzeiten->where('weekday',$x)->first()->time_spaet->format('H:i')}}
                                                        @endif
                                                    @elseif($child->schickzeiten->where('weekday',$x)->first()->type == "genau")
                                                        @if($child->schickzeiten->where('weekday',$x)->first()->time)
                                                            genau {{$child->schickzeiten->where('weekday',$x)->first()->time->format('H:i')}}
                                                        @endif
                                                    @endif
                                                @else
                                                    -
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


