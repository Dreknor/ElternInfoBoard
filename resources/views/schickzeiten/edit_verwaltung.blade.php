@extends('layouts.app')
@section('title') - Schickzeit bearbeiten (Verwaltung) @endsection

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
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                <h1 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-user-edit"></i>
                    Schickzeit bearbeiten (Verwaltung)
                </h1>
                <p class="text-purple-100 text-sm mt-1">{{$day}} für {{$child}}</p>
            </div>

            <!-- Info Section -->
            <div class="p-6 bg-purple-50 border-b border-purple-200">
                @include('schickzeiten.infos')
                <div class="mt-3 flex items-start gap-3 p-3 bg-amber-50 border-l-4 border-amber-500 rounded">
                    <i class="fas fa-info-circle text-amber-600 mt-1"></i>
                    <p class="text-amber-800 text-sm mb-0">
                        Änderungen werden berücksichtigt ab: <strong>{{\Carbon\Carbon::now()->next('monday')->format('d.m.Y')}}</strong>
                    </p>
                </div>
            </div>

            <!-- Error Display -->
            @error('time_spaet')
                <div class="mx-6 mt-4 flex items-start gap-3 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                    <i class="fas fa-exclamation-circle text-red-600 mt-1"></i>
                    <p class="text-red-800 text-sm mb-0">{{ $message }}</p>
                </div>
            @enderror

            <!-- Form -->
            <div class="p-6">
                <form method="post" action="{{url('verwaltung/schickzeiten/'.$parent)}}">
                    @csrf

                    <!-- Readonly Felder -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-child text-blue-600"></i> Name des Kindes
                        </label>
                        <input name="child"
                               value="{{$child}}"
                               readonly
                               class="w-full px-4 py-2 border-2 border-gray-200 bg-gray-50 rounded-lg text-gray-700 cursor-not-allowed">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-day text-blue-600"></i> Wochentag
                        </label>
                        <input name="weekday"
                               value="{{$day}}"
                               readonly
                               class="w-full px-4 py-2 border-2 border-gray-200 bg-gray-50 rounded-lg text-gray-700 cursor-not-allowed">
                    </div>

                    <!-- Typ Auswahl -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-list text-blue-600"></i> Typ der Schickzeit
                        </label>
                        <select name="type" id="type" x-model="type"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                            <option value="genau">Genau um ... Uhr</option>
                            <option value="ab" @if($schickzeit and $schickzeit->type == "ab") selected @endif>Ab ... bis ... Uhr</option>
                        </select>
                    </div>

                    <!-- Zeit Eingabe -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-clock text-blue-600"></i> Zeit
                        </label>
                        <input name="time" id="time" type="time"
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                               min="{{config('schicken.ab')}}"
                               max="{{config('schicken.max')}}"
                               required
                               value="{{$schickzeit?->time?->format('H:i')}}">
                        <p class="mt-1 text-xs text-gray-500">
                            Erlaubter Zeitraum: {{config('schicken.ab')}} - {{config('schicken.max')}}
                        </p>
                    </div>

                    <!-- Spätestens (conditional) -->
                    <div class="mb-6"
                         x-show="type === 'ab'"
                         @if(!($schickzeit_spaet and $schickzeit_spaet !="") and !($schickzeit and $schickzeit->type == "ab")) style="display: none;" @endif
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-hourglass-end text-green-600"></i> Spätestens (optional)
                        </label>
                        <input name="time_spaet" type="time"
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                               min="14:00:00"
                               max="16:30:00"
                               id="spaet"
                               value="{{$schickzeit_spaet?->time?->format('H:i')}}">
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200">
                            <i class="fas fa-save"></i>
                            Speichern
                        </button>
                        <a href="{{ url()->previous()}}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-times"></i>
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bestätigungsmodals für Schickzeiten --}}
    @include('components.schickzeiten-confirmation-modals')

@endsection

