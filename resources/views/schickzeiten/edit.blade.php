@extends('layouts.app')
@section('title') - Schickzeit bearbeiten @endsection

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Zurück Button -->
        <div class="mb-6">
            <a href="{{ url()->previous()}}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left"></i>
                Zurück
            </a>
        </div>

        <!-- Hauptcard -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200 max-w-3xl mx-auto" x-data="{ type: '{{ $schickzeit?->type ?? 'genau' }}' }">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                <h1 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-clock"></i>
                    Schickzeit bearbeiten
                </h1>
                <p class="text-blue-100 text-sm mt-1">{{$day}} für {{$child->first_name}} {{$child->last_name}}</p>
            </div>

            <!-- Info Section -->
            <div class="p-6 bg-blue-50 border-b border-blue-200">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                    <div class="text-sm text-blue-800">
                        @include('schickzeiten.infos')
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="p-6">
                <form method="post" action="{{url('schickzeiten/'.$child->id.'/'.$day)}}">
                    @csrf

                    <!-- Typ Auswahl -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-list text-blue-600"></i> Typ der Schickzeit
                        </label>
                        <select name="type" id="type" x-model="type"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                            <option value="genau">Genau um ... Uhr</option>
                            <option value="ab">Ab ... bis ... Uhr</option>
                        </select>
                    </div>

                    <!-- Zeit Eingabe (genau) -->
                    <div class="mb-6" x-show="type === 'genau'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-clock text-green-600"></i> Zeit
                        </label>
                        <input name="time" id="time" type="time"
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                               min="{{$vorgaben->schicken_ab}}"
                               max="{{$vorgaben->schicken_bis}}"
                               value="{{$schickzeit?->time?->format('H:i')}}">
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-exclamation-circle"></i> Erlaubter Zeitraum: {{$vorgaben->schicken_ab}} - {{$vorgaben->schicken_bis}}
                        </p>
                    </div>

                    <!-- Zeitraum Eingabe (ab/bis) -->
                    <div class="mb-6" x-show="type === 'ab'" style="display: none;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock text-blue-600"></i> Ab ... Uhr
                                </label>
                                <input name="time_ab" type="time"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                       min="{{$vorgaben->schicken_ab}}"
                                       max="{{$vorgaben->schicken_bis}}"
                                       value="{{$schickzeit?->time_ab?->format('H:i')}}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock text-amber-600"></i> Spätestens (optional)
                                </label>
                                <input name="time_spaet" type="time"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                       min="{{$vorgaben->schicken_ab}}"
                                       max="{{$vorgaben->schicken_bis}}"
                                       value="{{$schickzeit?->time_spaet?->format('H:i')}}">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            <i class="fas fa-exclamation-circle"></i> Erlaubter Zeitraum: {{$vorgaben->schicken_ab}} - {{$vorgaben->schicken_bis}}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Speichern
                        </button>
                        <a href="{{ url()->previous()}}"
                           class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-times"></i>
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

