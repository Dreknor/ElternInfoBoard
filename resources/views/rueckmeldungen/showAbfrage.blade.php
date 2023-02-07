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
                            Rückmeldungen
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover table-striped">
                    <thead>
                    <tr>
                        <th></th>
                        <th>
                            Name
                        </th>

                        @foreach($rueckmeldung->options as $option)
                            <th>
                                {{$option->option}}
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rueckmeldung->userRueckmeldungen as $userRueckmeldung)
                        <tr>
                            <td>
                                <a href="{{url('userrueckmeldung/'.$rueckmeldung->id.'/edit/'.$userRueckmeldung->id)}}">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </td>
                            <td>
                                {{$userRueckmeldung->user->name}}
                            </td>
                            @foreach($rueckmeldung->options as $option)
                                <td class="text-center @if($userRueckmeldung->answers->contains('option_id', $option->id) and $option->type == 'check') bg-success @endif">
                                    @if($userRueckmeldung->answers->contains('option_id', $option->id) and $userRueckmeldung->answers->where('option_id', $option->id)->first() != null)
                                        @switch($option->type)
                                            @case('text')
                                                @if($userRueckmeldung->answers->where('option_id', $option->id)->first()->answer != "")
                                                    {{$userRueckmeldung->answers->where('option_id', $option->id)->first()->answer}}
                                                @else
                                                    <i class="fa fa-slash ">

                                                        @endif
                                                        @break
                                                        @case('check')
                                                            <i class="fa fa-check">
                                                                @break
                                                                @endswitch
                                                                @else
                                                                    <i class="fa fa-slash ">
                                                @endif
                                </td>
                            @endforeach
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
