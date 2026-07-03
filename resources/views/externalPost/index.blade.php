@extends('layouts.app')
@section('title') - Externe Nachrichten @endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-2xl font-bold" style="color: var(--color-text-primary);">
                    <i class="far fa-newspaper" style="color: var(--color-widget-primary-from);"></i> Externe Nachrichten
                </h2>
                <a href="{{ url('/') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Zurück zum Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Table of Contents --}}
    <div class="rounded-xl shadow-lg overflow-hidden mb-4" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);">
        <div class="px-6 py-4 border-b" style="background-color: var(--color-primary); border-color: var(--color-primary-dark);">
            <h5 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="far fa-newspaper"></i>
                Externe Nachrichten
            </h5>
            <p class="text-sm text-white/80 mt-1 mb-0">
                Die hier angezeigten Nachrichten sind Angebote externer Personen/Einrichtungen.
            </p>
        </div>

        @if($nachrichten != null and count($nachrichten) > 0)
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($nachrichten AS $nachricht)
                        @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                            <a href="#{{$nachricht->id}}"
                               class="group relative overflow-hidden rounded-lg shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-0.5 p-3"
                               style="background-color: var(--color-body-bg);">
                                <div class="flex items-start gap-2">
                                    <div class="flex-1 min-w-0">
                                        <span class="block text-sm font-semibold leading-tight break-words" style="color: var(--color-text-primary);">
                                            {{\Illuminate\Support\Str::limit($nachricht->header, 60, $end='...')}}
                                        </span>
                                        <span class="text-xs mt-0.5 block" style="color: var(--color-text-secondary);">
                                            {{ $nachricht->updated_at->isoFormat('DD. MMM YYYY') }}
                                        </span>
                                    </div>
                                    <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <div class="p-6 text-center" style="background-color: var(--color-primary-light);">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3"
                     style="background-color: color-mix(in srgb, var(--color-primary) 20%, transparent);">
                    <i class="far fa-newspaper text-xl" style="color: var(--color-primary);"></i>
                </div>
                <p class="text-sm font-medium" style="color: var(--color-text-secondary);">
                    Es sind keine externen Nachrichten vorhanden
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

    @if($nachrichten != null and count($nachrichten) > 0)
        <div class="mt-4">
            {{$nachrichten->links()}}
        </div>
    @endif

</div>
@endsection


