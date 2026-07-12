@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <i class="fas fa-file-import text-primary text-xl"></i>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Benutzer importieren</h1>
        </div>

        <div class="p-6" x-data="importWizard()" x-cloak>

            {{-- Flash-Meldungen --}}
            @if(session('Meldung'))
            <div class="mb-4 p-4 rounded-lg bg-{{ session('type', 'info') === 'success' ? 'green' : (session('type') === 'danger' ? 'red' : 'blue') }}-50 border border-{{ session('type', 'info') === 'success' ? 'green' : (session('type') === 'danger' ? 'red' : 'blue') }}-200 text-{{ session('type', 'info') === 'success' ? 'green' : (session('type') === 'danger' ? 'red' : 'blue') }}-800">
                {{ session('Meldung') }}
            </div>
            @endif

            {{-- Step Indicator --}}
            <div class="mb-8">
                <div class="flex items-center justify-between relative">
                    <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 dark:bg-gray-700 z-0"></div>
                    <div class="absolute top-4 left-0 h-0.5 bg-primary z-0 transition-all duration-500"
                         :style="`width: ${(step - 1) * 50}%`"></div>

                    <template x-for="(label, i) in ['Import-Typ', 'Datei & Spalten', 'Optionen & Import']" :key="i">
                        <div class="flex flex-col items-center z-10">
                            <div class="w-8 h-8 rounded-full flex items-center justify-content-center text-sm font-bold transition-all duration-300"
                                 :class="step > i + 1 ? 'bg-green-500 text-white' : step === i + 1 ? 'bg-primary text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <span x-show="step <= i + 1" x-text="i + 1"></span>
                                <i x-show="step > i + 1" class="fas fa-check text-xs"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium hidden sm:block transition-colors duration-300"
                                  :class="step >= i + 1 ? 'text-primary' : 'text-gray-400'"
                                  x-text="label"></span>
                        </div>
                    </template>
                </div>
            </div>

            <form action="{{ url('/users/import') }}" method="post" enctype="multipart/form-data" id="importForm"
                  @submit="if (canSubmit) { submitting = true } else { $event.preventDefault() }">
                @csrf
                {{-- Hidden fields populated by Alpine --}}
                <input type="hidden" name="type" x-bind:value="importTyp">
                <input type="hidden" name="send_email" x-bind:value="sendMode === 'email' ? '1' : '0'">
                <input type="hidden" name="klassenstufe" x-bind:value="mapping.klassenstufe">
                <input type="hidden" name="lerngruppe" x-bind:value="mapping.lerngruppe">
                <input type="hidden" name="gruppen" x-bind:value="mapping.gruppen">
                <input type="hidden" name="S1Vorname" x-bind:value="mapping.s1Vorname">
                <input type="hidden" name="S1Nachname" x-bind:value="mapping.s1Nachname">
                <input type="hidden" name="S1Email" x-bind:value="mapping.s1Email">
                <input type="hidden" name="S2Vorname" x-bind:value="mapping.s2Vorname">
                <input type="hidden" name="S2Nachname" x-bind:value="mapping.s2Nachname">
                <input type="hidden" name="S2Email" x-bind:value="mapping.s2Email">
                <input type="hidden" name="kind_vorname" x-bind:value="mapping.kindVorname">
                <input type="hidden" name="kind_nachname" x-bind:value="mapping.kindNachname">
                <template x-for="(name, i) in selectedNewGroups" :key="i">
                    <input type="hidden" name="new_groups[]" :value="name">
                </template>

                {{-- ═══ STEP 1: Import-Typ ═══════════════════════════════════════════ --}}
                <div x-show="step === 1">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-1">Was möchten Sie importieren?</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Wählen Sie den passenden Import-Typ aus.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        {{-- Eltern --}}
                        <div @click="importTyp = 'eltern'"
                             class="cursor-pointer rounded-xl border-2 p-5 flex flex-col items-center text-center transition-all duration-200"
                             :class="importTyp === 'eltern'
                                ? 'border-primary bg-primary/5 dark:bg-primary/10'
                                : 'border-gray-200 dark:border-gray-700 hover:border-primary/50'">
                            <div class="w-14 h-14 rounded-full flex items-center justify-content-center mb-3"
                                 :class="importTyp === 'eltern' ? 'bg-primary/10 text-primary' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Eltern-Import</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jährlicher Gesamtimport aller Eltern mit Klassenzuordnung. Gruppenverknüpfungen werden neu aufgebaut.</p>
                            <div x-show="importTyp === 'eltern'" class="mt-3 text-primary">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                        </div>

                        {{-- Aufnahme --}}
                        <div @click="importTyp = 'aufnahme'"
                             class="cursor-pointer rounded-xl border-2 p-5 flex flex-col items-center text-center transition-all duration-200"
                             :class="importTyp === 'aufnahme'
                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-green-400'">
                            <div class="w-14 h-14 rounded-full flex items-center justify-content-center mb-3"
                                 :class="importTyp === 'aufnahme' ? 'bg-green-100 text-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'">
                                <i class="fas fa-user-plus text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Aufnahme-Import</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Neue Eltern aufnehmen. Bestehende Konten bleiben unverändert.</p>
                            <div x-show="importTyp === 'aufnahme'" class="mt-3 text-green-500">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                        </div>

                        {{-- Mitarbeiter --}}
                        <div @click="importTyp = 'mitarbeiter'"
                             class="cursor-pointer rounded-xl border-2 p-5 flex flex-col items-center text-center transition-all duration-200"
                             :class="importTyp === 'mitarbeiter'
                                ? 'border-gray-500 bg-gray-50 dark:bg-gray-700/50'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-400'">
                            <div class="w-14 h-14 rounded-full flex items-center justify-content-center mb-3"
                                 :class="importTyp === 'mitarbeiter' ? 'bg-gray-200 text-gray-700' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'">
                                <i class="fas fa-chalkboard-teacher text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Mitarbeiter-Import</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Lehrkräfte und Mitarbeiter importieren. Benötigte Spalten: <code class="text-xs">e_mail</code>, <code class="text-xs">vorname</code>, <code class="text-xs">nachname</code>.</p>
                            <div x-show="importTyp === 'mitarbeiter'" class="mt-3 text-gray-500">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Warning for Eltern --}}
                    <div x-show="importTyp === 'eltern'" class="mb-4 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 flex gap-3">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 flex-shrink-0"></i>
                        <div class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Wichtig:</strong> Alle nicht-geschützten Gruppenverknüpfungen werden vor dem Import geleert und vollständig neu aufgebaut. Dieser Vorgang kann nicht rückgängig gemacht werden.
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" @click="step = 2"
                                :disabled="!importTyp"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg font-medium text-sm transition-all text-white"
                                :class="importTyp ? 'hover:opacity-90 cursor-pointer' : 'bg-gray-200 !text-gray-400 cursor-not-allowed'"
                                :style="importTyp ? 'background-color: var(--primary)' : ''">
                            Weiter <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ═══ STEP 2: Datei & Spalten ════════════════════════════════════ --}}
                <div x-show="step === 2">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-1">Excel-Datei hochladen</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                        Laden Sie Ihre Excel-Datei hoch. Die Spalten werden automatisch erkannt und zugeordnet.
                    </p>

                    {{-- Vorlage Download --}}
                    <div class="mb-5 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg flex flex-wrap gap-3 items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Vorlage herunterladen:</span>
                        <a x-show="importTyp === 'eltern'" href="{{ route('users.vorlage.eltern') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-medium bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 text-gray-700 dark:text-gray-200 hover:bg-gray-50">
                            <i class="fas fa-download"></i> Vorlage Eltern
                        </a>
                        <a x-show="importTyp === 'aufnahme'" href="{{ route('users.vorlage.aufnahme') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-medium bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 text-gray-700 dark:text-gray-200 hover:bg-gray-50">
                            <i class="fas fa-download"></i> Vorlage Aufnahme
                        </a>
                        <a x-show="importTyp === 'mitarbeiter'" href="{{ route('users.vorlage.mitarbeiter') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-medium bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 text-gray-700 dark:text-gray-200 hover:bg-gray-50">
                            <i class="fas fa-download"></i> Vorlage Mitarbeiter
                        </a>
                    </div>

                    {{-- File Upload Area --}}
                    <div class="mb-5">
                        <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Excel-Datei auswählen <span class="text-red-500">*</span>
                        </label>
                        <div class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors"
                             :class="fileName ? 'border-green-400 bg-green-50 dark:bg-green-900/10' : 'border-gray-300 dark:border-gray-600 hover:border-primary/50 bg-gray-50 dark:bg-gray-700/30'">
                            <input type="file" name="file" id="importFile" accept=".xls,.xlsx,.ods"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                   @change="handleFileUpload($event)" required>
                            <div x-show="!fileName && !loading">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Datei hierher ziehen oder <span class="text-primary font-medium">klicken zum Auswählen</span></p>
                                <p class="text-xs text-gray-400 mt-1">.xls, .xlsx, .ods – max. 10 MB</p>
                            </div>
                            <div x-show="loading" class="py-2">
                                <svg class="animate-spin h-8 w-8 text-primary mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Datei wird analysiert…</p>
                            </div>
                            <div x-show="fileName && !loading" class="flex items-center justify-center gap-3">
                                <i class="fas fa-file-excel text-green-500 text-2xl"></i>
                                <div class="text-left">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="fileName"></p>
                                    <p class="text-xs text-green-600" x-text="headers.length > 0 ? headers.length + ' Spalten erkannt' : 'Bereit'"></p>
                                </div>
                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Column Mapping (only for eltern/aufnahme and when headers are detected) --}}
                    <div x-show="headers.length > 0 && importTyp !== 'mitarbeiter'" x-transition.opacity>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Spaltenzuordnung</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            Die Spalten wurden automatisch erkannt. Bitte prüfen und ggf. korrigieren.
                            Felder mit <span class="text-red-500 font-medium">*</span> sind Pflichtfelder.
                        </p>

                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400 w-1/3">Feld</th>
                                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400">Spalte in Ihrer Datei</th>
                                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400 hidden sm:table-cell">Erkannte Überschrift</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                    {{-- ── Eltern-only fields ── --}}
                                    <template x-if="importTyp === 'eltern'">
                                        <tr class="bg-white dark:bg-gray-800">
                                            <td class="px-4 py-2.5">
                                                <span class="font-medium text-gray-800 dark:text-gray-200">Klassenstufe</span>
                                                <span class="text-red-500 ml-0.5">*</span>
                                                <p class="text-xs text-gray-400">z.B. "5" → Gruppe "Klassenstufe 5"</p>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <select x-model="mapping.klassenstufe"
                                                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                    <option value="">— nicht zugeordnet —</option>
                                                    <template x-for="(h, i) in headers" :key="i">
                                                        <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.klassenstufe ? headers[mapping.klassenstufe - 1] || '—' : '—'"></td>
                                        </tr>
                                    </template>
                                    <template x-if="importTyp === 'eltern'">
                                        <tr class="bg-white dark:bg-gray-800">
                                            <td class="px-4 py-2.5">
                                                <span class="font-medium text-gray-800 dark:text-gray-200">Lerngruppe / Klasse</span>
                                                <span class="text-red-500 ml-0.5">*</span>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <select x-model="mapping.lerngruppe"
                                                        class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                    <option value="">— nicht zugeordnet —</option>
                                                    <template x-for="(h, i) in headers" :key="i">
                                                        <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.lerngruppe ? headers[mapping.lerngruppe - 1] || '—' : '—'"></td>
                                        </tr>
                                    </template>

                                    {{-- ── Sorgeberechtigter 1 ── --}}
                                    <tr class="bg-blue-50/50 dark:bg-blue-900/10">
                                        <td class="px-4 py-2.5" colspan="3">
                                            <span class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wide">
                                                <i class="fas fa-user mr-1"></i>Sorgeberechtigte/r 1 (Pflichtfelder)
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5">
                                            <span class="font-medium text-gray-800 dark:text-gray-200">Vorname</span>
                                            <span class="text-red-500 ml-0.5">*</span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.s1Vorname"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht zugeordnet —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.s1Vorname ? headers[mapping.s1Vorname - 1] || '—' : '—'"></td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5">
                                            <span class="font-medium text-gray-800 dark:text-gray-200">Nachname</span>
                                            <span class="text-red-500 ml-0.5">*</span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.s1Nachname"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht zugeordnet —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.s1Nachname ? headers[mapping.s1Nachname - 1] || '—' : '—'"></td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5">
                                            <span class="font-medium text-gray-800 dark:text-gray-200">E-Mail-Adresse</span>
                                            <span class="text-red-500 ml-0.5">*</span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.s1Email"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht zugeordnet —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.s1Email ? headers[mapping.s1Email - 1] || '—' : '—'"></td>
                                    </tr>

                                    {{-- ── Sorgeberechtigter 2 (optional) ── --}}
                                    <tr class="bg-purple-50/50 dark:bg-purple-900/10">
                                        <td class="px-4 py-2.5" colspan="3">
                                            <span class="text-xs font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wide">
                                                <i class="fas fa-user mr-1"></i>Sorgeberechtigte/r 2 <span class="font-normal normal-case">(optional)</span>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">Vorname</td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.s2Vorname"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht vorhanden —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.s2Vorname ? headers[mapping.s2Vorname - 1] || '—' : '—'"></td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">Nachname</td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.s2Nachname"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht vorhanden —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.s2Nachname ? headers[mapping.s2Nachname - 1] || '—' : '—'"></td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">E-Mail-Adresse</td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.s2Email"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht vorhanden —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.s2Email ? headers[mapping.s2Email - 1] || '—' : '—'"></td>
                                    </tr>

                                    {{-- ── Kinder (optional) ── --}}
                                    <tr class="bg-amber-50/50 dark:bg-amber-900/10">
                                        <td class="px-4 py-2.5" colspan="3">
                                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide">
                                                <i class="fas fa-child mr-1"></i>Kind <span class="font-normal normal-case">(optional – für automatische Verknüpfung)</span>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5">
                                            <span class="text-gray-700 dark:text-gray-300">Vorname des Kindes</span>
                                            <p class="text-xs text-gray-400">Wird genutzt um das Kind mit den Eltern zu verknüpfen</p>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.kindVorname"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht vorhanden —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.kindVorname ? headers[mapping.kindVorname - 1] || '—' : '—'"></td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">Nachname des Kindes</td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.kindNachname"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht vorhanden —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.kindNachname ? headers[mapping.kindNachname - 1] || '—' : '—'"></td>
                                    </tr>

                                    {{-- ── Gruppen-Liste (optional) ── --}}
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-2.5">
                                            <span class="text-gray-700 dark:text-gray-300">Weitere Gruppen</span>
                                            <p class="text-xs text-gray-400">Komma-getrennte Gruppennamen</p>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <select x-model="mapping.gruppen" @change="loadGroupPreview()"
                                                    class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-1.5">
                                                <option value="">— nicht vorhanden —</option>
                                                <template x-for="(h, i) in headers" :key="i">
                                                    <option :value="i + 1" x-text="(i+1) + ': ' + (h || '(leer)')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 hidden sm:table-cell text-xs text-gray-500" x-text="mapping.gruppen ? headers[mapping.gruppen - 1] || '—' : '—'"></td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                        {{-- Neue Gruppen aus der Gruppen-Spalte auswählen --}}
                        <div x-show="mapping.gruppen" class="mt-4 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide flex items-center justify-between">
                                <span>Gruppen aus der Datei</span>
                                <span x-show="groupPreviewLoading" class="normal-case font-normal text-gray-400">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>wird geprüft…
                                </span>
                            </div>
                            <div class="p-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                    Wählen Sie aus, welche der folgenden Gruppen (sofern noch nicht vorhanden) als
                                    <strong>globale Gruppe</strong> angelegt werden sollen. Nicht ausgewählte, noch
                                    nicht existierende Gruppen werden beim Import übersprungen.
                                </p>
                                <div x-show="!groupPreviewLoading && groupPreview.length === 0" class="text-xs text-gray-400">
                                    Keine Gruppennamen in der zugeordneten Spalte gefunden.
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="group in groupPreview" :key="group.name">
                                        <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs cursor-pointer"
                                               :class="group.exists
                                                    ? 'border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-500'
                                                    : (selectedNewGroups.includes(group.name)
                                                        ? 'border-primary bg-primary/5 text-gray-800 dark:text-gray-100'
                                                        : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300')">
                                            <input type="checkbox" class="rounded"
                                                   x-show="!group.exists"
                                                   :checked="selectedNewGroups.includes(group.name)"
                                                   @change="toggleNewGroup(group.name)">
                                            <i class="fas fa-check text-green-500" x-show="group.exists"></i>
                                            <span x-text="group.name"></span>
                                            <span x-show="group.exists" class="text-gray-400">(vorhanden)</span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>


                        {{-- Validation summary --}}
                        <div x-show="!requiredMappingsValid && headers.length > 0"
                             class="mt-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-xs text-red-700 dark:text-red-300">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Bitte ordnen Sie alle Pflichtfelder zu, bevor Sie fortfahren.
                        </div>
                        <div x-show="requiredMappingsValid && headers.length > 0"
                             class="mt-3 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-xs text-green-700 dark:text-green-300">
                            <i class="fas fa-check-circle mr-1"></i>
                            Alle Pflichtfelder sind zugeordnet. Sie können fortfahren.
                        </div>
                    </div>

                    {{-- Mitarbeiter info box --}}
                    <div x-show="fileName && importTyp === 'mitarbeiter'" x-transition.opacity
                         class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-sm text-blue-800 dark:text-blue-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Mitarbeiter-Import:</strong> Die Datei muss die Spaltenüberschriften
                        <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">e_mail</code>,
                        <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">vorname</code> und
                        <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">nachname</code> enthalten. Keine weitere Konfiguration erforderlich.
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="step = 1"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </button>
                        <button type="button" @click="step = 3"
                                :disabled="!canProceedStep2"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg font-medium text-sm transition-all text-white"
                                :class="canProceedStep2 ? 'hover:opacity-90 cursor-pointer' : 'bg-gray-200 !text-gray-400 cursor-not-allowed'"
                                :style="canProceedStep2 ? 'background-color: var(--primary)' : ''">
                            Weiter <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ═══ STEP 3: Optionen & Import ══════════════════════════════════ --}}
                <div x-show="step === 3">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-1">Optionen & Zusammenfassung</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Überprüfen Sie alle Einstellungen, bevor Sie den Import starten.</p>

                    {{-- Summary --}}
                    <div class="mb-5 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Zusammenfassung
                        </div>
                        <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div class="flex px-4 py-3 gap-4">
                                <dt class="text-sm text-gray-500 dark:text-gray-400 w-40 flex-shrink-0">Import-Typ</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                    <span x-show="importTyp === 'eltern'"><i class="fas fa-users mr-1 text-primary"></i> Eltern-Import</span>
                                    <span x-show="importTyp === 'aufnahme'"><i class="fas fa-user-plus mr-1 text-green-500"></i> Aufnahme-Import</span>
                                    <span x-show="importTyp === 'mitarbeiter'"><i class="fas fa-chalkboard-teacher mr-1 text-gray-500"></i> Mitarbeiter-Import</span>
                                </dd>
                            </div>
                            <div class="flex px-4 py-3 gap-4">
                                <dt class="text-sm text-gray-500 dark:text-gray-400 w-40 flex-shrink-0">Datei</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="fileName"></dd>
                            </div>
                            <template x-if="importTyp !== 'mitarbeiter' && headers.length > 0">
                                <div class="flex px-4 py-3 gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400 w-40 flex-shrink-0">Spalten</dt>
                                    <dd class="text-sm text-gray-800 dark:text-gray-200">
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-if="mapping.s1Vorname">
                                                <span class="px-2 py-0.5 rounded text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"
                                                      x-text="'Vorname: ' + (headers[mapping.s1Vorname - 1] || 'Sp. ' + mapping.s1Vorname)"></span>
                                            </template>
                                            <template x-if="mapping.s1Email">
                                                <span class="px-2 py-0.5 rounded text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"
                                                      x-text="'E-Mail: ' + (headers[mapping.s1Email - 1] || 'Sp. ' + mapping.s1Email)"></span>
                                            </template>
                                            <template x-if="mapping.kindVorname && mapping.kindNachname">
                                                <span class="px-2 py-0.5 rounded text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                                    <i class="fas fa-child mr-1"></i>Kind-Verknüpfung aktiv
                                                </span>
                                            </template>
                                            <template x-if="mapping.s2Email">
                                                <span class="px-2 py-0.5 rounded text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                                    2. Sorgeberechtigte/r
                                                </span>
                                            </template>
                                        </div>
                                    </dd>
                                </div>
                            </template>
                            <template x-if="selectedNewGroups.length > 0">
                                <div class="flex px-4 py-3 gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400 w-40 flex-shrink-0">Neue globale Gruppen</dt>
                                    <dd class="text-sm text-gray-800 dark:text-gray-200">
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="name in selectedNewGroups" :key="name">
                                                <span class="px-2 py-0.5 rounded text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300"
                                                      x-text="name"></span>
                                            </template>
                                        </div>
                                    </dd>
                                </div>
                            </template>
                        </dl>
                    </div>

                    {{-- Zugangsdaten Option --}}
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Zugangsdaten für neue Benutzer
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                   :class="sendMode === 'email' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700 hover:border-primary/30'">
                                <input type="radio" x-model="sendMode" value="email" class="mt-0.5 text-primary">
                                <div>
                                    <div class="font-medium text-sm text-gray-800 dark:text-gray-200">
                                        <i class="fas fa-envelope mr-1.5 text-primary"></i>Per E-Mail versenden
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Neue Benutzer erhalten automatisch eine E-Mail mit ihren Zugangsdaten.</div>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                   :class="sendMode === 'pdf' ? 'border-orange-400 bg-orange-50 dark:bg-orange-900/10' : 'border-gray-200 dark:border-gray-700 hover:border-orange-300'">
                                <input type="radio" x-model="sendMode" value="pdf" class="mt-0.5">
                                <div>
                                    <div class="font-medium text-sm text-gray-800 dark:text-gray-200">
                                        <i class="fas fa-file-pdf mr-1.5 text-orange-500"></i>Als PDF (kein Mailversand an neue Benutzer)
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Neue Benutzer erhalten <strong>keine</strong> E-Mail. Stattdessen wird nach dem Import ein PDF mit den
                                        Zugangsdaten aller <em>neuen</em> Benutzer heruntergeladen und zusätzlich an Ihre eigene E-Mail-Adresse versendet.
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Eltern danger confirmation --}}
                    <div x-show="importTyp === 'eltern'" class="mb-5">
                        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800">
                            <div class="flex gap-3 mb-3">
                                <i class="fas fa-exclamation-triangle text-red-600 text-lg mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <h4 class="font-semibold text-red-800 dark:text-red-300 text-sm">Wichtige Sicherheitsabfrage</h4>
                                    <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                                        Beim Eltern-Import werden <strong>alle nicht-geschützten Gruppenverknüpfungen</strong> aller Benutzer gelöscht und neu aufgebaut. Dieser Vorgang kann nicht rückgängig gemacht werden.
                                    </p>
                                </div>
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="confirmed" class="w-4 h-4 rounded border-red-400 text-red-600">
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">
                                    Ich verstehe und bestätige, dass alle Gruppenverknüpfungen vor dem Import geleert werden.
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="step = 2" :disabled="submitting"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </button>
                        <button type="submit"
                                :disabled="!canSubmit || submitting"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-semibold text-sm transition-all"
                                :class="(canSubmit && !submitting) ? 'bg-green-600 text-white hover:bg-green-700 shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
                            <svg x-show="submitting" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <i x-show="!submitting" class="fas fa-rocket"></i>
                            <span x-text="submitting ? 'Import läuft…' : 'Import starten'"></span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function importWizard() {
    return {
        step: 1,
        importTyp: '',
        fileName: '',
        headers: [],
        loading: false,
        sendMode: 'email',
        confirmed: false,
        submitting: false,
        currentFile: null,
        groupPreview: [],
        selectedNewGroups: [],
        groupPreviewLoading: false,
        mapping: {
            klassenstufe: '',
            lerngruppe:   '',
            gruppen:      '',
            s1Vorname:    '',
            s1Nachname:   '',
            s1Email:      '',
            s2Vorname:    '',
            s2Nachname:   '',
            s2Email:      '',
            kindVorname:  '',
            kindNachname: '',
        },

        get requiredMappingsValid() {
            if (this.importTyp === 'eltern') {
                return this.mapping.klassenstufe && this.mapping.lerngruppe
                    && this.mapping.s1Vorname && this.mapping.s1Nachname && this.mapping.s1Email;
            }
            if (this.importTyp === 'aufnahme') {
                return this.mapping.s1Vorname && this.mapping.s1Nachname && this.mapping.s1Email;
            }
            return true;
        },

        get canProceedStep2() {
            if (!this.fileName) return false;
            if (this.importTyp === 'mitarbeiter') return true;
            return this.headers.length > 0 && this.requiredMappingsValid;
        },

        get canSubmit() {
            if (this.importTyp === 'eltern') return this.confirmed;
            return true;
        },

        autoDetect(headers) {
            const patterns = {
                klassenstufe: [/klass.*stufe/i, /jahrgangsstufe/i, /jahrgang/i, /stufe/i],
                lerngruppe:   [/lerngruppe/i, /^klasse$/i, /klassen/i, /^kl\b/i],
                gruppen:      [/^gruppen$/i, /gruppen.*liste/i, /listen/i],
                s1Vorname:    [/vorname.*1/i, /s1.*vorname/i, /1.*vorname/i, /erz.*1.*vorname/i, /^vorname$/i],
                s1Nachname:   [/nachname.*1/i, /s1.*nachname/i, /1.*nachname/i, /erz.*1.*nachname/i, /^nachname$/i],
                s1Email:      [/mail.*1/i, /s1.*mail/i, /1.*mail/i, /^e.?mail$/i, /^mail$/i],
                s2Vorname:    [/vorname.*2/i, /s2.*vorname/i, /2.*vorname/i, /erz.*2.*vorname/i],
                s2Nachname:   [/nachname.*2/i, /s2.*nachname/i, /2.*nachname/i, /erz.*2.*nachname/i],
                s2Email:      [/mail.*2/i, /s2.*mail/i, /2.*mail/i],
                kindVorname:  [/kind.*vorname/i, /vorname.*kind/i, /schueler.*vorname/i, /sch.*vorname/i, /vorname.*sch/i],
                kindNachname: [/kind.*nachname/i, /nachname.*kind/i, /schueler.*nachname/i, /sch.*nachname/i],
            };

            headers.forEach((header, index) => {
                if (!header) return;
                Object.entries(patterns).forEach(([field, pats]) => {
                    if (!this.mapping[field]) {
                        for (const pat of pats) {
                            if (pat.test(header)) {
                                this.mapping[field] = index + 1;
                                break;
                            }
                        }
                    }
                });
            });
        },

        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.fileName = file.name;
            this.currentFile = file;
            this.groupPreview = [];
            this.selectedNewGroups = [];

            if (this.importTyp === 'mitarbeiter') return;

            this.loading = true;
            this.headers = [];
            Object.keys(this.mapping).forEach(k => this.mapping[k] = '');

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            try {
                const response = await fetch('{{ route("users.import.headers") }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    throw new Error(err.error || 'HTTP ' + response.status);
                }
                const data = await response.json();
                this.headers = data.headers;
                this.autoDetect(data.headers);
                if (this.mapping.gruppen) {
                    this.loadGroupPreview();
                }
            } catch (e) {
                console.error('Header-Erkennung fehlgeschlagen:', e);
                alert('Fehler beim Lesen der Datei: ' + e.message + '\nBitte prüfen Sie das Dateiformat.');
                this.fileName = '';
                event.target.value = '';
            } finally {
                this.loading = false;
            }
        },

        toggleNewGroup(name) {
            const idx = this.selectedNewGroups.indexOf(name);
            if (idx === -1) {
                this.selectedNewGroups.push(name);
            } else {
                this.selectedNewGroups.splice(idx, 1);
            }
        },

        async loadGroupPreview() {
            this.groupPreview = [];
            if (!this.mapping.gruppen || !this.currentFile) {
                this.selectedNewGroups = [];
                return;
            }

            this.groupPreviewLoading = true;
            const formData = new FormData();
            formData.append('file', this.currentFile);
            formData.append('gruppen_column', this.mapping.gruppen);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            try {
                const response = await fetch('{{ route("users.import.groups") }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    throw new Error(err.error || 'HTTP ' + response.status);
                }
                const data = await response.json();
                this.groupPreview = data.groups || [];
                // Neue Gruppen sind standardmäßig zur Anlage vorausgewählt.
                this.selectedNewGroups = this.groupPreview.filter(g => !g.exists).map(g => g.name);
            } catch (e) {
                console.error('Gruppen-Erkennung fehlgeschlagen:', e);
            } finally {
                this.groupPreviewLoading = false;
            }
        },
    };
}
</script>
@endpush
