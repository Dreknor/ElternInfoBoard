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
                <form method="post" action="{{url('schickzeiten/'.$child->id.'/'.$day)}}" id="editSchickzeitForm">
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

                    <!-- Hidden input for update_daily_times -->
                    <input type="hidden" name="update_daily_times" id="update_daily_times" value="0">

                    <!-- Submit Button -->
                    <div class="flex gap-3">
                        <button type="button" onclick="handleFormSubmit(event)"
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

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function handleFormSubmit(event) {
            event.preventDefault();

            try {
                // Prüfe, ob tagesaktuelle Schickzeiten für diesen Wochentag existieren
                const response = await fetch('{{ route('schickzeiten.checkDailyTimes') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        child_id: {{ $child->id }},
                        weekday: {{ $day_number }}
                    })
                });

                const data = await response.json();

                if (data.has_daily_times) {
                    const result = await Swal.fire({
                        title: 'Schickzeit ändern?',
                        html: `
                            <p class="mb-4">Die regelmäßige Schickzeit wird geändert.</p>
                            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-3">
                                <p class="text-sm text-yellow-800 mb-2">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Es existieren ${data.count} tagesaktuelle Schickzeit(en) für diesen Wochentag:
                                </p>
                                <p class="text-xs text-yellow-700">${data.dates.join(', ')}</p>
                            </div>
                            <div class="text-left mt-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="updateDailyTimes" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Auch tagesaktuelle Schickzeiten anpassen</span>
                                </label>
                            </div>
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#16a34a',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Speichern',
                        cancelButtonText: 'Abbrechen'
                    });

                    if (result.isConfirmed) {
                        const updateDailyTimes = document.getElementById('updateDailyTimes')?.checked || false;
                        document.getElementById('update_daily_times').value = updateDailyTimes ? '1' : '0';
                        document.getElementById('editSchickzeitForm').submit();
                    }
                } else {
                    // Keine tagesaktuellen Schickzeiten, direkt absenden
                    document.getElementById('editSchickzeitForm').submit();
                }
            } catch (error) {
                console.error('Fehler:', error);
                Swal.fire({
                    title: 'Fehler',
                    text: 'Es ist ein Fehler aufgetreten.',
                    icon: 'error'
                });
            }
        }
    </script>
@endpush
