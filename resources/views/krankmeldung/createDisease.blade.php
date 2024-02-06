@extends('layouts.app')

@section('title')
    - meldepfl. Krankheit erstellen
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">
                            neue meldepflichtige Krankheit erstellen:
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{url('diseases/create')}}" method="post" class="form form-horizontal">
                            @csrf
                            <div class="form-row">
                                <label for="disease">
                                    Name der Krankheit:
                                </label>
                                <select class="custom-select" name="disease_id" , id="disease">
                                    @foreach($diseases as $disease)
                                        <option value="{{$disease->id}}">{{$disease->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row">
                                <button type="submit" class="btn btn-primary">Krankheit erstellen</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        Erkrankungen bearbeiten
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">von - bis</th>
                                <th scope="col" colspan="2">Aktionen</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($activeDiseases as $activeDisease)
                                <tr>
                                    <td>{{$activeDisease->disease->name}}</td>
                                    <td>{{$activeDisease->start->format('d.m.Y')}}
                                        - {{$activeDisease->end->format('d.m.Y')}}</td>

                                    <td>
                                        @if($activeDisease->active)
                                            <form action="{{url('diseases/'.$activeDisease->id.'/active')}}"
                                                  method="post">
                                                @csrf
                                                @method('put')
                                                <button type="submit" class="btn btn-warning">deativieren</button>
                                            </form>
                                        @else
                                            <form action="{{url('diseases/'.$activeDisease->id.'/delete')}}"
                                                  method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-danger">löschen</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{url('diseases/'.$activeDisease->id.'/extend')}}"
                                           class="btn btn-primary">verlängern</a>

                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
