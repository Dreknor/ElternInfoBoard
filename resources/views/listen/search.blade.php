@extends('layouts.app')
@section('title') - Listen @endsection


@section('css')

    <style type="text/css">
        @media (min-width: 576px) {
            .card-columns {
                column-count: 1;
            }
        }

        @media (min-width: 768px) {
            .card-columns {
                column-count: 2;
            }
        }

        @media (min-width: 992px) {
            .card-columns {
                column-count: 3;
            }
        }

        @media (min-width: 1200px) {
            .card-columns {
                column-count: 3;
            }
        }
    </style>

@endsection

@section('content')
    <div class="container-fluid">
        <a href="{{url('listen')}}" class="btn btn-outline-primary">
            <i class="fa fa-arrow-left"></i>
            Zurück
        </a>
        @if(auth()->user()->can('edit terminliste'))
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            gefundene Listen
                    </h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ url('listen/search') }}">
                        @csrf
                        <div class="input-group mb-3">
                            <input type="text" name="query" class="form-control" placeholder="Suche nach Listenname">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit">Suchen</button>
                            </div>
                        </div>
                    </form>
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>
                                Titel
                            </th>
                            <th>
                                abgelaufen am
                            </th>
                            <th>
                                Aktionen
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($archiv as $liste)
                            <tr>
                                <td>
                                    {{$liste->listenname}}
                                </td>
                                <td>
                                    {{$liste->ende->format('d.m.Y')}}
                                </td>
                                <td>
                                    <a href="{{url("listen/$liste->id")}}" class="card-link">
                                        <i class="fa fa-eye"></i>
                                    </a>

                                    <a href="{{url("listen/$liste->id/refresh")}}" class="card-link">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3">
                                {{$archiv->links()}}
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    @endif
@endsection
