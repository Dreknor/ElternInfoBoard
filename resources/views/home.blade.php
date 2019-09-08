@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4>Alle aktuellen Mitteilungen</h4>
                </div>
                <div class="card-body"></div>
            </div>
        </div>

    </div>


    @if($nachrichten != null )
        @foreach($nachrichten AS $nachricht)
            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                @include('nachrichten.nachricht')
            @endif
        @endforeach

        {{$nachrichten->links()}}
    @else
        <div class="card">
            <div class="card-body bg-info">
                <p>
                    Es sind keine Nachrichten vorhanden
                </p>
            </div>
        </div>
            @endif
</div>
@endsection
