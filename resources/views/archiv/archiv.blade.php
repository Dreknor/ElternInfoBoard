@extends('layouts.app')
@section('title') - Archiv @endsection

@section('content')

<div class="container-fluid">
    <div class="card blur">
        <div class="card-header border-bottom">
            <h5>
                archivierte Nachrichten
            </h5>
        </div>
        @if($nachrichten == null or count($nachrichten)<1)
            <div class="card-body bg-info">
                <p>
                    Es sind keine Nachrichten vorhanden
                </p>
            </div>

        @endif
    </div>

    @foreach($nachrichten AS $nachricht)
        @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
            <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                @include('archiv.nachricht')
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
