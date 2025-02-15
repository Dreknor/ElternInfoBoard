@extends('layouts.app')
@section('title') - Schickzeiten @endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    Schickzeiten
                </h6>

            </div>
            <div class="card-body">
                @include('schickzeiten.infos')
            </div>
        </div>

    </div>
    <div class="container-fluid">
        <div class="row">
            @foreach($children as $child)
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                {{$child->first_name}} {{$child->last_name}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title">regelmäßige Schickzeiten</h6>
                            <div class="container-fluid">
                                <ul class="list-group">
                                    @for($x=1;$x<6;$x++)
                                        <li class="list-group-item">
                                            <div class="row">
                                                <div class="col-10">
                                                    <b>
                                                        {{$weekdays[$x]}}
                                                    </b>
                                                </div>
                                                <div class="col-1 ml-auto">
                                                    <div class="btn-group">
                                                        <a href="#" class="card-link " data-toggle="dropdown"
                                                           aria-haspopup="true" aria-expanded="false">
                                                            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                                        </a>
                                                        <div class="dropdown-menu">
                                                            <a href="{{url("schickzeiten/edit/$x/".$child->id)}}"
                                                               class="dropdown-item">
                                                                <i class="fa fa-edit"></i> bearbeiten
                                                            </a>
                                                            @if($child->schickzeiten->where('weekday', $x)->first())
                                                                <form action="{{route('schickzeiten.destroy', ['schickzeit' => $child->schickzeiten->where('weekday', $x)->first()->id])}}"
                                                                      method="post" class="form-inline">
                                                                    @csrf
                                                                    @method('delete')
                                                                    <button type="submit"
                                                                            class="dropdown-item btn-danger">
                                                                        <i class="fa fa-trash"></i> löschen
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12">
                                                    @if($child->schickzeiten->where('weekday', $x)->first())
                                                        @if($child->schickzeiten->where('weekday', $x)->first()->type == 'genau')
                                                            {{$child->schickzeiten->where('weekday', $x)->first()->time?->format('H:i')}} Uhr
                                                        @else
                                                            {{$child->schickzeiten->where('weekday', $x)->first()->time_ab?->format('H:i')}} @if(!is_null($child->schickzeiten->where('weekday', $x)->first()->time_ab) && $child->schickzeiten->where('weekday', $x)->first()->time_spaet) - @endif {{$child->schickzeiten->where('weekday', $x)->first()->time_spaet?->format('H:i')}} Uhr
                                                        @endif

                                                    @endif
                                                </div>
                                            </div>

                                        </li>
                                    @endfor
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer">
                            <h6 class="card-title">individuelle Schickzeiten</h6>
                            <div class="container-fluid">
                                <ul class="list-group">
                                    @foreach($child->schickzeiten->where('specific_date', '!=', NULL) as $schickzeit)
                                        <li class="list-group-item">
                                            <div class="row">
                                                <div class="col-12">
                                                    <b>
                                                        {{$schickzeit->specific_date->format('d.m.Y')}}:
                                                    </b>
                                                        @if($schickzeit->type =="genau")
                                                            genau {{$schickzeit->time?->format('H:i')}} Uhr
                                                        @else
                                                            ab {{$schickzeit->time_ab?->format('H:i')}} Uhr @if(!is_null($schickzeit->time_ab) && $schickzeit->time_spaet) - @endif {{$schickzeit->time_spaet?->format('H:i')}} Uhr
                                                        @endif

                                                    <div class="pull-right">
                                                        <form action="{{route('schickzeiten.destroy', ['schickzeit' => $schickzeit->id])}}" method="post">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="card bg-gradient-directional-grey-blue">
                                            <div class="card-header">
                                                <h6>
                                                    Neue individuelle Schickzeit anlegen
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="container-fluid">
                                                    <form action="{{route('schickzeiten.store', ['child' => $child->id])}}" method="post">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label for="specific_date">Datum</label>
                                                            <input type="date" name="specific_date" id="specific_date" value="{{old('specific_date', \Carbon\Carbon::now()->format('Y-m-d'))}}"
                                                                   class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="type">Typ</label>
                                                            <select name="type" class="custom-select" id="type">
                                                                <option value="genau">genau</option>
                                                                <option value="ab">ab ... bis ... Uhr</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group" id="genauZeit">
                                                            <label for="time">Zeit</label>
                                                            <input name="time" id="time" type="time" class="form-control"
                                                                   min="{{$vorgaben->schicken_ab}}" max="{{$vorgaben->schicken_bis}}"
                                                                   value="{{old('time')}}">
                                                        </div>
                                                        <div class="form-group collapse" id="spaet_row">
                                                            <div class="container-fluid">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="ab">ab ... Uhr</label>
                                                                        <input name="time_ab" type="time" class="form-control"
                                                                               min="{{$vorgaben->schicken_ab}}"
                                                                               max="{{$vorgaben->schicken_bis}}" id="spät."
                                                                               value="{{old('time_ab')}}">
                                                                    </div>
                                                                    <div class="col-md-6 ">
                                                                        <label for="spät.">spätestens (optional)</label>
                                                                        <input name="time_spaet" type="time" class="form-control"
                                                                               min="{{$vorgaben->schicken_ab}}"
                                                                               max="{{$vorgaben->schicken_bis}}" id="spät."
                                                                               value="{{old('time_spaet')}}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <button type="submit" class="btn btn-primary btn-block">Neue individuelle Schickzeit
                                                            anlegen
                                                        </button>
                                                    </form>

                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <form action="{{url("schickzeiten/$child->id")}}" method="post">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-danger btn-block">Alle Schickzeiten löschen</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>

    <div class="container-fluid">
        <div class="card">
            <a href="{{url(('einstellungen'))}}" class="btn btn-primary">Neues Kind anlegen</a>
        </div>

    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $("#type").change(function () {
                $('#spaet_row').toggle();
                $('#genauZeit').toggle();
            });
        });
    </script>
@endpush
