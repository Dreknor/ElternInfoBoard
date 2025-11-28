@extends('layouts.app')
@section('title') - Archiv @endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="far fa-newspaper text-blue-600"></i> Expterne Nachrichten
                    </h2>
                    <a href="{{ url('/') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Zurück zum Dashboard
                    </a>
                </div>
                <div class="mb-4">
                    <p class="text-muted">
                        Die hier angezeigten Nachrichten sind Angebote externer Personen/Einrichtungen.
                    </p>
                </div>
            </div>
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


