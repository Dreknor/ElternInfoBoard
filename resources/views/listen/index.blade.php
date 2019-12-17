@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
                    <div class="col-md-10 col-sm-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>
                                    aktuelle Listen
                                </h5>
                            </div>
                            @if(count($listen)<1)
                                <div class="card-body alert-info">
                                    <p>
                                        Es wurden keine aktuellen Listen gefunden
                                    </p>
                                </div>
                            @endif
                        </div>
                        @if(count($listen)>=1)
                                    <div class="card-deck">
                                        @foreach($listen as $liste)
                                            <div class="card">
                                                <div class="card-header">
                                                    <h5>
                                                        {{$liste->listenname}}
                                                    </h5>
                                                    <div class="row">
                                                        <div class="col-sm-8 -col-md-6">
                                                            <p class="info small">
                                                                {!! $liste->comment !!}
                                                            </p>
                                                        </div>
                                                        @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))

                                                        @endif
                                                    </div>


                                                </div>
                                                <div class="card-body border-top">
                                                    @if($eintragungen->where('listen_id', $liste->id)->count() > 0)
                                                        <div class="row">
                                                            <div class="col-8">
                                                                <b>Ihr Termin:</b > <br>{{$eintragungen->where('listen_id', $liste->id)->first()->termin->format('d.m.Y H:i')}} Uhr
                                                            </div>
                                                            <div class="col-4">

                                                                <form action="{{url('eintragungen/'.$eintragungen->where('listen_id', $liste->id)->first()->id)}}" method="post">
                                                                    <a href="{{$eintragungen->where('listen_id', $liste->id)->first()->link($liste->listenname, $liste->duration)->google()}}" class="btn btn-primary btn-sm" target="_blank" title="Goole-Kalender-Link">
                                                                        <img src="{{asset('img/icon-google-cal.png')}}" height="25px">
                                                                    </a>
                                                                    <a href="{{$eintragungen->where('listen_id', $liste->id)->first()->link($liste->listenname,  $liste->duration)->ics()}}" class="btn btn-primary btn-sm" title="ICS-Download für Apple und Windows">
                                                                        <img  src="{{asset('img/ics-icon.png')}}" height="25px">
                                                                    </a>
                                                                    @csrf
                                                                    @method("delete")
                                                                    <button type="submit" class="btn btn-xs btn-danger">absagen</button>
                                                                </form>
                                                            </div>
                                                    @else
                                                        <a href="{{url("listen/$liste->id")}}" class="btn btn-primary btn-block">
                                                            Auswahl anzeigen
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="card-footer border-top">
                                                    <small>
                                                        endet: {{$liste->ende->format('d.m.Y')}}
                                                    </small>
                                                    @if(auth()->user()->can('edit terminliste'))
                                                        <div class="badge badge-info pull-right">
                                                            {{$liste->type}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                            @endif
                        </div>
                    </div>
        </div>
    </div>


@endsection