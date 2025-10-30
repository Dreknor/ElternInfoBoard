@extends('layouts.app')
@section('title') - Archiv @endsection

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800">
            <h5 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                <i class="fas fa-archive"></i>
                Archivierte Nachrichten
            </h5>
        </div>

        <!-- Body -->
        <div class="p-6">
            <p class="text-gray-600 mb-4">
                Hier finden Sie alle archivierten Nachrichten sortiert nach Monaten.
            </p>

            <!-- Month Navigation -->
            <div class="flex flex-wrap gap-2">
                @for($x = \Carbon\Carbon::now(); $x->greaterThanOrEqualTo((!is_null($first_post))? $first_post->archiv_ab : \Carbon\Carbon::now()); $x->subMonth())
                    <a href="{{url('archiv/'.$x->format('Y-m'))}}"
                       class="inline-flex items-center gap-2 px-4 py-2 border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white font-medium rounded-lg transition-all duration-200">
                        <i class="fas fa-calendar-alt text-sm"></i>
                        {{$x->locale('de')->monthName}} {{$x->format ('Y')}}
                    </a>
                @endfor
            </div>
        </div>

        <!-- No Messages Info -->
        @if($nachrichten == null or count($nachrichten)<1)
            <div class="bg-cyan-50 border-t border-cyan-200 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-cyan-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-cyan-800 font-medium mb-0">
                            Keine Nachrichten verfügbar
                        </p>
                        <p class="text-cyan-600 text-sm mb-0">
                            Es sind keine archivierten Nachrichten für den ausgewählten Zeitraum vorhanden.
                        </p>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- Messages Container -->
    @if($nachrichten != null and count($nachrichten) > 0)
        <div class="space-y-6">
            @foreach($nachrichten AS $nachricht)
                @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                    <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                        @include('archiv.nachricht')
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            <div class="bg-white rounded-lg shadow-md px-6 py-4">
                {{$nachrichten->links()}}
            </div>
        </div>
    @endif
</div>

@endsection
