@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header @if($liste->active == 0) bg-info @endif">
                <h5>
                    {{$liste->listenname}} @if($liste->active == 0) (inaktiv) @endif
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
