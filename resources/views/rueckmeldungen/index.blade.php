@extends('layouts.app')
@section('title')
    - Rückmeldungen
@endsection

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title">
                            Rückmeldungen
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-hover" id="rueckmeldungenTable">
                    <thead>
                    <tr>
                        <td></td>
                        <th>Nachricht</th>
                        <th>Ende</th>
                        <th>Typ</th>
                        <th>Anzahl</th>
                        <td></td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rueckmeldungen as $rueckmeldung)
                        <tr>
                            <td>
                                @if($rueckmeldung->type == "email")
                                    <a href="{{url('rueckmeldungen/'.$rueckmeldung->id."/show/")}}">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                            <td>
                                {{$rueckmeldung->post->header}}
                            </td>
                            <td>
                                {{$rueckmeldung->ende->format('d.m.Y')}}
                                @if($rueckmeldung->pflicht)
                                    <i class="text-danger fas fa-exclamation-circle"></i>
                                @endif
                            </td>
                            <td>
                                @switch($rueckmeldung->type)
                                    @case('email')
                                        <i class="fas fa-envelope" title="Email an {{$rueckmeldung->empfaenger}}"></i>
                                        @break
                                    @case('poll')
                                        <i class="fas fa-poll" title="Umfrage"></i>
                                        @break
                                    @case('bild')
                                        <i class="fas fa-image" title="Bild"></i>
                                        @break
                                    @case('abfrage')
                                        <i class="fas fa-table" title="Abfrage"></i>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                {{$rueckmeldung->rueckmeldungen}}
                            </td>
                            <td>
                                @if($rueckmeldung->type == "email" or $rueckmeldung->type == 'abfrage')
                                    <a href="{{url('rueckmeldungen/'.$rueckmeldung->id."/download")}}">
                                        <i class="fa fa-download"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="{{asset('js/moment-with-locales.js')}}"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.12.1/sorting/datetime-moment.js"></script>

    <script>
        $.fn.dataTable.moment('D.M.YYYY');
        $('#rueckmeldungenTable').dataTable();

    </script>
@endpush

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet"/>

@endsection
