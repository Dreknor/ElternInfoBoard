@extends('layouts.app')

@section('title')
    - Krankmeldung
@endsection

@section('content')
    @can('download krankmeldungen')
        <div class="mb-6">
            <a href="{{ url('krankmeldung/download') }}"
               class="inline-flex items-center gap-2 w-full md:w-auto px-4 py-2
                      bg-blue-600 hover:bg-blue-700 text-white font-medium
                      rounded-lg transition-colors duration-200 justify-center md:justify-start">
                <i class="fas fa-download"></i>
                <span>aktuelle Krankmeldung herunterladen</span>
            </a>
        </div>
    @endcan

    <!-- Krankmeldung erstellen -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
            <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                <i class="fas fa-clipboard-list"></i>
                Neue Krankmeldung erstellen
            </h5>
        </div>

        <!-- Card Body -->
        <div class="p-4 md:p-6">
            <form action="{{ url('/krankmeldung') }}" method="post" class="space-y-6" enctype="multipart/form-data">
                @csrf

                <!-- Schüler Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        <span>Name des Schülers / der Schülerin</span>
                        <span class="text-red-500">*</span>
                    </label>

                    @if(auth()->user()->children()->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Kind auswählen -->
                            <div>
                                <label for="child" class="block text-sm text-gray-600 mb-2">Kind auswählen:</label>
                                <select name="child_id" id="child"
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                               focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                               transition-all duration-200 outline-none">
                                    <option value="">Bitte wählen</option>
                                    @foreach(auth()->user()->children() as $child)
                                        <option value="{{ $child->id }}">{{ $child->first_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Oder Name eingeben -->
                            <div>
                                <label for="name" class="block text-sm text-gray-600 mb-2">oder Name eingeben:</label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                              focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                              transition-all duration-200 outline-none"
                                       autofocus>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="text"
                               id="name"
                               name="name"
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                      transition-all duration-200 outline-none"
                               value="{{ old('name', $krankmeldungen->count() > 0 ? $krankmeldungen->first()->name : '') }}"
                               @if($krankmeldungen->count() === 0) autofocus @endif>
                        @error('name')
                            <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>{{ $message }}
                            </p>
                        @enderror
                    @endif
                </div>

                <!-- Erkrankung & Daten -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($diseases)
                        <div class="md:col-span-1">
                            <label for="disease" class="block text-sm font-medium text-gray-700 mb-2">besondere Erkrankung:</label>
                            <select name="disease_id" id="disease"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                           transition-all duration-200 outline-none">
                                <option value="0">keine genannte</option>
                                @foreach($diseases as $disease)
                                    <option value="{{ $disease->id }}"
                                            @if(old('disease_id') == $disease->id) selected @endif>
                                        {{ $disease->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="md:col-span-1">
                        <label for="start" class="block text-sm font-medium text-gray-700 mb-2">
                            <span>Krank ab</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="start"
                               name="start"
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                      transition-all duration-200 outline-none"
                               min="{{ \Carbon\Carbon::now()->subDays(3)->format('Y-m-d') }}"
                               value="{{ old('start', \Carbon\Carbon::now()->format('Y-m-d')) }}"
                               required>
                        @error('start')
                            <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label for="ende" class="block text-sm font-medium text-gray-700 mb-2">
                            <span>Krank bis</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="ende"
                               name="ende"
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                      transition-all duration-200 outline-none"
                               value="{{ old('ende', \Carbon\Carbon::now()->format('Y-m-d')) }}"
                               required>
                        @error('ende')
                            <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Dateien -->
                <div>
                    <label for="files" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-paperclip text-gray-500 mr-1"></i>
                        <span>Dateien (optional):</span>
                    </label>
                    <input type="file"
                           id="files"
                           name="files[]"
                           class="w-full text-sm text-gray-600
                                  file:mr-3 file:px-3 file:py-2 file:bg-blue-50
                                  file:border-0 file:rounded-lg file:text-blue-600
                                  file:cursor-pointer hover:file:bg-blue-100"
                           multiple>
                    <p class="mt-1 text-xs text-gray-500">Unterstützte Dateien: PDF, PNG, JPG, JPEG</p>
                    @error('files')
                        <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Kommentar -->
                <div>
                    <label for="kommentar" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment-alt text-gray-500 mr-1"></i>
                        <span>Kommentar / Bemerkungen (optional):</span>
                    </label>
                    <textarea id="kommentar"
                              name="kommentar"
                              rows="6"
                              class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                      transition-all duration-200 outline-none"
                              placeholder="Hier können Sie weitere Informationen zur Krankheit eingeben...">{{ old('kommentar') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Formatierung möglich (fett, kursiv, etc.)</p>
                    @error('kommentar')
                        <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Button -->
                <div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 w-full md:w-auto
                                   px-6 py-2.5 bg-green-600 hover:bg-green-700
                                   text-white font-semibold rounded-lg
                                   transition-colors duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-check-circle"></i>
                        <span>Krankmeldung senden</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bisherige Krankmeldungen -->
    @if($krankmeldungen && $krankmeldungen->count() > 0)
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-4 py-3 border-b border-teal-700">
                <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-history"></i>
                    Bisherige Krankmeldungen
                </h5>
            </div>

            <!-- Card Body -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                <i class="fas fa-user text-gray-500 mr-2"></i>Kind
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                <i class="fas fa-calendar-alt text-gray-500 mr-2"></i>Zeitraum
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                <i class="fas fa-info-circle text-gray-500 mr-2"></i>Informationen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($krankmeldungen as $krankmeldung)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                                        <i class="fas fa-child"></i>
                                        {{ $krankmeldung->name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                                        <span class="font-medium">{{ $krankmeldung->start->format('d.m.Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <i class="fas fa-arrow-left text-gray-400 text-xs"></i>
                                        <span class="font-medium">{{ $krankmeldung->ende->format('d.m.Y') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-2">
                                        <div class="hidden md:block text-sm text-gray-700 bg-gray-50 p-2 rounded">
                                            {!! $krankmeldung->kommentar ?? '<em class="text-gray-400">Kein Kommentar</em>' !!}
                                        </div>
                                        <div class="text-xs text-gray-500 border-t border-gray-200 pt-2">
                                            <p class="flex items-center gap-1 mb-1">
                                                <i class="fas fa-clock"></i>
                                                {{ $krankmeldung->created_at->format('d.m.Y H:i') }} Uhr
                                            </p>
                                            <p class="flex items-center gap-1">
                                                <i class="fas fa-user-check"></i>
                                                von {{ $krankmeldung->user->name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Card Footer - Pagination -->
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
                {{ $krankmeldungen->links() }}
            </div>
        </div>
    @else
        <div class="flex items-start gap-3 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-lg">
            <i class="fas fa-info-circle text-amber-600 mt-0.5 flex-shrink-0"></i>
            <p class="text-amber-800 text-sm mb-0">
                Es existieren noch keine bisherigen Krankmeldungen. Diese werden hier nach der ersten Meldung angezeigt.
            </p>
        </div>
    @endif

@endsection
