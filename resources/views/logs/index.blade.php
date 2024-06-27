@extends('layouts.app')
@section('title') - Benutzer @endsection

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title">
                            Logs
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-hover" id="logTable">
                    <thead>
                    <tr>
                        <td></td>
                        <td>Benutzer</td>
                        <td>Log</td>
                        <td>Zeit</td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{$log->datetime}}</td>
                            <td>{{$log->level_name}}</td>
                            <td>{{$log->message}}</td>
                            <td>{{$log->created_at}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>



@endsection
