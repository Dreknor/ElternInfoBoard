@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-6">
        <div class="max-w-3xl mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="{{route('elternrat.events.index')}}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>Zurück zur Übersicht</span>
                </a>
            </div>

            <!-- Main Form Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-calendar-plus"></i>
                        Neuer Termin
                    </h5>
                </div>

                @if ($errors->any())
                    <div class="p-6 bg-red-50 border-b border-red-200">
                        <div class="bg-white border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl mt-0.5"></i>
                                <div class="flex-1">
                                    <h6 class="font-semibold text-red-800 mb-2">Bitte beheben Sie folgende Fehler:</h6>
                                    <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="p-6">
                    <form action="{{route('elternrat.events.store')}}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-heading text-orange-600"></i> Titel *
                            </label>
                            <input type="text"
                                   name="title"
                                   value="{{old('title')}}"
                                   required
                                   placeholder="z.B. Elternratssitzung November"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-align-left text-orange-600"></i> Beschreibung
                            </label>
                            <textarea name="description"
                                      rows="4"
                                      placeholder="Tagesordnung und weitere Informationen..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all resize-none">{{old('description')}}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                <i class="fas fa-info-circle"></i>
                                Optional: Fügen Sie Details zur Tagesordnung oder wichtige Hinweise hinzu
                            </p>
                        </div>

                        <!-- Date & Time -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-calendar text-orange-600"></i> Startzeit *
                                </label>
                                <input type="datetime-local"
                                       name="start_time"
                                       value="{{old('start_time')}}"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-calendar text-orange-600"></i> Endzeit *
                                </label>
                                <input type="datetime-local"
                                       name="end_time"
                                       value="{{old('end_time')}}"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt text-orange-600"></i> Ort
                            </label>
                            <input type="text"
                                   name="location"
                                   value="{{old('location')}}"
                                   placeholder="z.B. Schule, Raum 101"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                            <p class="mt-1 text-xs text-gray-500">
                                <i class="fas fa-info-circle"></i>
                                Optional: Wo findet der Termin statt?
                            </p>
                        </div>

                        <!-- Reminder Settings -->
                        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                            <h6 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <i class="fas fa-bell text-orange-600"></i>
                                Erinnerung
                            </h6>
                            <div class="space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox"
                                           name="send_reminder"
                                           value="1"
                                           checked
                                           class="w-5 h-5 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 focus:ring-2">
                                    <span class="text-sm text-gray-700">Erinnerung an Teilnehmer senden</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <label class="text-sm text-gray-700">Stunden vorher:</label>
                                    <select name="reminder_hours"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 bg-white">
                                        <option value="1">1 Stunde</option>
                                        <option value="3">3 Stunden</option>
                                        <option value="6">6 Stunden</option>
                                        <option value="12">12 Stunden</option>
                                        <option value="24" selected>24 Stunden</option>
                                        <option value="48">48 Stunden</option>
                                        <option value="72">72 Stunden</option>
                                        <option value="168">1 Woche</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-gray-200 flex flex-col sm:flex-row gap-3">
                            <button type="submit"
                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white font-semibold rounded-lg hover:from-orange-600 hover:to-red-600 transform hover:scale-105 transition-all duration-200 shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Termin erstellen</span>
                            </button>
                            <a href="{{route('elternrat.events.index')}}"
                               class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-times"></i>
                                <span>Abbrechen</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="mt-6 bg-blue-50 rounded-lg p-4 border border-blue-200">
                <h6 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-blue-600"></i>
                    Tipps
                </h6>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                    <li>Alle Elternratsmitglieder werden über neue Termine informiert</li>
                    <li>Teilnehmer können zusagen, absagen oder "vielleicht" angeben</li>
                    <li>Erinnerungen werden automatisch vor dem Termin verschickt</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
