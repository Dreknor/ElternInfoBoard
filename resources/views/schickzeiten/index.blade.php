@extends('layouts.app')

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
                <div class="col-lg-6 col-md-6 "col-sm-12>
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
                                        <th>

                                        </th>
                                        <th>
                                            ab
                                        </th>
                                        <th>
                                            genau
                                        </th>
                                        <th>
                                            spätestens
                                        </th>
                                        <td></td>
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
                                            <td>
                                                <div class="row">
                                                    <div class="col">
                                                        <a href="{{url("schickzeiten/edit/$x/".$child)}}" class="card-link">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                    </div>
                                                    <div class="col">
                                                        @if($schickzeiten->where('weekday', $x)->where('child_name',$child)->first())
                                                            <form action="{{url("schickzeiten/$x/".$child)}}" method="post">
                                                                @csrf
                                                                @method('delete')
                                                                <button type="submit" class="btn btn-link btn-danger text-danger"><i class="fa fa-trash"></i></button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endfor

                                </table>
                            </div>

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
