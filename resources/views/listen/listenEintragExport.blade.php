@extends('layouts.app')
@section('title')
    - Listen
@endsection

@section('content')
    <div class="container-fluid">
        <a class="btn btn-outline-info" href="{{url('listen')}}">zurück zur Übersicht</a>
        <div class="card">
            <div class="card-header border-bottom @if($liste->active == 0) bg-info @endif">
                <h5>
                    {{$liste->listenname}} @if($liste->active == 0)
                        (inaktiv)
                    @endif
                </h5>

                {!! $liste->comment !!}


            </div>

            <div class="card-body">
                @if($liste->eintragungen->count()> 0)
                    <ul class="list-group">
                        @foreach($liste->eintragungen as $eintrag)
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col pt-2">
                                        {{$eintrag->eintragung}}
                                    </div>
                                    <div class="col-auto pull-right">
                                        {{optional($eintrag->user)->name }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-info">
                        <p>
                            Es wurden bisher keine Eintragungen angelegt.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
@push('js')

@endpush
