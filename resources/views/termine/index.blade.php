@extends('layouts.app')
@section('title') - Alle Termine @endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="far fa-calendar-alt text-green-600"></i> Alle Termine
                </h2>
                <div>
                    <a href="{{ url('/') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Zurück zum Dashboard
                    </a>
                    @can('create termine')
                        <a href="{{ url('/termin/create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Neuer Termin
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-4">
                    @if($termine && count($termine) > 0)
                        @php
                            $terminsByMonth = $termine->groupBy(function($termin) {
                                $displayDate = $termin->display_date ?? $termin->start;
                                return $displayDate->format('Y-m');
                            });
                        @endphp

                        @foreach($terminsByMonth as $month => $monthTermine)
                            <div class="mb-4">
                                <h4 class="text-lg font-bold text-gray-700 mb-3 pb-2 border-b">
                                    <i class="far fa-calendar"></i>
                                    {{ \Carbon\Carbon::parse($month.'-01')->locale('de')->isoFormat('MMMM YYYY') }}
                                </h4>
                                <div class="space-y-3">
                                    @foreach($monthTermine as $termin)
                                        @php
                                            $displayDate = $termin->display_date ?? $termin->start;
                                            $isMultiDay = $termin->ende && $termin->start->format('Y-m-d') != $termin->ende->format('Y-m-d');
                                        @endphp
                                        <div class="p-4 border @if($isMultiDay) border-orange-400 bg-orange-50 @else border-gray-200 @endif rounded-lg hover:shadow-md transition-all duration-200">
                                            <div class="row">
                                                <div class="col-md-2 text-center mb-3 mb-md-0">
                                                    <div class="d-inline-block" style="min-width: 80px;">
                                                        <div class="@if($isMultiDay) bg-orange-600 @else bg-green-600 @endif text-white rounded-t px-3 py-1">
                                                            <small class="font-bold">{{ $displayDate->format('M') }}</small>
                                                        </div>
                                                        <div class="bg-white border border-gray-200 rounded-b px-3 py-2">
                                                            <span class="text-3xl font-bold text-gray-800">{{ $displayDate->format('d') }}</span>
                                                        </div>
                                                        <small class="text-gray-600 d-block mt-1">
                                                            {{ $displayDate->locale('de')->isoFormat('dddd') }}
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-md-10">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h5 class="font-bold text-gray-800 mb-0">
                                                            {{ $termin->terminname }}
                                                            @if($isMultiDay)
                                                                <span class="badge badge-warning ml-2" title="Mehrtägiger Termin">
                                                                    <i class="fas fa-calendar-week"></i> Mehrtägig
                                                                </span>
                                                            @endif
                                                            @if(auth()->user()->can('view all') && ($termin->public ?? false))
                                                                <span class="badge badge-success ml-2" title="Dieser Termin ist öffentlich">Öffentlich</span>
                                                            @endif
                                                        </h5>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <!-- Calendar Links -->
                                                            <a href="{{$termin->link(optional(auth()->user())->calendar_prefix)->ics()}}"
                                                               class="btn btn-sm btn-outline-secondary"
                                                               title="ICS-Download für Apple und Windows">
                                                                <img src="{{asset('img/ics-icon.png')}}" style="width: 16px; height: 16px;" alt="ICS">
                                                            </a>
                                                            <a href="{{$termin->link(optional(auth()->user())->calendar_prefix)->google()}}"
                                                               class="btn btn-sm btn-outline-secondary"
                                                               target="_blank"
                                                               title="Google-Kalender-Link">
                                                                <img src="{{asset('img/icon-google-cal.png')}}" style="width: 16px; height: 16px;" alt="Google Calendar">
                                                            </a>

                                                            @can('edit termin')
                                                                <a href="{{ url('/termin/'.$termin->id.'/edit') }}" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                        </div>
                                                    </div>

                                                    <div class="row mt-3">
                                                        <div class="col-md-6 mb-2">
                                                            <p class="text-gray-600 mb-0">
                                                                @php
                                                                    $isMultiDay = $termin->ende && $termin->start->format('Y-m-d') != $termin->ende->format('Y-m-d');
                                                                    $daysDiff = $isMultiDay ? $termin->start->diffInDays($termin->ende) + 1 : 0;
                                                                @endphp

                                                                @if($isMultiDay)
                                                                    <i class="fas fa-calendar-week text-blue-600"></i>
                                                                    <strong>Zeitraum:</strong>
                                                                    {{ $termin->start->locale('de')->isoFormat('D. MMM') }}
                                                                    @if(!$termin->fullDay)
                                                                        {{ $termin->start->format('H:i') }}
                                                                    @endif
                                                                    -
                                                                    {{ $termin->ende->locale('de')->isoFormat('D. MMM') }}
                                                                    @if(!$termin->fullDay)
                                                                        {{ $termin->ende->format('H:i') }}
                                                                    @endif
                                                                    <span class="badge badge-info ml-2">{{ floor($daysDiff) }} Tage</span>
                                                                @elseif($termin->fullDay)
                                                                    <i class="far fa-calendar text-blue-600"></i>
                                                                    <strong>Ganztägig</strong>
                                                                @else
                                                                    <i class="far fa-clock text-blue-600"></i>
                                                                    <strong>Uhrzeit:</strong> {{ $termin->start->format('H:i') }} Uhr
                                                                    @if($termin->ende && $termin->start->format('Y-m-d') == $termin->ende->format('Y-m-d'))
                                                                        - {{ $termin->ende->format('H:i') }} Uhr
                                                                    @endif
                                                                @endif
                                                            </p>
                                                        </div>

                                                        @if($termin->groups && count($termin->groups) > 0)
                                                            <div class="col-md-6 mb-2">
                                                                <p class="text-gray-600 mb-0">
                                                                    <i class="fas fa-users text-purple-600"></i>
                                                                    <strong>Gruppen:</strong>
                                                                    @foreach($termin->groups as $group)
                                                                        <span class="badge badge-secondary">{{ $group->name }}</span>
                                                                    @endforeach
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="far fa-calendar-alt text-gray-300" style="font-size: 4rem;"></i>
                            <p class="text-gray-500 mt-3 text-lg">Keine Termine vorhanden</p>
                            @can('create termine')
                                <a href="{{ url('/termin/create') }}" class="btn btn-success mt-3">
                                    <i class="fas fa-plus"></i> Ersten Termin erstellen
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .space-y-3 > * + * {
        margin-top: 0.75rem;
    }
    .border-orange-400 {
        border-color: #fb923c !important;
    }
    .bg-orange-50 {
        background-color: #fff7ed !important;
    }
    .bg-orange-600 {
        background-color: #ea580c !important;
    }
</style>
@endsection
