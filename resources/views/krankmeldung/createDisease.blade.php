@extends('layouts.app')

@section('title')
    - Krankheiten verwalten
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Diese Seite wurde zur neuen Krankheitenverwaltung verschoben.
                </div>
            </div>
        </div>
    </div>

    <script>
        window.location.href = "{{route('diseases.index')}}";
    </script>
@endsection

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
                                            <form action="{{route('active-diseases.toggle', $activeDisease->id)}}"
                                                  method="post">
                                                @csrf
                                                @method('put')
                                                <button type="submit" class="btn btn-warning">deativieren</button>
                                            </form>
                                        @else
                                            <form action="{{route('active-diseases.delete', $activeDisease->id)}}"
                                                  method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-danger">löschen</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$activeDisease->active)
                                            <form action="{{route('active-diseases.toggle', $activeDisease->id)}}"
                                                  method="post">
                                                @csrf
                                                @method('put')
                                                <button type="submit" class="btn btn-warning">freigeben</button>
                                            </form>
                                        @else
                                            <a href="{{route('active-diseases.extend', $activeDisease->id)}}"
                                                  class="btn btn-primary">verlängern</a>
                                        @endif

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
