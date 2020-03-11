@extends('layouts.app')

@section('css')

    <style type="text/css">
        @media (min-width: 576px) {
            .card-columns {
                column-count: 1;
            }
        }

        @media (min-width: 768px) {
            .card-columns {
                column-count: 2;
            }
        }

        @media (min-width: 992px) {
            .card-columns {
                column-count: 3;
            }
        }

        @media (min-width: 1200px) {
            .card-columns {
                column-count: 3;
            }
        }
    </style>

@endsection

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
                    <div class="col-md-12 col-sm-12">
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
                                    <div class="card-columns">
                                        @foreach($listen as $liste)
                                            <div class="card">
                                                <div class="card-header  @if($liste->active == 0) bg-info @endif ">
                                                    <h5>
                                                        {{$liste->listenname}} @if($liste->active == 0) (inaktiv) @endif
                                                    </h5>
                                                    <div class="row">
                                                        <div class="col-sm-8 col-md-8 col-lg-8">
                                                            <p class="info small">
                                                                {!! $liste->comment !!}
                                                            </p>
                                                        </div>

                                                        @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                                                            <div class="col-sm-4 col-md-4 col-lg-4">
                                                                <div class="pull-right">
                                                                    @if($liste->active == 0)
                                                                        <a href="{{url("listen/$liste->id/activate")}}" class="btn btn-warning">
                                                                            <i class="fas fa-eye" title="veröffentlichen"></i>
                                                                        </a>
                                                                    @else
                                                                        <a href="{{url("listen/$liste->id/deactivate")}}" class="btn btn-warning btn-xs">
                                                                            <i class="fas fa-eye-slash" title="ausblenden"></i>
                                                                        </a>
                                                                    @endif
                                                                </div>

                                                            </div>
                                                        @endif

                                                    </div>


                                                </div>
                                                <div class="card-body border-top">
                                                    @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <p>
                                                                    Bisherige Eintragungen: {{$liste->eintragungen->where('reserviert_fuer', '!=', null)->count()}}
                                                                </p>
                                                            </div>

                                                        </div>
                                                    @endif

                                                    @if($eintragungen->where('listen_id', $liste->id)->count() > 0)
                                                        @foreach($eintragungen->where('listen_id', $liste->id)->sortBy('termin')->all() as $eintragung)
                                                            <div class="row">
                                                                <div class="col-8">
                                                                    <b>Ihr Termin:</b > <br>{{$eintragung->termin->format('d.m.Y H:i')}} Uhr
                                                                </div>
                                                                <div class="col-4">

                                                                    <form action="{{url('eintragungen/'.$eintragung->id)}}" method="post">
                                                                        <a href="{{$eintragung->link($liste->listenname, $liste->duration)->google()}}" class="btn btn-primary btn-sm" target="_blank" title="Goole-Kalender-Link">
                                                                            <img src="{{asset('img/icon-google-cal.png')}}" height="25px">
                                                                        </a>
                                                                        <a href="{{$eintragung->link($liste->listenname,  $liste->duration)->ics()}}" class="btn btn-primary btn-sm" title="ICS-Download für Apple und Windows">
                                                                            <img  src="{{asset('img/ics-icon.png')}}" height="25px">
                                                                        </a>
                                                                        @csrf
                                                                        @method("delete")
                                                                        <button type="submit" class="btn btn-xs btn-danger">absagen</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                    @if($eintragungen->where('listen_id', $liste->id)->count() < 1 or $liste->multiple == 1)
                                                        <div class="row">
                                                            <a href="{{url("listen/$liste->id")}}" class="btn btn-primary btn-block">
                                                                Auswahl anzeigen
                                                            </a>
                                                        </div>
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
