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
                            <h6 class="card-title">
                                {{$child->first_name}} {{$child->last_name}}
                            </h6>
                        </div>
                        <div class="card-body">
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
                                                                <form action="{{url("schickzeiten/$x/".$child)}}"
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
                                                    @if($child->schickzeiten->where('weekday', $x)->where('type','ab')->first())
                                                        ab <span
                                                            class="text-info font-weight-bold">{{substr($child->schickzeiten->where('weekday', $x)->where('type','=','ab')->first()->time->format('H:i'), 0 ,5)}}</span>
                                                        Uhr
                                                        @if($child->schickzeiten->where('weekday', $x)->where('type','spät.')->first())
                                                            bis spät. <span
                                                                class="text-info font-weight-bold">{{substr($child->schickzeiten->where('weekday', $x)->where('type','=','spät.')->first()->time->format('H:i'), 0 ,5)}}</span>
                                                            Uhr
                                                        @endif
                                                    @elseif($child->schickzeiten->where('weekday', $x)->where('type','genau')->first())
                                                        <span
                                                            class="text-info font-weight-bold">{{substr($child->schickzeiten->where('weekday', $x)->where('type','genau')->first()->time->format('H:i'), 0 ,5)}}</span>
                                                        Uhr
                                                    @else
                                                        <span class="text-danger">Keine Zeit eingetragen</span>
                                                    @endif

                                                </div>
                                            </div>

                                        </li>
                                    @endfor
                                </ul>
                            </div>
                        </div>

                        <div class="card-footer">
                            <a class="text-danger"
                               href="{{url('schickzeiten/'.auth()->id().'/trash/'.\Illuminate\Support\Str::replace(' ', '_',$child))}}">
                                <i class="fa fa-trash"></i> alles löschen
                            </a>
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
