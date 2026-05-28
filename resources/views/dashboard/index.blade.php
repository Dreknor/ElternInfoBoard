@extends('layouts.app')
@section('title') - Dashboard @endsection

@section('content')
<div class="container-fluid">
    <!-- Willkommensbereich -->
    <div class="row">
        <div class="col-12">
            <div class="rounded-lg shadow-lg p-6 mb-4 text-white"
                 style="background-color: var(--color-primary); background-image: linear-gradient(to right, var(--color-primary), var(--color-secondary));">
                <h2 class="text-2xl font-bold mb-2">Willkommen, {{ auth()->user()->name }}!</h2>
                <p class="mb-0" style="opacity: 0.85;">{{ $datum->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}</p>
            </div>

            <!-- Hinweis auf offene Anwesenheitsabfragen -->
            @if($openAttendanceSurveys)
                <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg shadow p-3 mb-4">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clipboard-check text-orange-500" style="font-size: 1.2rem;"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <h6 class="font-bold text-orange-800 mb-2">
                                <i class="fas fa-user-clock"></i> Offene Anwesenheitsabfragen
                            </h6>
                            <p class="text-sm text-orange-800 mb-2">
                                Es gibt noch offene Anwesenheitsabfragen für Ihre Kinder. Bitte geben Sie diese zeitnah ab.
                            </p>
                            <a href="{{ url('/schickzeiten#anwesenheitsabfrage') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg transition-colors duration-200">
                                <i class="fas fa-arrow-right"></i>
                                Zu den Anwesenheitsabfragen
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @php
        $hasCheckin   = $careChildren && $careChildren->count() > 0;
        $hasLosung    = !empty($losung);
        $hasDiseases  = $dashboardDiseasesWidget !== null
                        && auth()->user()->canAny(['manage diseases', 'see diseases']);
        $hasSidebar   = $hasLosung || $hasDiseases;
        // Spaltenbreiten: CheckIn bekommt 8, Sidebar 4 – sonst jeweils 12
        $checkinCol   = ($hasCheckin && $hasSidebar) ? 'col-xl-8 col-lg-7' : 'col-12';
        $sidebarCol   = ($hasCheckin && $hasSidebar) ? 'col-xl-4 col-lg-5' : 'col-12';
        // Wenn kein CheckIn, aber Losung+Erkrankungen vorhanden: nebeneinander
        $losungCol    = (!$hasCheckin && $hasLosung && $hasDiseases) ? 'col-lg-6' : 'col-12';
        $diseasesCol  = (!$hasCheckin && $hasLosung && $hasDiseases) ? 'col-lg-6' : 'col-12';
    @endphp

    {{-- Layout-Zeile: CheckIn-Status | Sidebar (Losung + Erkrankungen) --}}
    @if($hasCheckin || $hasSidebar)
    <div class="row g-4 mb-4">

        {{-- CheckIn-Status --}}
        @if($hasCheckin)
        <div class="{{ $checkinCol }}">
            @include('dashboard.components.checkin-status')
        </div>
        @endif

        {{-- Sidebar: Losung + Erkrankungen --}}
        @if($hasSidebar)
        <div class="{{ $sidebarCol }}">

            {{-- Wenn kein CheckIn: Losung & Erkrankungen nebeneinander --}}
            @if(!$hasCheckin && $hasLosung && $hasDiseases)
            <div class="row g-3">
                <div class="{{ $losungCol }}">
                    @include('include.losung')
                </div>
                <div class="{{ $diseasesCol }}">
                    @include('dashboard.components.diseases')
                </div>
            </div>

            {{-- Wenn CheckIn vorhanden: Losung & Erkrankungen gestapelt in Sidebar --}}
            @else
                @if($hasLosung)
                    <div class="{{ $hasDiseases ? 'mb-4' : '' }}">
                        @include('include.losung')
                    </div>
                @endif
                @if($hasDiseases)
                    @include('dashboard.components.diseases')
                @endif
            @endif

        </div>
        @endif

    </div>
    @endif

    <!-- Offene Rückmeldungen -->
    <div class="row">
        @include('dashboard.components.pending-feedback')
    </div>

    <!-- Ungelesene Chat-Nachrichten -->
    <div class="row">
        @include('dashboard.components.unread-messages')
    </div>

    <!-- Rückmeldestatus für Lehrkräfte/Autoren -->
    <div class="row">
        @include('dashboard.components.feedback-stats')
    </div>

    <div class="row">
        <!-- Neueste Nachrichten -->
        <div class="col-lg-6 mb-4">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden h-100">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 border-b border-blue-800">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="far fa-newspaper"></i>
                        Neueste Nachrichten
                    </h5>
                </div>
                <div class="p-4">
                    @if($nachrichten && count($nachrichten) > 0)
                        <div class="space-y-3">
                            @foreach($nachrichten as $nachricht)
                                <a href="{{ url('nachrichten#'.$nachricht->id) }}" class="block p-3 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200 text-decoration-none">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="font-bold text-gray-800 mb-1">{{ $nachricht->header }}</h6>
                                        @if($nachricht->rueckmeldung)
                                            <span class="badge badge-info badge-sm">
                                                <i class="fas fa-comment-dots"></i>
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {!! strip_tags($nachricht->news) !!}
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-gray-500">
                                            <i class="far fa-clock"></i> {{ $nachricht->created_at->diffForHumans() }}
                                        </small>
                                        <span class="text-blue-600 text-sm font-semibold">
                                            Weiterlesen <i class="fas fa-arrow-right"></i>
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="text-center mt-4">
                            <a href="{{ url('/nachrichten') }}" class="btn btn-outline-primary">
                                <i class="fas fa-list"></i> Alle Nachrichten anzeigen
                            </a>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="far fa-newspaper text-gray-300" style="font-size: 3rem;"></i>
                            <p class="text-gray-500 mt-3">Keine neuen Nachrichten vorhanden</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Nächste Termine -->
        <div class="col-lg-6 mb-4">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden h-100">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3 border-b border-green-800">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="far fa-calendar-alt"></i>
                        Nächste Termine
                    </h5>
                </div>
                <div class="p-4">
                    @if($termine && count($termine) > 0)
                        <div class="space-y-3">
                            @foreach($termine as $termin)
                                <div class="p-3 border-l-4 border-green-500 bg-gray-50 rounded">
                                    <div class="d-flex gap-3">
                                        <div class="text-center" style="min-width: 60px;">
                                            <div class="bg-green-600 text-white rounded-t px-2 py-1">
                                                <small class="font-bold">{{ $termin->start->format('M') }}</small>
                                            </div>
                                            <div class="bg-white border border-gray-200 rounded-b px-2 py-1">
                                                <span class="text-2xl font-bold text-gray-800">{{ $termin->start->format('d') }}</span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="font-bold text-gray-800 mb-0">{{ $termin->terminname }}</h6>
                                                <div class="d-flex gap-1">
                                                    <a href="{{$termin->link(auth()->user()->calendar_prefix)->ics()}}"
                                                       class="btn btn-sm btn-outline-secondary p-1"
                                                       title="ICS-Download">
                                                        <img src="{{asset('img/ics-icon.png')}}" style="width: 14px; height: 14px;" alt="ICS">
                                                    </a>
                                                    <a href="{{$termin->link(auth()->user()->calendar_prefix)->google()}}"
                                                       class="btn btn-sm btn-outline-secondary p-1"
                                                       target="_blank"
                                                       title="Google Calendar">
                                                        <img src="{{asset('img/icon-google-cal.png')}}" style="width: 14px; height: 14px;" alt="Google">
                                                    </a>
                                                </div>
                                            </div>
                                            @php
                                                $isMultiDay = $termin->ende && $termin->start->format('Y-m-d') != $termin->ende->format('Y-m-d');
                                                $daysDiff = $isMultiDay ? $termin->start->diffInDays($termin->ende) + 1 : 0;
                                            @endphp
                                            @if($isMultiDay)
                                                <p class="text-sm text-gray-600 mb-0">
                                                    <i class="fas fa-calendar-week"></i>
                                                    {{ $termin->start->locale('de')->isoFormat('D. MMM') }}
                                                    @if(!$termin->fullDay)
                                                        {{ $termin->start->format('H:i') }}
                                                    @endif
                                                    -
                                                    {{ $termin->ende->locale('de')->isoFormat('D. MMM') }}
                                                    @if(!$termin->fullDay)
                                                        {{ $termin->ende->format('H:i') }}
                                                    @endif
                                                    <span class="badge badge-info badge-sm ml-1">{{ floor($daysDiff) }} Tage</span>
                                                </p>
                                            @elseif(!$termin->fullDay)
                                                <p class="text-sm text-gray-600 mb-0">
                                                    <i class="far fa-clock"></i> {{ $termin->start->format('H:i') }} Uhr
                                                    @if($termin->ende && $termin->start->format('Y-m-d') == $termin->ende->format('Y-m-d'))
                                                        - {{ $termin->ende->format('H:i') }} Uhr
                                                    @endif
                                                </p>
                                            @else
                                                <p class="text-sm text-gray-600 mb-0">
                                                    <i class="far fa-calendar"></i> Ganztägig
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-4">
                            <a href="{{ url('/termine') }}" class="btn btn-outline-success">
                                <i class="far fa-calendar"></i> Alle Termine anzeigen
                            </a>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="far fa-calendar-alt text-gray-300" style="font-size: 3rem;"></i>
                            <p class="text-gray-500 mt-3">Keine anstehenden Termine</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Schnellzugriff -->
    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded-lg shadow-lg p-4">
                <h5 class="text-lg font-bold text-gray-800 mb-3">
                    <i class="fas fa-bolt"></i> Schnellzugriff
                </h5>
                <div class="row">
                    @can('view schickzeiten')
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ url('/schickzeiten') }}" class="d-block p-3 text-center border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200 text-decoration-none">
                                <i class="fas fa-clock text-blue-600" style="font-size: 2rem;"></i>
                                <p class="text-gray-800 font-semibold mt-2 mb-0">Hort</p>
                            </a>
                        </div>
                    @endcan

                    @can('view krankmeldung')
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ url('/krankmeldung') }}" class="d-block p-3 text-center border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200 text-decoration-none">
                                <i class="fas fa-notes-medical text-red-600" style="font-size: 2rem;"></i>
                                <p class="text-gray-800 font-semibold mt-2 mb-0">Krankmeldung</p>
                            </a>
                        </div>
                    @endcan

                        @can('view vertretungsplan')
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="{{ url('/vertretungsplan') }}" class="d-block p-3 text-center border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200 text-decoration-none">
                                    <i class="fas fa-chalkboard-teacher text-yellow-600" style="font-size: 2rem;"></i>
                                    <p class="text-gray-800 font-semibold mt-2 mb-0">Vertretungsplan</p>
                                </a>
                            </div>
                        @endcan


                    @can('view child')
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ url('/care/children') }}" class="d-block p-3 text-center border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200 text-decoration-none">
                                <i class="fas fa-child text-purple-600" style="font-size: 2rem;"></i>
                                <p class="text-gray-800 font-semibold mt-2 mb-0">Kinder</p>
                            </a>
                        </div>
                    @endcan

                    @can('view listen')
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ url('/listen') }}" class="d-block p-3 text-center border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all duration-200 text-decoration-none">
                                <i class="fas fa-list text-green-600" style="font-size: 2rem;"></i>
                                <p class="text-gray-800 font-semibold mt-2 mb-0">Listen</p>
                            </a>
                        </div>
                    @endcan
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
</style>
@endsection

