@extends('layouts.app')
@section('title') - Vereinsmitglieder importieren @endsection

@php
    $preview = $report->dryRun && $token;
    $sections = [
        [
            'key' => 'created', 'open' => true,
            'title' => $report->dryRun ? 'Neue Konten anlegen' : 'Neue Konten angelegt',
            'hint' => 'Keine passende E-Mail-Adresse und kein gleichnamiges Konto gefunden.',
            'icon' => 'fa-user-plus', 'tone' => 'blue',
        ],
        [
            'key' => 'linked', 'open' => true,
            'title' => $report->dryRun ? 'Bestehende Konten in den Verein aufnehmen' : 'Bestehende Konten in den Verein aufgenommen',
            'hint' => 'Über die E-Mail-Adresse erkannt – nur Gruppe und Rolle „Vereinsmitglied“ kommen hinzu.'.($options['notify'] ? ' Die Konten erhalten eine Benachrichtigung in der Anwendung.' : ''),
            'icon' => 'fa-link', 'tone' => 'green',
        ],
        [
            'key' => 'completed', 'open' => false,
            'title' => $report->dryRun ? 'Gruppe oder Rolle ergänzen' : 'Gruppe oder Rolle ergänzt',
            'hint' => 'Bereits Mitglied, aber Gruppe oder Rolle fehlte.',
            'icon' => 'fa-wrench', 'tone' => 'teal',
        ],
        [
            'key' => 'removed', 'open' => true,
            'title' => $report->dryRun ? 'Aus dem Verein entfernen' : 'Aus dem Verein entfernt',
            'hint' => 'Nicht mehr in der Datei. Gruppe und Rolle „Vereinsmitglied“ werden entfernt, das Konto bleibt bestehen. Abgewählte Personen bleiben Mitglied.'.($options['notify'] ? ' Die Konten erhalten eine Benachrichtigung in der Anwendung.' : ''),
            'icon' => 'fa-user-minus', 'tone' => 'red',
        ],
    ];
    $tones = [
        'blue'  => ['icon' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',   'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200'],
        'green' => ['icon' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300', 'badge' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'],
        'teal'  => ['icon' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',   'badge' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200'],
        'red'   => ['icon' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',       'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'],
    ];
    $changes = count($report->created) + count($report->linked) + count($report->completed) + count($report->removed);
    $btnSecondary = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 no-underline whitespace-nowrap';
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-2 sm:px-0"
     x-data="{
        selected: 0,
        decided: 0,
        recount() {
            this.selected = document.querySelectorAll('input[form=vereinConfirm][name=\'selected[]\']:checked').length;
            this.decided = [...document.querySelectorAll('input[form=vereinConfirm][type=radio]:checked')].filter(r => r.value !== 'skip').length;
        }
     }"
     x-init="recount()" @change="recount()">

    {{-- Kopfbereich --}}
    <div class="mb-6">
        <a href="{{ url('users/importVerein') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 no-underline mb-3">
            <i class="fas fa-arrow-left text-xs"></i> Vereinsmitglieder importieren
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 leading-tight m-0">
                {{ $report->dryRun ? 'Vorschau des Imports' : 'Import abgeschlossen' }}
            </h1>
            @if($report->dryRun)
                <span class="inline-flex items-center gap-2 self-start px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Noch nichts gespeichert
                </span>
            @else
                <span class="inline-flex items-center gap-2 self-start px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">
                    <i class="fas fa-check"></i> Änderungen gespeichert
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 mb-0">
            {{ $report->rows }} {{ $report->rows === 1 ? 'Zeile' : 'Zeilen' }} gelesen ·
            Abgleich {{ $options['removeMissing'] ? 'eingeschaltet' : 'ausgeschaltet' }} ·
            Zugangsdaten {{ $options['sendEmail'] ? 'per E-Mail' : 'als PDF' }} ·
            Benachrichtigung {{ $options['notify'] ? 'eingeschaltet' : 'ausgeschaltet' }} ·
            nur Gruppe und Rolle „Vereinsmitglied“ {{ $report->dryRun ? 'werden' : 'wurden' }} geändert
        </p>
        @if($preview)
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-3 mb-0">
                <i class="fas fa-hand-pointer mr-1 text-gray-400"></i>
                Jede Änderung lässt sich einzeln oder je Gruppe an- und abwählen. Für Klärungsfälle können Sie direkt eine Lösung wählen.
            </p>
        @endif
    </div>

    {{-- Kennzahlen --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach([
            ['Mitglieder danach', $report->membersAfter, 'text-gray-900 dark:text-gray-100'],
            ['Unverändert', $report->unchanged, 'text-gray-900 dark:text-gray-100'],
            ['Verknüpft', count($report->linked) + count($report->completed), 'text-green-700 dark:text-green-400'],
            ['Neue Konten', count($report->created), 'text-blue-700 dark:text-blue-400'],
            ['Entfernt', count($report->removed), 'text-red-700 dark:text-red-400'],
            [$report->dryRun ? 'Klärungsfälle' : 'Nicht zugeordnet', $report->dryRun ? count($report->cases) : count($report->unassigned), 'text-amber-700 dark:text-amber-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl font-semibold tabular-nums mt-1 {{ $value > 0 ? $color : 'text-gray-300 dark:text-gray-600' }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>
    @if($preview)
        <p class="text-xs text-gray-500 dark:text-gray-400 -mt-4 mb-6">Die Kennzahlen gehen davon aus, dass alle Änderungen übernommen werden.</p>
    @endif

    @if(! $options['removeMissing'] && $report->kept > 0)
        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl border bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 text-sm">
            <i class="fas fa-info-circle mt-0.5"></i>
            <span>{{ $report->kept }} bisherige Mitglieder stehen nicht in der Datei und bleiben Mitglied, weil der Abgleich ausgeschaltet ist.</span>
        </div>
    @endif

    <div class="space-y-4">

        {{-- Klärungsfälle mit Entscheidung --}}
        @if($report->cases !== [])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-300 dark:border-amber-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-amber-50 dark:bg-amber-900/20 flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-amber-600 mt-1"></i>
                    <div>
                        <h2 class="text-base font-semibold text-amber-900 dark:text-amber-200 m-0">
                            {{ count($report->cases) }} {{ count($report->cases) === 1 ? 'Klärungsfall' : 'Klärungsfälle' }}
                        </h2>
                        <p class="text-sm text-amber-800 dark:text-amber-300 mt-1 mb-0">
                            Diese Zeilen konnten nicht eindeutig zugeordnet werden. Wählen Sie je Fall eine Lösung – ohne Auswahl wird die Zeile nicht übernommen
                            und erscheint in der Auswertung.
                        </p>
                    </div>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 list-none m-0 p-0">
                    @foreach($report->cases as $case)
                        <li class="px-5 py-4 grid grid-cols-1 md:grid-cols-5 gap-4" x-data="{ choice: 'skip' }">
                            <div class="md:col-span-2 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded-md text-xs font-mono bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Zeile {{ $case['row'] }}</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 break-words">{{ $case['name'] }}</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 break-all">{{ $case['rawEmail'] !== '' ? $case['rawEmail'] : 'keine E-Mail-Adresse in der Datei' }}</div>
                                <div class="text-sm text-gray-700 dark:text-gray-300 mt-2">{{ $case['message'] }}</div>
                            </div>
                            <fieldset class="md:col-span-3 space-y-1.5 m-0 p-0 border-0">
                                <legend class="sr-only">Lösung für Zeile {{ $case['row'] }}</legend>
                                @php
                                    $choices = array_merge($case['options'], [['value' => 'email', 'label' => 'Anderem Konto per E-Mail-Adresse zuordnen']]);
                                @endphp
                                @foreach($choices as $option)
                                    <label class="flex items-start gap-2.5 px-3 py-2 rounded-lg border cursor-pointer text-sm mb-0 transition-colors"
                                           :class="choice === @js($option['value']) ? 'border-amber-400 bg-amber-50/60 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                                        <input type="radio" form="vereinConfirm" name="decision[{{ $case['row'] }}]" value="{{ $option['value'] }}" x-model="choice" class="mt-0.5">
                                        <span class="text-gray-800 dark:text-gray-200 break-words">{{ $option['label'] }}</span>
                                    </label>
                                @endforeach
                                <div x-show="choice === 'email'" x-cloak class="pl-8">
                                    <input type="email" form="vereinConfirm" name="decision_email[{{ $case['row'] }}]" placeholder="E-Mail-Adresse des bestehenden Kontos"
                                           class="w-full px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                </div>
                                <label class="flex items-start gap-2.5 px-3 py-2 rounded-lg border cursor-pointer text-sm mb-0 transition-colors"
                                       :class="choice === 'skip' ? 'border-gray-400 bg-gray-50 dark:bg-gray-700/40' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                                    <input type="radio" form="vereinConfirm" name="decision[{{ $case['row'] }}]" value="skip" x-model="choice" class="mt-0.5">
                                    <span class="text-gray-600 dark:text-gray-300">Nicht übernehmen (in der Auswertung aufführen)</span>
                                </label>
                            </fieldset>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Entschiedene Klärungsfälle (Ergebnis) --}}
        @if($report->resolved !== [])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 flex items-center gap-3">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"><i class="fas fa-check-double"></i></span>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 m-0">Gelöste Klärungsfälle</h2>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 list-none m-0 p-0 border-t border-gray-100 dark:border-gray-700">
                    @foreach($report->resolved as $item)
                        <li class="px-5 py-2.5 text-sm flex flex-wrap gap-x-3">
                            <span class="font-mono text-xs text-gray-500 mt-0.5">Zeile {{ $item['row'] }}</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $item['action'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Änderungen --}}
        @foreach($sections as $section)
            @php $entries = $report->{$section['key']}; $tone = $tones[$section['tone']]; @endphp
            @continue($entries === [])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
                 x-data="{
                    open: {{ $section['open'] ? 'true' : 'false' }},
                    q: '',
                    boxes() { return [...$el.querySelectorAll('input[name=\'selected[]\']')]; },
                    sync() {
                        if (! $refs.all) return;
                        const n = this.boxes().filter(b => b.checked).length;
                        $refs.all.checked = n === this.boxes().length;
                        $refs.all.indeterminate = n > 0 && n < this.boxes().length;
                        $refs.count.textContent = n + ' / ' + this.boxes().length;
                    },
                    toggleAll(on) { this.boxes().forEach(b => b.checked = on); this.sync(); }
                 }"
                 x-init="sync()" @change="sync()">
                <div class="px-5 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                    @if($preview)
                        <input type="checkbox" x-ref="all" @change="toggleAll($event.target.checked)" class="w-4 h-4 rounded flex-shrink-0"
                               title="Alle in dieser Gruppe an- oder abwählen" aria-label="Alle in „{{ $section['title'] }}“ auswählen">
                    @endif
                    <button type="button" @click="open = !open" class="flex-1 min-w-0 flex items-center gap-4 text-left bg-transparent border-0 p-0">
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $tone['icon'] }}">
                            <i class="fas {{ $section['icon'] }}"></i>
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-base font-semibold text-gray-900 dark:text-gray-100">{{ $section['title'] }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $section['hint'] }}</span>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-sm font-semibold tabular-nums whitespace-nowrap {{ $tone['badge'] }}">
                            @if($preview)<span x-ref="count">{{ count($entries) }}</span>@else{{ count($entries) }}@endif
                        </span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                </div>
                <div x-show="open" x-cloak class="border-t border-gray-100 dark:border-gray-700">
                    @if(count($entries) > 10)
                        <div class="px-5 pt-3">
                            <input type="search" x-model="q" placeholder="Name oder E-Mail suchen…"
                                   class="w-full sm:w-72 px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    @if($preview)<th class="px-5 py-2 w-10"><span class="sr-only">Übernehmen</span></th>@endif
                                    <th class="text-left font-medium px-5 py-2 w-16">Zeile</th>
                                    <th class="text-left font-medium px-5 py-2">Name</th>
                                    <th class="text-left font-medium px-5 py-2">E-Mail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($entries as $entry)
                                    @php $id = 'sel-'.md5($entry['key']); @endphp
                                    <tr x-show="!q || {{ \Illuminate\Support\Js::from(mb_strtolower($entry['name'].' '.$entry['email'])) }}.includes(q.toLowerCase())"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        @if($preview)
                                            <td class="px-5 py-2">
                                                <input type="checkbox" id="{{ $id }}" form="vereinConfirm" name="selected[]" value="{{ $entry['key'] }}" checked class="w-4 h-4 rounded">
                                            </td>
                                        @endif
                                        <td class="px-5 py-2 font-mono text-xs text-gray-500">{{ $entry['row'] ?? '–' }}</td>
                                        <td class="px-5 py-2 text-gray-900 dark:text-gray-100">
                                            @if($preview)<label for="{{ $id }}" class="mb-0 cursor-pointer">{{ $entry['name'] }}</label>@else{{ $entry['name'] }}@endif
                                        </td>
                                        <td class="px-5 py-2 text-gray-600 dark:text-gray-400 break-all">{{ $entry['email'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        @if($changes === 0 && $report->cases === [] && $report->resolved === [])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-6 py-10 text-center">
                <i class="fas fa-check-circle text-3xl text-green-500 mb-3"></i>
                <p class="text-base font-medium text-gray-900 dark:text-gray-100 m-0">Keine Änderungen</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-0">Die Mitgliederliste stimmt mit den Konten überein.</p>
            </div>
        @endif

        {{-- Hinweise --}}
        @if($report->notes !== [])
            <details class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <summary class="px-5 py-3 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-200">
                    <i class="fas fa-info-circle mr-1 text-gray-400"></i> {{ count($report->notes) }} {{ count($report->notes) === 1 ? 'Hinweis' : 'Hinweise' }} zur Datei
                </summary>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 list-none m-0 p-0 border-t border-gray-100 dark:border-gray-700">
                    @foreach($report->notes as $note)
                        <li class="px-5 py-2.5 text-sm flex flex-wrap gap-x-3">
                            <span class="font-mono text-xs text-gray-500 mt-0.5">Zeile {{ $note['row'] }}</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $note['person'] }}</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $note['message'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        {{-- Auswertung --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200"><i class="fas fa-clipboard-list"></i></span>
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 m-0">Auswertung</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mb-0">
                        Personen ohne Zuordnung und Mitglieder, die über das System voraussichtlich nicht erreicht werden.
                        @if($report->dryRun) Stand der Vorschau – nach der Bestätigung steht die endgültige Auswertung bereit. @endif
                    </p>
                </div>
                <a href="{{ route('users.import.verein.auswertung', $evaluation) }}" class="{{ $btnSecondary }}">
                    <i class="fas fa-file-excel text-green-600"></i> Als Excel exportieren
                </a>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700 border-t border-gray-100 dark:border-gray-700">
                @foreach([
                    ['Nicht zugeordnet', $report->unassigned, 'fa-user-times', 'Alle Zeilen konnten zugeordnet werden.'],
                    ['Nicht erreichbar', $report->unreachable, 'fa-bell-slash', 'Alle Mitglieder sind über das System erreichbar.'],
                ] as [$title, $items, $icon, $empty])
                    <div class="bg-white dark:bg-gray-800 p-5">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                            <i class="fas {{ $icon }} text-gray-400"></i> {{ $title }}
                            <span class="ml-auto px-2 py-0.5 rounded-full text-xs font-semibold {{ count($items) ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">{{ count($items) }}</span>
                        </h3>
                        @forelse($items as $item)
                            @if($loop->index === 8)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 mb-0">… und {{ count($items) - 8 }} weitere im Export.</p>
                                @break
                            @endif
                            <div class="py-1.5 text-sm border-b border-gray-50 dark:border-gray-700/60 last:border-0">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</span>
                                @if($item['email'] !== '')<span class="text-gray-500 dark:text-gray-400 break-all"> · {{ $item['email'] }}</span>@endif
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['reason'] }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 m-0"><i class="fas fa-check text-green-500 mr-1"></i>{{ $empty }}</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Aktionsleiste --}}
    <div class="mt-6 md:sticky md:bottom-4 z-20 bg-white/95 dark:bg-gray-800/95 backdrop-blur rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg">
        <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            @if($preview)
                <p class="text-sm text-gray-600 dark:text-gray-300 m-0">
                    <strong x-text="selected">{{ $changes }}</strong> von {{ $changes }} {{ $changes === 1 ? 'Änderung' : 'Änderungen' }} ausgewählt
                    @if($report->cases !== [])
                        · <span class="text-amber-700 dark:text-amber-400"><span x-text="decided">0</span> von {{ count($report->cases) }} Klärungsfällen gelöst</span>
                    @endif
                </p>
                <div class="flex gap-2" x-data="{ submitting: false }">
                    <a href="{{ url('users/importVerein') }}" class="{{ $btnSecondary }}">Abbrechen</a>
                    <form id="vereinConfirm" action="{{ route('users.import.verein.confirm') }}" method="POST" class="m-0"
                          @submit="if (! confirm('Import mit der getroffenen Auswahl jetzt ausführen?')) { $event.preventDefault(); return; } submitting = true">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="abgleich" value="{{ $options['removeMissing'] ? 1 : 0 }}">
                        <input type="hidden" name="send_email" value="{{ $options['sendEmail'] ? 1 : 0 }}">
                        <input type="hidden" name="benachrichtigen" value="{{ $options['notify'] ? 1 : 0 }}">
                        <input type="hidden" name="selection_sent" value="1">
                        <button type="submit" :disabled="submitting"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold bg-green-600 text-white hover:bg-green-700 disabled:opacity-60 whitespace-nowrap">
                            <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            Auswahl übernehmen
                        </button>
                    </form>
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-300 m-0">
                    Der Import wurde ausgeführt.
                    @if($report->notifiedAdded + $report->notifiedRemoved > 0)
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $report->notifiedAdded + $report->notifiedRemoved }} {{ $report->notifiedAdded + $report->notifiedRemoved === 1 ? 'Benachrichtigung' : 'Benachrichtigungen' }} versendet
                            ({{ $report->notifiedAdded }} Aufnahme, {{ $report->notifiedRemoved }} Entfernung).
                        </span>
                    @endif
                </p>
                <div class="flex flex-wrap gap-2">
                    @if($hasPdf ?? false)
                        <a href="{{ route('users.import.verein.zugangsdaten', $evaluation) }}" class="{{ $btnSecondary }}">
                            <i class="fas fa-file-pdf text-red-600"></i> Zugangsdaten (PDF)
                        </a>
                    @endif
                    <a href="{{ route('users.import.verein.auswertung', $evaluation) }}" class="{{ $btnSecondary }}">
                        <i class="fas fa-file-excel text-green-600"></i> Auswertung
                    </a>
                    <a href="{{ url('users') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold bg-gray-900 text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 no-underline whitespace-nowrap">
                        Zu den Benutzerkonten
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
