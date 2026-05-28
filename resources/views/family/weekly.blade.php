@extends('layouts.app')
@section('title') - Wochenplan @endsection

@section('content')
<div class="container-fluid">

    {{-- Wochen-Navigation --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="{{ route('family.weekly', ['week' => $prev_week]) }}" class="btn btn-outline-secondary">
            <i class="fas fa-chevron-left"></i> Vorherige Woche
        </a>

        <div class="text-center">
            <h4 class="font-bold mb-1">{{ $week_label }}</h4>
            @if($current_week !== $week_start->format('Y-\WW'))
                <a href="{{ route('family.weekly') }}" class="text-sm text-blue-600">
                    <i class="fas fa-calendar-day"></i> Zur aktuellen Woche
                </a>
            @endif
        </div>

        <div class="d-flex gap-2 align-items-center">
            @if($children->count() > 0)
            <a href="{{ route('family.weekly.pdf', ['week' => $week_start->format('Y-\WW')]) }}"
               class="btn btn-outline-danger btn-sm" title="PDF herunterladen">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            @endif
            <a href="{{ route('family.weekly', ['week' => $next_week]) }}" class="btn btn-outline-secondary">
                Nächste Woche <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    {{-- Ferien-Hinweis --}}
    @if($holidays->isNotEmpty())
        <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded p-3 mb-4 d-flex align-items-center gap-2">
            <i class="fas fa-umbrella-beach text-yellow-600 text-xl"></i>
            <div>
                @foreach($holidays as $holiday)
                    <strong>{{ $holiday->name }}</strong>
                    <span class="text-sm text-gray-600">
                        ({{ $holiday->start->format('d.m.') }}–{{ $holiday->end->format('d.m.Y') }})
                    </span>
                    @if(!$loop->last), @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Kind-Tabs oder leerer Zustand --}}
    @if($children->count() > 0)
    <div x-data="{ activeChild: {{ $children->first()['child']->id }} }">

        {{-- Tab-Leiste: bei mehreren Kindern als Tabs, bei einem Kind als einfache Überschrift --}}
        @if($children->count() > 1)
        <div class="d-flex gap-2 mb-4 overflow-x-auto pb-1">
            @foreach($children as $childData)
            <button @click="activeChild = {{ $childData['child']->id }}"
                    :class="activeChild === {{ $childData['child']->id }}
                        ? 'bg-blue-600 text-white shadow-md'
                        : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'"
                    class="px-4 py-2 rounded-lg font-semibold whitespace-nowrap transition-all text-sm">
                <i class="fas fa-child mr-1"></i>
                {{ $childData['child']->first_name }}
                @if($childData['klasse'])
                    <span class="text-xs opacity-75">({{ $childData['klasse'] }})</span>
                @endif
                @if($childData['summary']['sick_days'] > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-4 h-4 bg-red-500 text-white text-xs rounded-full">
                        K
                    </span>
                @endif
                @if($childData['summary']['has_vertretungen'])
                    <span class="ml-1 inline-flex items-center justify-center w-4 h-4 bg-orange-400 text-white text-xs rounded-full" title="Vertretungen diese Woche">
                        V
                    </span>
                @endif
                @if(($childData['summary']['pending_abfragen'] ?? 0) > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-4 h-4 bg-blue-500 text-white text-xs rounded-full" title="{{ $childData['summary']['pending_abfragen'] }} offene Abfrage(n)">
                        A
                    </span>
                @endif
            </button>
            @endforeach
        </div>
        @else
        {{-- Einzelnes Kind: Name als Überschrift anzeigen --}}
        @php $onlyChild = $children->first(); @endphp
        <div class="d-flex align-items-center gap-2 mb-4">
            <i class="fas fa-child text-blue-600 text-lg"></i>
            <h5 class="font-bold text-gray-800 mb-0">
                {{ $onlyChild['child']->first_name }} {{ $onlyChild['child']->last_name }}
                @if($onlyChild['klasse'])
                    <span class="text-sm text-gray-500 font-normal">({{ $onlyChild['klasse'] }})</span>
                @endif
            </h5>
            @if($onlyChild['summary']['sick_days'] > 0)
                <span class="badge badge-danger"><i class="fas fa-thermometer-half"></i> krank</span>
            @endif
            @if($onlyChild['summary']['has_vertretungen'])
                <span class="badge badge-warning text-dark"><i class="fas fa-exchange-alt"></i> Vertretungen</span>
            @endif
        </div>
        @endif

        {{-- Wochenplan pro Kind --}}
        @foreach($children as $childData)
        <div x-show="activeChild === {{ $childData['child']->id }}" x-cloak>
            @include('family.components.week-table', ['childData' => $childData])
        </div>
        @endforeach
    </div>

    @else
        {{-- Leerer Zustand --}}
        <div class="text-center py-12">
            <i class="fas fa-child" style="font-size: 3rem; color: #d1d5db;"></i>
            <h5 class="text-gray-500 mt-3">Keine Kinder verknüpft</h5>
            <p class="text-gray-400 text-sm mt-1">
                Bitte fügen Sie Ihre Kinder unter
                <a href="{{ route('child.index') }}" class="text-blue-600 font-semibold">Kinderverwaltung</a>
                hinzu, um den Wochenplan nutzen zu können.
            </p>
        </div>
    @endif

</div>
@endsection

