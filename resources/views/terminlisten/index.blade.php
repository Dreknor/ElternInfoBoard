@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            @if(count($terminlisten)<1)
                    <div class="col-md-10 col-sm-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>
                                    aktuelle Listen
                                </h5>
                            </div>
                            <div class="card-body alert-info">
                                <p>
                                    Es wurden keine aktuellen Listen gefunden
                                </p>
                            </div>
                        </div>
                    </div>
            @else


            @endif
        </div>
    </div>


@endsection