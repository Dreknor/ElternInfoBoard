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
               @can('download schickzeiten')
                       <a href="{{url('schickzeiten/download')}}" class="btn btn-primary">
                           download
                       </a>
               @endcan
           </div>
        </div>

    </div>
    <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($childs->sortBy('child_name') as $child)
                            <li class="list-group-item"  data-toggle="collapse" href="#collapse{{$child->id}}_{{$child->users_id}}" role="button" >
                                {{$child->child_name}} ( {{$child->user->name}} )
                                @php
                                    $schickzeit = $schickzeiten->where('child_name',$child->child_name)->first();
                                @endphp
                                @if(!$schickzeit->child_id)
                                    <div class="pull-right">
                                        <a href="{{route('child.createFromSchickzeit', [$schickzeit->id])}}"
                                           class="card-link">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    </div>
                                @endif
                            </li>
                            <div class="collapse card mt-2" id="collapse{{$child->id}}_{{$child->users_id}}">
                                <div class="card-body">
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
                                                    @if($schickzeiten->where('weekday', $x)->where('child_name',$child->child_name)->where('users_id',$child->users_id)->where('type','ab')->first())
                                                        {{substr($schickzeiten->where('weekday', $x)->where('type','=','ab')->where('child_name',$child->child_name)->where('users_id',$child->users_id)->first()->time->format('H:i'), 0 ,5)}} Uhr
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($schickzeiten->where('weekday', $x)->where('child_name',$child->child_name)->where('users_id',$child->users_id)->where('type','genau')->first())
                                                        {{$schickzeiten->where('weekday', $x)->where('type','genau')->where('child_name',$child->child_name)->where('users_id',$child->users_id)->first()->time->format('H:i')}} Uhr
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($schickzeiten->where('weekday', $x)->where('child_name',$child->child_name)->where('users_id',$child->users_id)->where('type','spät.')->first())
                                                        {{substr($schickzeiten->where('weekday', $x)->where('child_name',$child->child_name)->where('users_id',$child->users_id)->where('type','=','spät.')->first()->time->format('H:i'), 0 ,5)}} Uhr
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="row">
                                                        <div class="col">
                                                                <a href="{{url("verwaltung/schickzeiten/edit/$x/".$child->child_name."/".$child->users_id)}}" class="card-link">
                                                                    <i class="fa fa-edit"></i>
                                                                </a>
                                                        </div>
                                                        <div class="col">
                                                            @if($schickzeiten->where('weekday', $x)->where('child_name',$child->child_name)->where('users_id',$child->users_id)->first())
                                                                <form action="{{url("verwaltung/schickzeiten/$x/".$child->child_name."/".$child->users_id)}}" method="post">
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
                                <div class="card-footer">
                                    <a href="{{url('verwaltung/schickzeiten/'.$child->users_id.'/trash/'.\Illuminate\Support\Str::replace(' ', '_',$child->child_name))}}"
                                       class="btn btn-danger">
                                        <i class="fa fa-trash"></i> alle Schickzeiten löschen
                                    </a>
                                </div>

                            </div>
                        @endforeach
                    </ul>
                </div>
            </div>

        <div class="row mt-5">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">
                            Neues Kind anlegen
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post" class="form form-horizontal" action="{{url('verwaltung/schickzeiten/child/create')}}">
                            @csrf
                            @error('child')
                            <span>{{ $message }}</span>
                            @enderror
                            <div class="form-row">
                                <label for="'child">Name des Kindes</label>
                                <input name="child" class="form-control @error('child') has-error @enderror" placeholder="Name des neuen Kindes" required autofocus id="child">
                            </div>

                            <div class="form-row mt-2">
                                <label for="'parent">Name des Elternteil</label>
                                <select name="parent" class="custom-select @error('parent') has-error @enderror" required id="parent">
                                    <option></option>
                                    @foreach($parents as $parent)
                                        <option value="{{$parent->id}}">
                                            {{$parent->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-success btn-block">Neues Kind speichern</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
