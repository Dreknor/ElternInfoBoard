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
                    @foreach($children as $child)
                        <li class="list-group-item"  data-toggle="collapse" href="#collapse{{$child->id}}_{{$child->users_id}}" role="button" >
                            {{$child->last_name}}, {{$child->first_name}} <span class="badge badge-primary">{{$child->schickzeiten->count()}}</span>
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
                                                    @if($child->schickzeiten->where('weekday', $x)->count() > 0) {{$child->schickzeiten->where('weekday', $x)->first()->time_ab?->format('H:i')}} @endif
                                                </td>
                                                <td>
                                                    @if($child->schickzeiten->where('weekday', $x)->count() > 0) {{ $child->schickzeiten->where('weekday', $x)->first()?->time?->format('H:i') }} @endif
                                                </td>
                                                <td>
                                                    @if($child->schickzeiten->where('weekday', $x)->count() > 0)  {{$child->schickzeiten->where('weekday', $x)->first()?->time_spaet?->format('H:i')}} @endif
                                                </td>
                                                <td>
                                                    <div class="row">
                                                        <div class="col">
                                                            <a href="{{route('schickzeiten.edit',['child' => $child->id, 'day' => $x])}}" class="btn btn-link btn-primary text-primary">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        </div>
                                                        <div class="col">
                                                            @if($child->schickzeiten->where('weekday', $x)->first())
                                                                <form action="{{route('schickzeiten.destroy', ['schickzeit' => $child->schickzeiten->where('weekday', $x)->first()->id])}}" method="post">
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
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
