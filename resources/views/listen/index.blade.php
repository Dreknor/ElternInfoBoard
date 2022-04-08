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
        <div class="row justify-content-center">
                    <div class="col-md-12 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>
                                    aktuelle Listen
                                </h5>
                            </div>
                            @if(count($listen)<1)
                                <div class="card-body alert-info">
                                    <p>
                                        Es wurden keine aktuellen Listen gefunden
                                    </p>
                                </div>
                            @endif
                        </div>
                        @if(count($listen)>=1)
                            <div class="card-columns">
                                @foreach($listen as $liste)
                                    @if($liste->type == 'termin')
                                        @include('listen.cards.terminListe')
                                    @else
                                        @include('listen.cards.eintragListe')
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
        </div>
    </div>
    @if(auth()->user()->can('edit terminliste'))
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        abgelaufene Listen
                    </h5>
                </div>
                <div class="card-body">
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
