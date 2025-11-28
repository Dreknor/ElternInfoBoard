@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3 border-b border-purple-800">
            <h4 class="text-xl font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-users"></i>
                Arbeitsgemeinschaften
            </h4>
        </div>

        <div class="p-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($arbeitsgemeinschaften as $ag)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-md hover:shadow-lg hover:border-purple-500 transition-all duration-200 overflow-hidden flex flex-col">
                        <div class="p-4 flex-1">
                            <h5 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                                <i class="fas fa-graduation-cap text-purple-600"></i>
                                {{ $ag->name }}
                            </h5>

                            <div class="space-y-2 text-sm">
                                <div class="flex items-start gap-2 text-gray-700">
                                    <i class="fas fa-align-left text-purple-600 mt-1 w-4"></i>
                                    <span>{{$ag->description}}</span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-calendar-day text-purple-600 w-4"></i>
                                    <span>{{ $weekdays[$ag->weekday] }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-calendar-alt text-purple-600 w-4"></i>
                                    <span>{{ $ag->start_date->format('d.m.Y') }} - {{ $ag->end_date->format('d.m.Y') }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-clock text-purple-600 w-4"></i>
                                    <span>{{ $ag->start_time->format('H:i') }} - {{ $ag->end_time->format('H:i') }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-user text-purple-600 w-4"></i>
                                    <span>Leitung: {{ $ag->manager->name }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-users text-purple-600 w-4"></i>
                                    <span>{{ $ag->participants->count() }}/{{ $ag->max_participants }} Teilnehmer</span>
                                </div>
                            </div>

                            @php
                                $angemeldeteKinder = $ag->participants->filter(function($participant) {
                                    return auth()->user()->children()->contains($participant->id);
                                });
                            @endphp

                            @if($angemeldeteKinder->isNotEmpty())
                                <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm font-semibold text-green-800 mb-2">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Angemeldete Kinder:
                                    </p>
                                    <ul class="space-y-1">
                                        @foreach($angemeldeteKinder as $kind)
                                            <li class="flex items-center gap-2 text-sm text-green-700">
                                                <i class="fas fa-check text-green-600"></i>
                                                {{ $kind->first_name }} {{ $kind->last_name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-2 text-xs text-green-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Abmeldungen können nur über die Schule vorgenommen werden.
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="bg-gray-50 border-t border-gray-200 p-3">
                            @if($ag->participants->count() >= $ag->max_participants)
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 text-red-700 font-semibold rounded-lg w-full justify-center">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Ausgebucht</span>
                                </div>
                            @elseif($availableChildrenByAg[$ag->id]->isNotEmpty())
                                <form action="{{ route('arbeitsgemeinschaften.anmelden', $ag) }}" method="POST">
                                    @csrf
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <select name="child_id"
                                                class="flex-1 px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 outline-none text-sm">
                                            @foreach($availableChildrenByAg[$ag->id] as $child)
                                                <option value="{{ $child->id }}">
                                                    {{ $child->last_name }}, {{ $child->first_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors duration-200 whitespace-nowrap">
                                            <i class="fas fa-user-plus"></i>
                                            <span>Anmelden</span>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-600 font-semibold rounded-lg w-full justify-center">
                                    <i class="fas fa-ban"></i>
                                    <span>Keine Anmeldung möglich</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="flex items-start gap-3 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                            <p class="text-blue-800 text-sm mb-0">Keine Arbeitsgemeinschaften gefunden.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
