@extends('layouts.app')

@section('content')
    <div class="w-full max-w-4xl mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                    <i class="fas fa-edit"></i>
                    Liste "{{ $liste->listenname }}" bearbeiten
                </h2>
            </div>

            <!-- Body -->
            <div class="px-6 py-6">
                <form action="{{ url('listen/' . $liste->id) }}" method="post" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Erste Reihe: Name und Typ-spezifische Felder -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="listenname" class="block text-sm font-semibold text-gray-700 mb-2">
                                Name der Liste <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="listenname" id="listenname"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   value="{{ $liste->listenname }}"
                                   required>
                        </div>

                        @if($liste->type == 'termin')
                            <div>
                                <label for="duration" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Dauer (Minuten)
                                    <span class="block text-xs font-normal text-gray-500 mt-1">Ändert keine bestehenden Termine</span>
                                </label>
                                <input type="number" min="0" name="duration" id="duration"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                       value="{{ $liste->duration }}">
                            </div>
                        @else
                            <div id="selectmakeEntry">
                                <label for="make_new_entry" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Neue Einträge erlauben?
                                </label>
                                <select name="make_new_entry" id="make_new_entry"
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                    <option value="0" selected>Nein</option>
                                    <option value="1">Ja</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- Zweite Reihe: Termine und Einstellungen -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="ende" class="block text-sm font-semibold text-gray-700 mb-2">
                                Ausblenden ab <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="ende"
                                   value="{{ $liste->ende->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   required>
                        </div>

                        <div>
                            <label for="visible_for_all" class="block text-sm font-semibold text-gray-700 mb-2">
                                Sichtbarkeit
                            </label>
                            <select name="visible_for_all" id="visible_for_all"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                <option value="0" @if($liste->visible_for_all == 0) selected @endif>Nur eigene Einträge</option>
                                <option value="1" @if($liste->visible_for_all == 1) selected @endif>Alle Einträge sichtbar</option>
                            </select>
                        </div>

                        <div>
                            <label for="multiple" class="block text-sm font-semibold text-gray-700 mb-2">
                                Mehrfach buchbar?
                            </label>
                            <select name="multiple" id="multiple"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                <option value="0" @if($liste->multiple == 0) selected @endif>Nein</option>
                                <option value="1" @if($liste->multiple == 1) selected @endif>Ja</option>
                            </select>
                        </div>

                        <div>
                            <label for="active" class="block text-sm font-semibold text-gray-700 mb-2">
                                Aktiviert?
                            </label>
                            <select name="active" id="active"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                <option value="1" @if($liste->active == 1) selected @endif>Ja, veröffentlichen</option>
                                <option value="0" @if($liste->active == 0) selected @endif>Nein, Entwurf</option>
                            </select>
                        </div>
                    </div>

                    <!-- Beschreibung und Gruppen -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="comment" class="block text-sm font-semibold text-gray-700 mb-2">
                                Beschreibung / Hinweis
                            </label>
                            <textarea name="comment" id="comment" rows="4"
                                      class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none resize-none">{{ $liste->comment }}</textarea>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h3 class="font-semibold text-gray-700 mb-3">Gruppen zuweisen</h3>
                            @include('include.formGroups')
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200">
                            <i class="fas fa-check"></i>
                            Änderungen speichern
                        </button>
                        <a href="{{ url('listen/' . $liste->id) }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors duration-200">
                            <i class="fas fa-times"></i>
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

