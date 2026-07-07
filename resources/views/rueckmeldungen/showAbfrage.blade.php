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
                    <div class="col-auto pull-right">
                        <a href="{{url('userrueckmeldung/'.$rueckmeldung->id.'/new')}}" class="btn btn-outline-info">
                            <i class="fa fa-plus-circle"></i>
                            <div class="d-none d-md-inline">
                                neue Rückmeldung anlegen
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped table-sm" id="abfrageTable">
                        <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Name</th>
                            <th>Email</th>
                            @foreach($rueckmeldung->options as $option)
                                <th>{{$option->option}}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rueckmeldung->userRueckmeldungen as $userRueckmeldung)
                            <tr>
                                <td class="text-center">
                                    <a href="{{url('userrueckmeldung/'.$rueckmeldung->id.'/edit/'.$userRueckmeldung->id)}}">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <td data-sort="{{trim($userRueckmeldung->user->familie_name)}}">
                                    {{trim($userRueckmeldung->user->familie_name)}}, {{$userRueckmeldung->user->vorname}}
                                </td>
                                <td>
                                    {{$userRueckmeldung->user->email}}
                                </td>
                                @foreach($rueckmeldung->options as $option)
                                    <td class="text-center @if($userRueckmeldung->answers->contains('option_id', $option->id) and $option->type) bg-success @endif">
                                        @if($userRueckmeldung->answers->contains('option_id', $option->id) and $userRueckmeldung->answers->where('option_id', $option->id)->first() != null)
                                            @switch($option->type)
                                                @case('text')
                                                    @if($userRueckmeldung->answers->where('option_id', $option->id)->first()->answer != "")
                                                        {{$userRueckmeldung->answers->where('option_id', $option->id)->first()->answer}}
                                                    @else
                                                        <i class="fa fa-slash"></i>
                                                    @endif
                                                    @break
                                                @case('textbox')
                                                    @if($userRueckmeldung->answers->where('option_id', $option->id)->first()->answer != "")
                                                        {!! $userRueckmeldung->answers->where('option_id', $option->id)->first()->answer !!}
                                                    @else
                                                        <i class="fa fa-slash"></i>
                                                    @endif
                                                    @break
                                                @case('check')
                                                    <i class="fa fa-check"></i>
                                                    @break
                                            @endswitch
                                        @else
                                            <i class="fa fa-slash"></i>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr class="text-center table-secondary">
                            <th colspan="3">Summe:</th>
                            @foreach($rueckmeldung->options as $option)
                                <th>
                                    @if($option->type == 'check')
                                        {{$option->answers->count()}}
                                    @else
                                        {{$option->answers->where('answer', '!=', '')->count()}}
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

    <script>
        $('#abfrageTable').dataTable({
            paging: false,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: 0 }
            ],
            language: {
                search: "Suche:",
                zeroRecords: "Keine Einträge gefunden",
                info: "_TOTAL_ Einträge",
                infoEmpty: "Keine Einträge",
                infoFiltered: "(gefiltert aus _MAX_ Einträgen)"
            }
        });
    </script>
@endpush

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet"/>

@endsection
