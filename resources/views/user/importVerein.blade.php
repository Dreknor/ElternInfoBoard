@extends('layouts.app')
@section('title') - Vereinsmitglieder importieren @endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <i class="fas fa-users text-primary text-xl"></i>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Vereinsmitglieder importieren</h1>
        </div>

        <div class="p-6" x-data="{ sendMode: 'email' }">
            @if(session('Meldung'))
                <div class="mb-4 p-4 rounded-lg bg-{{ session('type') === 'danger' ? 'red' : 'blue' }}-50 border border-{{ session('type') === 'danger' ? 'red' : 'blue' }}-200 text-{{ session('type') === 'danger' ? 'red' : 'blue' }}-800">
                    {{ session('Meldung') }}
                </div>
            @endif

            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                Eine Zeile pro Mitglied mit den Spalten <strong>„Person: Vorname Nachname“</strong> und
                <strong>„Person: E-Mail Privat“</strong> (Auswertung der Schulsoftware). Mehrere E-Mail-Adressen einer Person
                mit Semikolon trennen.
            </p>
            <ul class="text-sm text-gray-600 dark:text-gray-300 mb-4 list-disc pl-5 space-y-1">
                <li>Bestehende Konten werden über <strong>alle</strong> E-Mail-Adressen der Zeile erkannt (Groß-/Kleinschreibung egal) und verknüpft – es entstehen keine doppelten Konten.</li>
                <li>Geändert werden ausschließlich die Gruppe und die Rolle <strong>„Vereinsmitglied“</strong>. Alle anderen Gruppen, Rollen und Kontodaten bleiben unverändert.</li>
                <li>Unklare Fälle (z.&nbsp;B. Adressen verschiedener Konten, gleichnamiges Konto mit anderer E-Mail, Konto im Papierkorb) werden nicht automatisch geändert, sondern in der Vorschau gemeldet.</li>
                <li>Vor dem Speichern wird eine <strong>Vorschau</strong> angezeigt.</li>
            </ul>

            <form action="{{ url('/users/importVerein') }}" method="post" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="send_email" x-bind:value="sendMode === 'email' ? '1' : '0'">

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <input type="file" name="file" accept=".xls,.xlsx,.ods,.csv" required
                           class="block w-full text-sm text-gray-700 dark:text-gray-200">
                    <a href="{{ route('users.vorlage.verein') }}" class="btn btn-outline-warning btn-sm whitespace-nowrap">
                        <i class="fas fa-download mr-1"></i> Vorlage Verein
                    </a>
                </div>
                @error('file')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="abgleich" value="1" class="mt-1" checked>
                    <span>Mitglieder, die <strong>nicht mehr in der Datei</strong> stehen, aus Gruppe und Rolle „Vereinsmitglied“ entfernen (die Vorschau zeigt, wer betroffen wäre; die Konten selbst bleiben bestehen)</span>
                </label>

                <div>
                    <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Zugangsdaten für neue Benutzer</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer"
                               :class="sendMode === 'email' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700'">
                            <input type="radio" x-model="sendMode" value="email" class="mt-0.5">
                            <span class="text-sm text-gray-800 dark:text-gray-200">
                                <i class="fas fa-envelope mr-1 text-primary"></i> Per E-Mail versenden
                            </span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer"
                               :class="sendMode === 'pdf' ? 'border-orange-400 bg-orange-50 dark:bg-orange-900/10' : 'border-gray-200 dark:border-gray-700'">
                            <input type="radio" x-model="sendMode" value="pdf" class="mt-0.5">
                            <span class="text-sm text-gray-800 dark:text-gray-200">
                                <i class="fas fa-file-pdf mr-1 text-orange-500"></i> Als PDF (kein Mailversand an neue Benutzer)
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye mr-1"></i> Vorschau anzeigen
                    </button>
                    <a href="{{ url('users/import') }}" class="btn btn-outline-secondary btn-sm">Andere Importe</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
