@extends('layouts.app')
@section('title')
    - Rückmeldungen
@endsection

@section('content')
    <a href="{{url('rueckmeldungen')}}" class="btn btn-round btn-primary">zurück</a>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title">
                            Rückmeldungen zu {{$rueckmeldung->post->header}}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-hover" id="rueckmeldungenTable">
                    <thead>
                    <tr>
                        <td></td>
                        <th>Zeitpunkt</th>
                        <th>Benutzer</th>
                        <th>Rückmeldung</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rueckmeldungen as $userRueckmeldung)
                        <tr>
                            <td>
                                @if($rueckmeldung->type == "email")
                                    <a href="{{url('rueckmeldungen/'.$rueckmeldung->id."/download/".$userRueckmeldung->users_id)}}">
                                        <i class="fa fa-download"></i>
                                    </a>
                                @endif
                            </td>
                            <td>
                                {{$userRueckmeldung->created_at->format('d.m.Y H:i')}}
                            </td>
                            <td>
                                {{$userRueckmeldung->user->name}}
                            </td>
                            <td>
                                {!! $userRueckmeldung->text !!}
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
