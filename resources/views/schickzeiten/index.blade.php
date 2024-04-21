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
            @foreach($childs as $child)
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">
                                {{$child}}
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
                                                            <a href="{{url("schickzeiten/edit/$x/".$child)}}"
                                                               class="dropdown-item">
                                                                <i class="fa fa-edit"></i> bearbeiten
                                                            </a>
                                                            @if($schickzeiten->where('weekday', $x)->where('child_name',$child)->first())
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
                                                    @if($schickzeiten->where('weekday', $x)->where('child_name',$child)->where('type','ab')->first())
                                                        ab <span
                                                            class="text-info font-weight-bold">{{substr($schickzeiten->where('weekday', $x)->where('type','=','ab')->where('child_name',$child)->first()->time->format('H:i'), 0 ,5)}}</span>
                                                        Uhr
                                                        @if($schickzeiten->where('weekday', $x)->where('type','spät.')->where('child_name',$child)->first())
                                                            bis spät. <span
                                                                class="text-info font-weight-bold">{{substr($schickzeiten->where('weekday', $x)->where('type','=','spät.')->where('child_name',$child)->first()->time->format('H:i'), 0 ,5)}}</span>
                                                            Uhr
                                                        @endif
                                                    @elseif($schickzeiten->where('weekday', $x)->where('type','genau')->where('child_name',$child)->first())
                                                        <span
                                                            class="text-info font-weight-bold">{{substr($schickzeiten->where('weekday', $x)->where('type','genau')->where('child_name',$child)->first()->time->format('H:i'), 0 ,5)}}</span>
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
            <div class="card-header">
                <h6 class="card-title">
                    Neues Kind anlegen
                </h6>
            </div>
            <div class="card-body">
                <form method="post" class="form form-horizontal" action="{{url('schickzeiten/child/create')}}">
                    @csrf
                    @error('child')
                        <span>{{ $message }}</span>
                    @enderror
                    <input name="child" class="form-control @error('child') has-error @enderror" placeholder="Name des neuen Kindes" required>
                    <button class="btn btn-success btn-block">Neues Kind speichern</button>
                </form>
            </div>
        </div>

    </div>
@endsection
