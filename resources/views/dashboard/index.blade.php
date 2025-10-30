@extends('layouts.app')
@section('title') - Dashboard @endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Willkommensbereich -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg shadow-lg p-6 mb-4 text-white">
                <h2 class="text-2xl font-bold mb-2">Willkommen, {{ auth()->user()->name }}!</h2>
                <p class="text-blue-100">{{ $datum->format('l, d. F Y') }}</p>
            </div>

            <!-- Losung des Tages -->
            @if($losung)
                @include('include.losung')
            @endif
        </div>
    </div>

    <!-- CheckIn-Status für Care-Kinder -->
    <div class="row">
        @include('dashboard.components.checkin-status')
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
                                            @if(!$termin->fullDay)
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

