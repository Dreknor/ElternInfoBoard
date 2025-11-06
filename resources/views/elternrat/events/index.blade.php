@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-calendar-alt text-orange-600"></i>
                    Terminplaner
                </h1>
                <p class="text-sm text-gray-600 mt-1">Sitzungen und Termine des Elternrats</p>
            </div>
            <a href="{{route('elternrat.events.create')}}"
               class="px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white font-semibold rounded-lg hover:from-orange-600 hover:to-red-600 transition-all flex items-center gap-2 shadow-lg">
                <i class="fas fa-plus"></i>
                <span>Neuer Termin</span>
            </a>
        </div>

        <!-- Tabs for Upcoming/Past -->
        <div x-data="{ tab: 'upcoming' }" class="space-y-6">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex flex-wrap">
                        <button @click="tab = 'upcoming'"
                                :class="tab === 'upcoming' ? 'border-orange-500 text-orange-600 bg-orange-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                            <i class="fas fa-calendar-day mr-2"></i>
                            Bevorstehend <span class="ml-1 px-2 py-0.5 bg-orange-100 text-orange-800 rounded-full text-xs">({{$upcomingEvents->count()}})</span>
                        </button>
                        <button @click="tab = 'past'"
                                :class="tab === 'past' ? 'border-orange-500 text-orange-600 bg-orange-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                            <i class="fas fa-history mr-2"></i>
                            Vergangene <span class="ml-1 px-2 py-0.5 bg-gray-100 text-gray-800 rounded-full text-xs">({{$pastEvents->count()}})</span>
                        </button>
                    </nav>
                </div>

                <!-- Upcoming Events -->
                <div x-show="tab === 'upcoming'" class="p-6">
                    <div class="space-y-4">
                        @forelse($upcomingEvents as $event)
                            <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-lg p-6 border border-orange-200 hover:shadow-lg transition-shadow">
                                <div class="flex flex-col lg:flex-row items-start justify-between gap-4">
                                    <div class="flex items-start gap-4 flex-1 w-full">
                                        <div class="bg-white rounded-lg p-4 text-center shadow-md flex-shrink-0">
                                            <div class="text-3xl font-bold text-orange-600">{{$event->start_time->format('d')}}</div>
                                            <div class="text-xs text-gray-600 uppercase">{{$event->start_time->format('M')}}</div>
                                            <div class="text-xs text-gray-500">{{$event->start_time->format('Y')}}</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-xl font-bold text-gray-800 mb-2">{{$event->title}}</h3>
                                            @if($event->description)
                                                <p class="text-sm text-gray-700 mb-3 line-clamp-2">{{$event->description}}</p>
                                            @endif
                                            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-3">
                                                <span class="flex items-center gap-1.5">
                                                    <i class="fas fa-clock text-orange-600"></i>
                                                    <strong>{{$event->start_time->format('H:i')}}</strong> - {{$event->end_time->format('H:i')}} Uhr
                                                </span>
                                                @if($event->location)
                                                    <span class="flex items-center gap-1.5">
                                                        <i class="fas fa-map-marker-alt text-orange-600"></i>
                                                        {{$event->location}}
                                                    </span>
                                                @endif
                                                <span class="flex items-center gap-1.5">
                                                    <i class="fas fa-user text-orange-600"></i>
                                                    {{$event->creator->name}}
                                                </span>
                                            </div>

                                            <!-- Attendance -->
                                            <div class="flex flex-wrap gap-2">
                                                @php
                                                    $userAttendance = $event->attendees->where('user_id', auth()->id())->first();
                                                @endphp
                                                <form action="{{route('elternrat.events.attendance', $event)}}" method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="status" value="accepted">
                                                    <button type="submit" class="px-3 py-2 rounded-lg text-sm font-semibold transition-all
                                                        @if($userAttendance && $userAttendance->status === 'accepted')
                                                            bg-green-600 text-white shadow-md
                                                        @else
                                                            bg-green-100 text-green-700 hover:bg-green-200
                                                        @endif">
                                                        <i class="fas fa-check"></i> Zusage ({{$event->acceptedCount()}})
                                                    </button>
                                                </form>
                                                <form action="{{route('elternrat.events.attendance', $event)}}" method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="status" value="maybe">
                                                    <button type="submit" class="px-3 py-2 rounded-lg text-sm font-semibold transition-all
                                                        @if($userAttendance && $userAttendance->status === 'maybe')
                                                            bg-amber-600 text-white shadow-md
                                                        @else
                                                            bg-amber-100 text-amber-700 hover:bg-amber-200
                                                        @endif">
                                                        <i class="fas fa-question"></i> Vielleicht ({{$event->maybeCount()}})
                                                    </button>
                                                </form>
                                                <form action="{{route('elternrat.events.attendance', $event)}}" method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="status" value="declined">
                                                    <button type="submit" class="px-3 py-2 rounded-lg text-sm font-semibold transition-all
                                                        @if($userAttendance && $userAttendance->status === 'declined')
                                                            bg-red-600 text-white shadow-md
                                                        @else
                                                            bg-red-100 text-red-700 hover:bg-red-200
                                                        @endif">
                                                        <i class="fas fa-times"></i> Absage ({{$event->declinedCount()}})
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @if(auth()->user()->can('delete elternrat file') || $event->created_by === auth()->id())
                                        <form action="{{route('elternrat.events.destroy', $event)}}" method="POST" class="inline-block flex-shrink-0"
                                              onsubmit="return confirm('Termin wirklich löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-md">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-16 text-gray-500">
                                <i class="fas fa-calendar-times text-6xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-semibold mb-2">Keine bevorstehenden Termine</p>
                                <p class="text-sm">Erstellen Sie einen neuen Termin, um loszulegen</p>
                                <a href="{{route('elternrat.events.create')}}"
                                   class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg hover:from-orange-600 hover:to-red-600">
                                    <i class="fas fa-plus"></i>
                                    <span>Termin erstellen</span>
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Past Events -->
                <div x-show="tab === 'past'" class="p-6" style="display: none;">
                    <div class="space-y-4">
                        @forelse($pastEvents as $event)
                            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                                <div class="flex items-start gap-4 opacity-75">
                                    <div class="bg-white rounded-lg p-4 text-center shadow-sm flex-shrink-0">
                                        <div class="text-3xl font-bold text-gray-600">{{$event->start_time->format('d')}}</div>
                                        <div class="text-xs text-gray-500 uppercase">{{$event->start_time->format('M')}}</div>
                                        <div class="text-xs text-gray-400">{{$event->start_time->format('Y')}}</div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold text-gray-700 mb-2">{{$event->title}}</h3>
                                        @if($event->description)
                                            <p class="text-sm text-gray-600 mb-3">{{$event->description}}</p>
                                        @endif
                                        <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                            <span class="flex items-center gap-1.5">
                                                <i class="fas fa-clock"></i>
                                                {{$event->start_time->format('H:i')}} - {{$event->end_time->format('H:i')}} Uhr
                                            </span>
                                            @if($event->location)
                                                <span class="flex items-center gap-1.5">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    {{$event->location}}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex gap-4 text-xs text-gray-500 mt-3">
                                            <span><i class="fas fa-check text-green-600"></i> {{$event->acceptedCount()}} Zusagen</span>
                                            <span><i class="fas fa-question text-amber-600"></i> {{$event->maybeCount()}} Vielleicht</span>
                                            <span><i class="fas fa-times text-red-600"></i> {{$event->declinedCount()}} Absagen</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-16 text-gray-500">
                                <i class="fas fa-calendar-times text-6xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-semibold">Keine vergangenen Termine</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

