@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-accent-from), var(--color-widget-accent-to)); border-color: var(--color-widget-accent-border)">
                <h4 class="text-xl font-bold mb-0 flex items-center gap-2" style="color: var(--color-widget-header-text)">
                    <i class="fas fa-edit"></i>
                    Arbeitsgemeinschaft bearbeiten
                </h4>
            </div>

            <div class="p-6">
                <form action="{{ route('verwaltung.arbeitsgemeinschaften.update', $arbeitsgemeinschaft) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Linke Spalte -->
                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-tag mr-1" style="color: var(--color-widget-accent-from)"></i>
                                    Name der AG
                                </label>
                                <input type="text"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('name') border-red-500 @enderror"
                                       id="name" name="name"
                                       value="{{ old('name', $arbeitsgemeinschaft->name) }}" required
                                       onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                       onblur="this.style.borderColor='#d1d5db'">
                                @error('name')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Beschreibung -->
                            <div>
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-align-left mr-1" style="color: var(--color-widget-accent-from)"></i>
                                    Beschreibung
                                </label>
                                <textarea class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 resize-none @error('description') border-red-500 @enderror"
                                          id="description" name="description" rows="4" required
                                          onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                          onblur="this.style.borderColor='#d1d5db'">{{ old('description', $arbeitsgemeinschaft->description) }}</textarea>
                                @error('description')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Max Teilnehmer -->
                            <div>
                                <label for="max_participants" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-users mr-1" style="color: var(--color-widget-accent-from)"></i>
                                    Maximale Teilnehmerzahl
                                </label>
                                <input type="number"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('max_participants') border-red-500 @enderror"
                                       id="max_participants" name="max_participants"
                                       value="{{ old('max_participants', $arbeitsgemeinschaft->max_participants) }}"
                                       min="{{ $arbeitsgemeinschaft->participants()->count() }}" required
                                       onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                       onblur="this.style.borderColor='#d1d5db'">
                                @error('max_participants')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">
                                    Aktuelle Teilnehmerzahl: {{ $arbeitsgemeinschaft->participants()->count() }}
                                </p>
                            </div>

                            <!-- Verantwortlicher -->
                            <div>
                                <label for="manager_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-user mr-1" style="color: var(--color-widget-accent-from)"></i>
                                    Verantwortlicher
                                </label>
                                <select class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('manager_id') border-red-500 @enderror"
                                        id="manager_id" name="manager_id" required
                                        onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                        onblur="this.style.borderColor='#d1d5db'">
                                    <option value="">Bitte wählen...</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}"
                                            {{ old('manager_id', $arbeitsgemeinschaft->manager_id) == $manager->id ? 'selected' : '' }}>
                                            {{ $manager->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('manager_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Rechte Spalte -->
                        <div class="space-y-4">
                            <!-- Wochentag -->
                            <div>
                                <label for="weekday" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-calendar-day mr-1" style="color: var(--color-widget-accent-from)"></i>
                                    Wochentag
                                </label>
                                <select class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('weekday') border-red-500 @enderror"
                                        id="weekday" name="weekday" required
                                        onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                        onblur="this.style.borderColor='#d1d5db'">
                                    <option value="">Bitte wählen...</option>
                                    @foreach($weekdays as $key => $day)
                                        <option value="{{ $key }}"
                                            {{ old('weekday', $arbeitsgemeinschaft->weekday) == $key ? 'selected' : '' }}>
                                            {{ $day }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('weekday')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Zeiten -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="start_time" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-clock mr-1" style="color: var(--color-widget-accent-from)"></i>
                                        Startzeit
                                    </label>
                                    <input type="time"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('start_time') border-red-500 @enderror"
                                           id="start_time" name="start_time"
                                           value="{{ old('start_time', $arbeitsgemeinschaft->start_time->format('H:i')) }}" required
                                           onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                           onblur="this.style.borderColor='#d1d5db'">
                                    @error('start_time')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="end_time" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-clock mr-1" style="color: var(--color-widget-accent-from)"></i>
                                        Endzeit
                                    </label>
                                    <input type="time"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('end_time') border-red-500 @enderror"
                                           id="end_time" name="end_time"
                                           value="{{ old('end_time', $arbeitsgemeinschaft->end_time->format('H:i')) }}" required
                                           onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                           onblur="this.style.borderColor='#d1d5db'">
                                    @error('end_time')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Datum -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-calendar mr-1" style="color: var(--color-widget-accent-from)"></i>
                                        Startdatum
                                    </label>
                                    <input type="date"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('start_date') border-red-500 @enderror"
                                           id="start_date" name="start_date"
                                           value="{{ old('start_date', $arbeitsgemeinschaft->start_date->format('Y-m-d')) }}" required
                                           onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                           onblur="this.style.borderColor='#d1d5db'">
                                    @error('start_date')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-calendar-check mr-1" style="color: var(--color-widget-accent-from)"></i>
                                        Enddatum
                                    </label>
                                    <input type="date"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('end_date') border-red-500 @enderror"
                                           id="end_date" name="end_date"
                                           value="{{ old('end_date', $arbeitsgemeinschaft->end_date->format('Y-m-d')) }}" required
                                           onfocus="this.style.borderColor='var(--color-widget-accent-from)'"
                                           onblur="this.style.borderColor='#d1d5db'">
                                    @error('end_date')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Gruppen -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-layer-group mr-1" style="color: var(--color-widget-accent-from)"></i>
                                    Zugangsberechtigte Gruppen
                                </label>
                                <div class="border-2 border-gray-300 rounded-lg p-4 @error('groups') border-red-500 @enderror">
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach($groups as $group)
                                            <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 rounded p-1 transition-colors">
                                                <input type="checkbox"
                                                       name="groups[]" value="{{ $group->id }}"
                                                       id="group_{{ $group->id }}"
                                                       class="w-4 h-4 rounded border-gray-300 cursor-pointer"
                                                       style="accent-color: var(--color-widget-accent-from)"
                                                    {{ in_array($group->id, old('groups', $arbeitsgemeinschaft->groups->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                <span class="text-sm text-gray-700">{{ $group->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                @error('groups')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-2 border-t border-gray-200">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md"
                                style="background-color: var(--color-widget-accent-from)"
                                onmouseover="this.style.backgroundColor='var(--color-widget-accent-to)'"
                                onmouseout="this.style.backgroundColor='var(--color-widget-accent-from)'">
                            <i class="fas fa-save"></i>
                            Änderungen speichern
                        </button>
                        <a href="{{ route('verwaltung.arbeitsgemeinschaften.index') }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors duration-200">
                            <i class="fas fa-times"></i>
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            function validateTimes() {
                if (startTimeInput.value && endTimeInput.value) {
                    endTimeInput.setCustomValidity(
                        startTimeInput.value >= endTimeInput.value
                            ? 'Die Endzeit muss nach der Startzeit liegen'
                            : ''
                    );
                }
            }
            startTimeInput.addEventListener('change', validateTimes);
            endTimeInput.addEventListener('change', validateTimes);

            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            function validateDates() {
                if (startDateInput.value && endDateInput.value) {
                    endDateInput.setCustomValidity(
                        startDateInput.value >= endDateInput.value
                            ? 'Das Enddatum muss nach dem Startdatum liegen'
                            : ''
                    );
                }
            }
            startDateInput.addEventListener('change', validateDates);
            endDateInput.addEventListener('change', validateDates);
        });
    </script>
@endpush
@endsection
