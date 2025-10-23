@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4>Arbeitsgemeinschaft bearbeiten</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('verwaltung.arbeitsgemeinschaften.update', $arbeitsgemeinschaft) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-4">
                        <!-- Linke Spalte -->
                        <div class="col-md-6">
                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold">Name der AG</label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $arbeitsgemeinschaft->name) }}"
                                       required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Beschreibung -->
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">Beschreibung</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description"
                                          name="description"
                                          rows="4"
                                          required>{{ old('description', $arbeitsgemeinschaft->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Maximale Teilnehmerzahl -->
                            <div class="mb-3">
                                <label for="max_participants" class="form-label fw-bold">Maximale Teilnehmerzahl</label>
                                <input type="number"
                                       class="form-control @error('max_participants') is-invalid @enderror"
                                       id="max_participants"
                                       name="max_participants"
                                       value="{{ old('max_participants', $arbeitsgemeinschaft->max_participants) }}"
                                       min="{{ $arbeitsgemeinschaft->participants()->count() }}"
                                       required>
                                @error('max_participants')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Aktuelle Teilnehmerzahl: {{ $arbeitsgemeinschaft->participants()->count() }}
                                </small>
                            </div>

                            <!-- Verantwortlicher -->
                            <div class="mb-3">
                                <label for="manager_id" class="form-label fw-bold">Verantwortlicher</label>
                                <select class="custom-select @error('manager_id') is-invalid @enderror"
                                        id="manager_id"
                                        name="manager_id"
                                        required>
                                    <option value="">Bitte wählen...</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}"
                                            {{ old('manager_id', $arbeitsgemeinschaft->manager_id) == $manager->id ? 'selected' : '' }}>
                                            {{ $manager->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('manager_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Rechte Spalte -->
                        <div class="col-md-6">
                            <!-- Wochentag -->
                            <div class="mb-3">
                                <label for="weekday" class="form-label fw-bold">Wochentag</label>
                                <select class="custom-select @error('weekday') is-invalid @enderror"
                                        id="weekday"
                                        name="weekday"
                                        required>
                                    <option value="">Bitte wählen...</option>
                                    @foreach($weekdays as $key => $day)
                                        <option value="{{ $key }}"
                                            {{ old('weekday', $arbeitsgemeinschaft->weekday) == $key ? 'selected' : '' }}>
                                            {{ $day }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('weekday')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Zeitraum -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="start_time" class="form-label fw-bold">Startzeit</label>
                                    <input type="time"
                                           class="form-control @error('start_time') is-invalid @enderror"
                                           id="start_time"
                                           name="start_time"
                                           value="{{ old('start_time', $arbeitsgemeinschaft->start_time->format('H:i')) }}"
                                           required>
                                    @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-6">
                                    <label for="end_time" class="form-label fw-bold">Endzeit</label>
                                    <input type="time"
                                           class="form-control @error('end_time') is-invalid @enderror"
                                           id="end_time"
                                           name="end_time"
                                           value="{{ old('end_time', $arbeitsgemeinschaft->end_time->format('H:i')) }}"
                                           required>
                                    @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Datum -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="start_date" class="form-label fw-bold">Startdatum</label>
                                    <input type="date"
                                           class="form-control @error('start_date') is-invalid @enderror"
                                           id="start_date"
                                           name="start_date"
                                           value="{{ old('start_date', $arbeitsgemeinschaft->start_date->format('Y-m-d')) }}"
                                           required>
                                    @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-6">
                                    <label for="end_date" class="form-label fw-bold">Enddatum</label>
                                    <input type="date"
                                           class="form-control @error('end_date') is-invalid @enderror"
                                           id="end_date"
                                           name="end_date"
                                           value="{{ old('end_date', $arbeitsgemeinschaft->end_date->format('Y-m-d')) }}"
                                           required>
                                    @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Gruppen -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Zugangsberechtigte Gruppen</label>
                                <div class="border rounded p-3 @error('groups') border-danger @enderror">
                                    <div class="row g-2">
                                        @foreach($groups as $group)
                                            <div class="col-md-6">

                                                    <input class=""
                                                           type="checkbox"
                                                           name="groups[]"
                                                           value="{{ $group->id }}"
                                                           id="group_{{ $group->id }}"
                                                        {{ in_array($group->id, old('groups', $arbeitsgemeinschaft->groups->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="group_{{ $group->id }}">
                                                        {{ $group->name }}
                                                    </label>

                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @error('groups')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <a href="{{ route('verwaltung.arbeitsgemeinschaften.index') }}" class="btn btn-secondary w-100">
                                <i class="bi bi-x-circle"></i>
                                <span class="button-text">Abbrechen</span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i>
                                <span class="button-text">Änderungen speichern</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* Optimierungen für mobile Ansicht */
            @media (max-width: 768px) {
                .card-body {
                    padding: 1rem;
                }

                /* Abstände zwischen den Formulargruppen */
                .mb-3 {
                    margin-bottom: 1.5rem !important;
                }

                /* Gruppen-Checkboxen */
                .form-check {
                    padding: 0.5rem;
                    border-bottom: 1px solid rgba(0,0,0,.1);
                }

                .form-check:last-child {
                    border-bottom: none;
                }

                /* Button-Text auf mobil */
                @media (max-width: 768px) {
                    .button-text {
                        display: none;
                    }

                    .bi {
                        font-size: 1.2rem;
                    }
                }
            }

            /* Hilfstext für Validierung */
            .form-text {
                font-size: 0.875rem;
            }

            /* Hover-Effekt für Checkboxen */
            .form-check:hover {
                background-color: rgba(0,0,0,.03);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Validierung der Zeiten
                const startTimeInput = document.getElementById('start_time');
                const endTimeInput = document.getElementById('end_time');

                function validateTimes() {
                    if (startTimeInput.value && endTimeInput.value) {
                        if (startTimeInput.value >= endTimeInput.value) {
                            endTimeInput.setCustomValidity('Die Endzeit muss nach der Startzeit liegen');
                        } else {
                            endTimeInput.setCustomValidity('');
                        }
                    }
                }

                startTimeInput.addEventListener('change', validateTimes);
                endTimeInput.addEventListener('change', validateTimes);

                // Validierung der Daten
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');

                function validateDates() {
                    if (startDateInput.value && endDateInput.value) {
                        if (startDateInput.value >= endDateInput.value) {
                            endDateInput.setCustomValidity('Das Enddatum muss nach dem Startdatum liegen');
                        } else {
                            endDateInput.setCustomValidity('');
                        }
                    }
                }

                startDateInput.addEventListener('change', validateDates);
                endDateInput.addEventListener('change', validateDates);
            });
        </script>
    @endpush
@endsection
