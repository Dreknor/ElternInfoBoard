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
                <table>
                    <thead>
                    <tr>
                        <th>
                            Name
                        </th>
                        <th>
                            Zeitpunkt
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
                                {{$userRueckmeldung->user->name}}
                            </td>
                            <td>
                                {{$userRueckmeldung->created_at->format('Y-m-d H:i')}}
                            </td>
                            @foreach($rueckmeldung->options as $option)
                                <th>
                                    @if($userRueckmeldung->answers->where('option_id', $option->id)->first() != null)
                                        @switch($option->type)
                                            @case('text')
                                                {{$userRueckmeldung->answers->where('option_id', $option->id)->first()->answer}}
                                                @break
                                            @case('check')
                                                1
                                                @break
                                        @endswitch
                                    @endif
                                </th>
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