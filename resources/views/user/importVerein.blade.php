@extends('layouts.app')
@section('title') - Vereinsmitglieder importieren @endsection

@section('content')
<div class="max-w-6xl mx-auto px-2 sm:px-0">

    {{-- Kopfbereich --}}
    <div class="mb-6">
        <a href="{{ url('users') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 no-underline mb-3">
            <i class="fas fa-arrow-left text-xs"></i> Benutzerkonten
        </a>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">Vereinsmitglieder importieren</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Mitgliederliste aus der Schulsoftware abgleichen – mit Vorschau vor dem Speichern.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Aktuelle Mitglieder</span>
                <span class="text-xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ $memberCount }}</span>
            </div>
        </div>
    </div>

    @if(session('Meldung'))
        <div @class([
            'mb-5 flex items-start gap-3 p-4 rounded-xl border text-sm',
            'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300' => session('type') === 'danger',
            'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300' => session('type') !== 'danger',
        ])>
            <i class="fas {{ session('type') === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle' }} mt-0.5"></i>
            <span>{{ session('Meldung') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Formular --}}
        <form action="{{ url('/users/importVerein') }}" method="post" enctype="multipart/form-data"
              class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700"
              x-data="{ fileName: '', dragging: false, sendMode: 'email', abgleich: true, notify: true, submitting: false }"
              @submit="submitting = true">
            @csrf
            <input type="hidden" name="send_email" :value="sendMode === 'email' ? '1' : '0'">
            <input type="hidden" name="abgleich" :value="abgleich ? '1' : '0'">
            <input type="hidden" name="benachrichtigen" :value="notify ? '1' : '0'">

            {{-- 1: Datei --}}
            <section class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-sm font-semibold flex items-center justify-center">1</span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 m-0">Mitgliederliste hochladen</h2>
                </div>

                <label for="vereinFile"
                       class="flex flex-col items-center justify-center gap-2 w-full px-6 py-10 rounded-xl border-2 border-dashed cursor-pointer text-center transition-colors"
                       :class="dragging ? 'border-amber-400 bg-amber-50 dark:bg-amber-900/10' : (fileName ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-900/10' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/40')"
                       @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                       @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { $refs.file.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0].name }">
                    <template x-if="!fileName">
                        <div>
                            <i class="fas fa-file-upload text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 m-0">Datei hierher ziehen oder <span class="text-amber-700 dark:text-amber-400 underline">auswählen</span></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-0">Excel, LibreOffice oder CSV · max. 10&nbsp;MB</p>
                        </div>
                    </template>
                    <template x-if="fileName">
                        <div>
                            <i class="fas fa-file-excel text-3xl text-green-600 mb-2"></i>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 m-0" x-text="fileName"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-0">Andere Datei wählen</p>
                        </div>
                    </template>
                    <input type="file" name="file" id="vereinFile" x-ref="file" accept=".xls,.xlsx,.ods,.csv" required class="sr-only"
                           @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''">
                </label>
                @error('file')
                    <p class="text-sm text-red-600 mt-2 mb-0"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                @enderror
            </section>

            {{-- 2: Abgleich --}}
            <section class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-sm font-semibold flex items-center justify-center">2</span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 m-0">Abgleich und Benachrichtigung</h2>
                </div>
                <button type="button" role="switch" :aria-checked="abgleich.toString()" @click="abgleich = !abgleich"
                        class="w-full flex items-start gap-4 p-4 rounded-xl border text-left transition-colors"
                        :class="abgleich ? 'border-amber-300 bg-amber-50/60 dark:border-amber-700 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700'">
                    <span class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors mt-0.5"
                          :class="abgleich ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600'">
                        <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform"
                              :class="abgleich ? 'translate-x-5' : ''"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">Nicht mehr enthaltene Mitglieder entfernen</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Wer nicht in der Datei steht, verliert Gruppe und Rolle „Vereinsmitglied“. Das Konto selbst bleibt bestehen.
                            Die Vorschau zeigt alle Betroffenen namentlich.
                        </span>
                    </span>
                </button>
                <button type="button" role="switch" :aria-checked="notify.toString()" @click="notify = !notify"
                        class="w-full flex items-start gap-4 p-4 mt-3 rounded-xl border text-left transition-colors"
                        :class="notify ? 'border-amber-300 bg-amber-50/60 dark:border-amber-700 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700'">
                    <span class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors mt-0.5"
                          :class="notify ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600'">
                        <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform"
                              :class="notify ? 'translate-x-5' : ''"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">Bestehende Konten per E-Mail benachrichtigen</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Wer neu in den Verein aufgenommen oder daraus entfernt wird, erhält eine kurze E-Mail.
                            Die Texte lassen sich unter Einstellungen › E-Mail anpassen.
                        </span>
                    </span>
                </button>
            </section>

            {{-- 3: Zugangsdaten --}}
            <section class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-full bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-sm font-semibold flex items-center justify-center">3</span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 m-0">Zugangsdaten für neue Konten</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors mb-0"
                           :class="sendMode === 'email' ? 'border-amber-400 bg-amber-50/60 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                        <input type="radio" x-model="sendMode" value="email" class="mt-1">
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100"><i class="fas fa-envelope mr-1.5 text-gray-500"></i>Per E-Mail</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Neue Mitglieder erhalten ihre Zugangsdaten direkt per E-Mail.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-colors mb-0"
                           :class="sendMode === 'pdf' ? 'border-amber-400 bg-amber-50/60 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                        <input type="radio" x-model="sendMode" value="pdf" class="mt-1">
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100"><i class="fas fa-file-pdf mr-1.5 text-gray-500"></i>Als PDF</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kein Mailversand. Das PDF wird heruntergeladen und zusätzlich an Sie geschickt.</span>
                        </span>
                    </label>
                </div>
            </section>

            <div class="p-6 bg-gray-50 dark:bg-gray-800/60 rounded-b-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 m-0">
                    <i class="fas fa-shield-alt mr-1"></i> Im nächsten Schritt sehen Sie alle Änderungen. Gespeichert wird erst nach Ihrer Bestätigung.
                </p>
                <button type="submit" :disabled="!fileName || submitting"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors"
                        :class="fileName && !submitting ? 'bg-gray-900 text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900' : 'bg-gray-200 text-gray-400 cursor-not-allowed dark:bg-gray-700'">
                    <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-eye'"></i>
                    <span x-text="submitting ? 'Datei wird geprüft…' : 'Vorschau anzeigen'"></span>
                </button>
            </div>
        </form>

        {{-- Hinweise --}}
        <aside class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">So wird abgeglichen</h3>
                <ul class="space-y-4 text-sm list-none p-0 m-0">
                    <li class="flex gap-3">
                        <span class="w-5 flex-shrink-0 text-center mt-0.5"><i class="fas fa-link text-green-600"></i></span>
                        <span class="text-gray-600 dark:text-gray-300">Bestehende Konten werden über <strong>alle</strong> E-Mail-Adressen einer Zeile erkannt und verknüpft.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 flex-shrink-0 text-center mt-0.5"><i class="fas fa-lock text-blue-600"></i></span>
                        <span class="text-gray-600 dark:text-gray-300">Geändert werden nur Gruppe und Rolle „Vereinsmitglied“. Andere Gruppen, Rollen und Kontodaten bleiben unberührt.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 flex-shrink-0 text-center mt-0.5"><i class="fas fa-user-plus text-gray-500"></i></span>
                        <span class="text-gray-600 dark:text-gray-300">Neue Konten entstehen nur, wenn weder E-Mail noch Name zu einem Konto passen.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 flex-shrink-0 text-center mt-0.5"><i class="fas fa-question-circle text-amber-600"></i></span>
                        <span class="text-gray-600 dark:text-gray-300">Unklare Fälle werden als <strong>Klärungsfall</strong> gemeldet und nicht automatisch geändert.</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Erwartete Spalten</h3>
                <dl class="text-sm space-y-2 mb-4">
                    <div>
                        <dt><code class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Person: Vorname Nachname</code></dt>
                        <dd class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-0">alternativ „Vorname“ und „Nachname“</dd>
                    </div>
                    <div>
                        <dt><code class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Person: E-Mail Privat</code></dt>
                        <dd class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-0">mehrere Adressen mit Semikolon trennen</dd>
                    </div>
                </dl>
                <a href="{{ route('users.vorlage.verein') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 no-underline">
                    <i class="fas fa-download"></i> Vorlage herunterladen
                </a>
            </div>
        </aside>
    </div>
</div>
@endsection
