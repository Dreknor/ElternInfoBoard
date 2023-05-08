@extends('layouts.app')
@section('title') - Archiv @endsection

@section('content')

<div class="container-fluid">
    <div class="card blur">
        <div class="card-header border-bottom">
            <h5>
                externe Angebote
            </h5>
            <p class="card-description">
                Die hier angezeigten Nachrichten sind Angebote externer Personen/Einrichtungen
            </p>
        </div>
        @if($nachrichten == null or count($nachrichten)<1)
            <div class="card-body bg-info">
                <p>
                    Es sind keine externen Angebote vorhanden
                </p>
            </div>

        @endif
    </div>

    @foreach($nachrichten AS $nachricht)
        @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
            <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                @include('externalPost.nachricht')
            </div>
        @endif
    @endforeach

    @if($nachrichten != null and count($nachrichten)>0)
        <div class="archiv">
            {{$nachrichten->links()}}
        </div>
    @endif
</div>

@endsection
