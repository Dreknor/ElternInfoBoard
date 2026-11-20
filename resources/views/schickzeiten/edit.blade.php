@extends('layouts.app')
@section('title') - Schickzeit bearbeiten @endsection

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Zurück Button -->
        <div class="mb-6">
            <a href="{{ url()->previous()}}"
               class="ea-btn ea-btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Zurück
            </a>
        </div>

        <!-- Hauptcard -->
        <div class="ea-card overflow-hidden max-w-3xl mx-auto" x-data="{ type: '{{ $schickzeit?->type ?? 'genau' }}' }">
            <!-- Header -->
            <div class="ea-hero !rounded-none !shadow-none">
                <div class="flex items-center gap-4">
                    <div class="ea-hero-icon"><i class="fas fa-clock" aria-hidden="true"></i></div>
                    <div class="min-w-0">
                        <h1 class="ea-hero-title">Schickzeit bearbeiten</h1>
                        <p class="ea-hero-sub">{{$day}} für {{$child->first_name}} {{$child->last_name}}</p>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="p-6 ea-subtle border-b ea-border">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle mt-1" style="color: var(--color-primary)"></i>
                    <div class="text-sm ea-text">
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
                        <label class="ea-label">
                            <i class="fas fa-list" style="color: var(--color-primary)"></i> Typ der Schickzeit
                        </label>
                        <select name="type" id="type" x-model="type"
                                class="ea-input">
                            <option value="genau">Genau um ... Uhr</option>
                            @if($vorgaben->schicken_erlaube_zeitraum)
                                <option value="ab">Ab ... bis ... Uhr</option>
                            @endif
                        </select>
                    </div>

                    <!-- Zeit Eingabe (genau) -->
                    <div class="mb-6" x-show="type === 'genau'">
                        <label class="ea-label">
                            <i class="fas fa-clock text-green-600 dark:text-green-400"></i> Zeit
                        </label>
                        <input name="time" id="time" type="time"
                               class="ea-input"
                               min="{{$vorgaben->schicken_ab}}"
                               max="{{$vorgaben->schicken_bis}}"
                               value="{{$schickzeit?->time?->format('H:i')}}">
                        <p class="ea-hint">
                            <i class="fas fa-exclamation-circle"></i> Erlaubter Zeitraum: {{$vorgaben->schicken_ab}} - {{$vorgaben->schicken_bis}}
                        </p>
                    </div>

                    <!-- Zeitraum Eingabe (ab/bis) -->
                    <div class="mb-6" x-show="type === 'ab'" style="display: none;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="ea-label">
                                    <i class="fas fa-clock" style="color: var(--color-primary)"></i> Ab ... Uhr
                                </label>
                                <input name="time_ab" type="time"
                                       class="ea-input"
                                       min="{{$vorgaben->schicken_ab}}"
                                       max="{{$vorgaben->schicken_bis}}"
                                       value="{{$schickzeit?->time_ab?->format('H:i')}}">
                            </div>
                            <div>
                                <label class="ea-label">
                                    <i class="fas fa-clock text-amber-600 dark:text-amber-400"></i> Spätestens (optional)
                                </label>
                                <input name="time_spaet" type="time"
                                       class="ea-input"
                                       min="{{$vorgaben->schicken_ab}}"
                                       max="{{$vorgaben->schicken_bis}}"
                                       value="{{$schickzeit?->time_spaet?->format('H:i')}}">
                            </div>
                        </div>
                        <p class="ea-hint mt-2">
                            <i class="fas fa-exclamation-circle"></i> Erlaubter Zeitraum: {{$vorgaben->schicken_ab}} - {{$vorgaben->schicken_bis}}
                        </p>
                    </div>

                    <!-- Hidden input for update_daily_times -->
                    <input type="hidden" name="update_daily_times" id="update_daily_times" value="0">

                    <!-- Submit Button -->
                    <div class="flex gap-3">
                        <button type="button" onclick="handleFormSubmit(event)"
                                class="ea-btn ea-btn-success ea-btn-lg flex-1">
                            <i class="fas fa-save"></i>
                            Speichern
                        </button>
                        <a href="{{ url()->previous()}}"
                           class="ea-btn ea-btn-secondary ea-btn-lg">
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
                                    <input type="checkbox" id="updateDailyTimes" class="ea-check">
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
