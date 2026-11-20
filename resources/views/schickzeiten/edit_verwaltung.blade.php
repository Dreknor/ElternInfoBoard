@extends('layouts.app')
@section('title') - Schickzeit bearbeiten (Verwaltung) @endsection

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
                    <div class="ea-hero-icon"><i class="fas fa-user-edit" aria-hidden="true"></i></div>
                    <div class="min-w-0">
                        <h1 class="ea-hero-title">Schickzeit bearbeiten (Verwaltung)</h1>
                        <p class="ea-hero-sub">{{$day}} für {{$child}}</p>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="p-6 ea-subtle border-b ea-border">
                @include('schickzeiten.infos')
                <div class="mt-3 ea-alert ea-alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <p class="text-sm mb-0">
                        Änderungen werden berücksichtigt ab: <strong>{{\Carbon\Carbon::now()->next('monday')->format('d.m.Y')}}</strong>
                    </p>
                </div>
            </div>

            <!-- Error Display -->
            @error('time_spaet')
                <div class="mx-6 mt-4 ea-alert ea-alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <p class="text-sm mb-0">{{ $message }}</p>
                </div>
            @enderror

            <!-- Form -->
            <div class="p-6">
                <form method="post" action="{{url('verwaltung/schickzeiten/'.$parent)}}">
                    @csrf

                    <!-- Readonly Felder -->
                    <div class="mb-4">
                        <label class="ea-label">
                            <i class="fas fa-child" style="color: var(--color-primary)"></i> Name des Kindes
                        </label>
                        <input name="child"
                               value="{{$child}}"
                               readonly
                               class="ea-input ea-subtle cursor-not-allowed">
                    </div>

                    <div class="mb-6">
                        <label class="ea-label">
                            <i class="fas fa-calendar-day" style="color: var(--color-primary)"></i> Wochentag
                        </label>
                        <input name="weekday"
                               value="{{$day}}"
                               readonly
                               class="ea-input ea-subtle cursor-not-allowed">
                    </div>

                    <!-- Typ Auswahl -->
                    <div class="mb-6">
                        <label class="ea-label">
                            <i class="fas fa-list" style="color: var(--color-primary)"></i> Typ der Schickzeit
                        </label>
                        <select name="type" id="type" x-model="type"
                                class="ea-input">
                            <option value="genau">Genau um ... Uhr</option>
                            @if($vorgaben->schicken_erlaube_zeitraum)
                                <option value="ab" @if($schickzeit and $schickzeit->type == "ab") selected @endif>Ab ... bis ... Uhr</option>
                            @endif
                        </select>
                    </div>

                    <!-- Zeit Eingabe -->
                    <div class="mb-6">
                        <label class="ea-label">
                            <i class="fas fa-clock" style="color: var(--color-primary)"></i> Zeit
                        </label>
                        <input name="time" id="time" type="time"
                               class="ea-input"
                               min="{{config('schicken.ab')}}"
                               max="{{config('schicken.max')}}"
                               required
                               value="{{$schickzeit?->time?->format('H:i')}}">
                        <p class="ea-hint">
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
                        <label class="ea-label">
                            <i class="fas fa-hourglass-end text-green-600 dark:text-green-400"></i> Spätestens (optional)
                        </label>
                        <input name="time_spaet" type="time"
                               class="ea-input"
                               min="14:00:00"
                               max="16:30:00"
                               id="spaet"
                               value="{{$schickzeit_spaet?->time?->format('H:i')}}">
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-3">
                        <button type="submit"
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

