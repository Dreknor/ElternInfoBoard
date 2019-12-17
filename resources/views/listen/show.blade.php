@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    {{$liste->listenname}}
                </h5>
                <div class="text-info">
                    <p class="text-muted">
                            {{ $liste->comment }}
                    </p>
                </div>
            </div>
            <div class="card-body">

            </div>
        </div>
    </div>
@endsection