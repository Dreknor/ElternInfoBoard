@extends('layouts.app')

@section('title')
    - Krankmeldung
@endsection

@section('content')
    @can('download krankmeldungen')
        <div class="mb-6">
            <a href="{{ url('krankmeldung/download') }}"
               class="inline-flex items-center gap-2 w-full md:w-auto px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200 justify-center md:justify-start"
               style="background-color: var(--color-widget-primary-from)">
                <i class="fas fa-download"></i>
                <span>aktuelle Krankmeldung herunterladen</span>
            </a>
        </div>
    @endcan

    <!-- Krankmeldung erstellen -->
    <div class="rounded-lg shadow-lg overflow-hidden mb-6" style="background-color: var(--color-card-bg)">
        <!-- Card Header -->
        <div class="px-4 py-3 border-b"
             style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
            <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
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
                    <label for="name" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">
                        <span>Name des Schülers / der Schülerin</span>
                        <span class="text-red-500">*</span>
                    </label>

                    @if(auth()->user()->children()->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Kind auswählen -->
                            <div>
                                <label for="child" class="block text-sm mb-2" style="color: var(--color-text-secondary)">Kind auswählen:</label>
                                <select name="child_id" id="child"
                                        class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                                        style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">
                                    <option value="">Bitte wählen</option>
                                    @foreach(auth()->user()->children() as $child)
                                        <option value="{{ $child->id }}">{{ $child->first_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Oder Name eingeben -->
                            <div>
                                <label for="name" class="block text-sm mb-2" style="color: var(--color-text-secondary)">oder Name eingeben:</label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                                       style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)"
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
                               class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                               style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)"
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
                            <label for="disease" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">besondere Erkrankung:</label>
                            <select name="disease_id" id="disease"
                                    class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                                    style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">
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
                        <label for="start" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">
                            <span>Krank ab</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="start"
                               name="start"
                               class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                               style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)"
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
                        <label for="ende" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">
                            <span>Krank bis</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="ende"
                               name="ende"
                               class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                               style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)"
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
                    <label for="files" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">
                        <i class="fas fa-paperclip mr-1" style="color: var(--color-text-secondary)"></i>
                        <span>Dateien (optional):</span>
                    </label>
                    <input type="file"
                           id="files"
                           name="files[]"
                           class="w-full text-sm file:mr-3 file:px-3 file:py-2 file:border-0 file:rounded-lg file:cursor-pointer"
                           style="color: var(--color-text-secondary)"
                           multiple>
                    <p class="mt-1 text-xs" style="color: var(--color-text-muted)">Unterstützte Dateien: PDF, PNG, JPG, JPEG</p>
                    @error('files')
                        <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Kommentar -->
                <div>
                    <label for="kommentar" class="block text-sm font-medium mb-2" style="color: var(--color-text-primary)">
                        <i class="fas fa-comment-alt mr-1" style="color: var(--color-text-secondary)"></i>
                        <span>Kommentar / Bemerkungen (optional):</span>
                    </label>
                    <textarea id="kommentar"
                              name="kommentar"
                              rows="6"
                              class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                              style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)"
                              placeholder="Hier können Sie weitere Informationen zur Krankheit eingeben...">{{ old('kommentar') }}</textarea>
                    <p class="mt-1 text-xs" style="color: var(--color-text-muted)">Formatierung möglich (fett, kursiv, etc.)</p>
                    @error('kommentar')
                        <p class="mt-1 text-sm text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Button -->
                <div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 w-full md:w-auto px-6 py-2.5 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg"
                            style="background-color: var(--color-widget-success-from)">
                        <i class="fas fa-check-circle"></i>
                        <span>Krankmeldung senden</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bisherige Krankmeldungen -->
    @if($krankmeldungen && $krankmeldungen->count() > 0)
        <div class="rounded-lg shadow-lg overflow-hidden" style="background-color: var(--color-card-bg)">
            <!-- Card Header -->
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-success-from), var(--color-widget-success-to)); border-color: var(--color-widget-success-border)">
                <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                    <i class="fas fa-history"></i>
                    Bisherige Krankmeldungen
                </h5>
            </div>

            <!-- Card Body -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2" style="background-color: var(--color-surface-subtle); border-color: var(--color-card-border)">
                            <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-text-primary)">
                                <i class="fas fa-user mr-2" style="color: var(--color-text-secondary)"></i>Kind
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-text-primary)">
                                <i class="fas fa-calendar-alt mr-2" style="color: var(--color-text-secondary)"></i>Zeitraum
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-semibold" style="color: var(--color-text-primary)">
                                <i class="fas fa-info-circle mr-2" style="color: var(--color-text-secondary)"></i>Informationen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-theme">
                        @foreach($krankmeldungen as $krankmeldung)
                            <tr class="transition-colors duration-200"
                                onmouseover="this.style.backgroundColor='var(--color-surface-subtle)'"
                                onmouseout="this.style.backgroundColor=''">
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center gap-2 px-3 py-1 text-sm font-medium rounded-full"
                                          style="background-color: var(--color-widget-body-bg); color: var(--color-widget-primary-border); border: 1px solid var(--color-widget-primary-border)">
                                        <i class="fas fa-child"></i>
                                        {{ $krankmeldung->name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm" style="color: var(--color-text-secondary)">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-arrow-right text-xs" style="color: var(--color-text-muted)"></i>
                                        <span class="font-medium">{{ $krankmeldung->start->format('d.m.Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <i class="fas fa-arrow-left text-xs" style="color: var(--color-text-muted)"></i>
                                        <span class="font-medium">{{ $krankmeldung->ende->format('d.m.Y') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-2">
                                        <div class="hidden md:block text-sm p-2 rounded" style="color: var(--color-text-primary); background-color: var(--color-surface-subtle)">
                                            {!! $krankmeldung->kommentar ?? '<em style="color: var(--color-text-muted)">Kein Kommentar</em>' !!}
                                        </div>
                                        <div class="text-xs border-t pt-2" style="color: var(--color-text-muted); border-color: var(--color-card-border)">
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
            <div class="px-4 py-3 border-t" style="background-color: var(--color-surface-subtle); border-color: var(--color-card-border)">
                {{ $krankmeldungen->links() }}
            </div>
        </div>
    @else
        <div class="flex items-start gap-3 p-4 border-l-4 rounded-lg"
             style="background-color: var(--color-widget-body-bg); border-color: var(--color-widget-warning-from)">
            <i class="fas fa-info-circle mt-0.5 flex-shrink-0" style="color: var(--color-widget-warning-from)"></i>
            <p class="text-sm mb-0" style="color: var(--color-widget-warning-border)">
                Es existieren noch keine bisherigen Krankmeldungen. Diese werden hier nach der ersten Meldung angezeigt.
            </p>
        </div>
    @endif

@endsection
