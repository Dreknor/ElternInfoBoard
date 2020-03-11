@extends('layouts.layout')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-4">
                <ul class="list-group ">
                    <li class="list-group-item">
                        <b>
                            Themen
                        </b>
                    </li>
                        @foreach($Nachrichten AS $nachricht)
                                <li class="list-group-item @if($nachricht->released != 1) list-group-item-info  @endif" data-target="#carousel" data-slide-to="{{$loop->index}}" >
                                    {{$nachricht->header}}

                                </li>
                        @endforeach
                </ul>
            </div>
            <div class="col-8">
                <div id="carousel" class="carousel slide " data-ride="carousel" data-interval="12000">
                    <div class="carousel-inner">
                        @foreach($Nachrichten as $nachricht)
                                <div class="carousel-item @if($loop->first) active @endif ">
                                    <div class="card">
                                        <div class="card-header @if($nachricht->released != 1) bg-info  @endif">
                                            <h5>
                                                {{$nachricht->header}} @if($nachricht->released != 1) (unveröffentlicht)  @endif
                                            </h5>
                                            <small>
                                                (Archiv ab: {{optional($nachricht->archiv_ab)->isoFormat('DD. MMMM YYYY')}})
                                            </small>
                                            @if(!is_null($nachricht->rueckmeldung))
                                                @if(!$archiv and $nachricht->rueckmeldung->pflicht == 1)
                                                    <div class="container-fluid">
                                                        <p>Rückmeldungen:</p>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                @for($x=1; $x <= $nachricht->userRueckmeldung->count(); $x++)
                                                                    <i class="fas fa-user-alt text-success" title="{{$x}}"></i>
                                                                @endfor
                                                                @for($x=1; $x <= ((round($nachricht->users->where('sorg2', '!=', null)->unique('email')->count()/2)) + $nachricht->users->where('sorg2', 0)->unique('email')->count())-$nachricht->userRueckmeldung->count(); $x++)
                                                                    <i class="fas fa-user-alt text-danger" title="{{$x}}"></i>
                                                                @endfor
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                            @endif
                                        </div>
                                        <div class="card-body">
                                            <p>
                                                {!! $nachricht->news !!}
                                            </p>
                                        </div>
                                        <div class="footer">

                                            @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
                                                <div class="container-fluid">
                                                    <div class="row">
                                                        @if(count($nachricht->getMedia('images'))>0)
                                                            @include('kiosk.footer.bilder')
                                                        @endif
                                                    </div>
                                                    <div class="row">
                                                        @if(count($nachricht->getMedia('files'))>0)
                                                            @include('kiosk.footer.dateiliste')
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
        <div class="row">

        </div>
    </div>
@endsection

@push('header')
    <meta http-equiv="Refresh" content="3600">
@endpush
