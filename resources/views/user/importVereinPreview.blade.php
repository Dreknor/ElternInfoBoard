@extends('layouts.app')
@section('title') - Vereinsmitglieder importieren @endsection

@php
    $sections = [
        [
            'key' => 'created', 'open' => true,
            'title' => $report->dryRun ? 'Neue Konten werden angelegt' : 'Neue Konten angelegt',
            'hint' => 'Keine passende E-Mail-Adresse und kein gleichnamiges Konto gefunden.',
            'icon' => 'fa-user-plus', 'tone' => 'blue',
        ],
        [
            'key' => 'linked', 'open' => true,
            'title' => $report->dryRun ? 'Bestehende Konten werden Vereinsmitglied' : 'Bestehende Konten sind jetzt Vereinsmitglied',
            'hint' => 'Über die E-Mail-Adresse erkannt und verknüpft – nur Gruppe und Rolle „Vereinsmitglied“ kommen hinzu.',
            'icon' => 'fa-link', 'tone' => 'green',
        ],
        [
            'key' => 'completed', 'open' => false,
            'title' => $report->dryRun ? 'Gruppe oder Rolle wird ergänzt' : 'Gruppe oder Rolle ergänzt',
            'hint' => 'Bereits Mitglied, aber Gruppe oder Rolle fehlte.',
            'icon' => 'fa-wrench', 'tone' => 'teal',
        ],
        [
            'key' => 'removed', 'open' => true,
            'title' => $report->dryRun ? 'Werden aus dem Verein entfernt' : 'Aus dem Verein entfernt',
            'hint' => 'Nicht mehr in der Datei. Gruppe und Rolle „Vereinsmitglied“ werden entfernt, das Konto bleibt bestehen.',
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
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-2 sm:px-0">

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
            nur Gruppe und Rolle „Vereinsmitglied“ {{ $report->dryRun ? 'werden' : 'wurden' }} geändert
        </p>
    </div>

    {{-- Kennzahlen --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach([
            ['Mitglieder danach', $report->membersAfter(), 'text-gray-900 dark:text-gray-100'],
            ['Unverändert', $report->unchanged, 'text-gray-900 dark:text-gray-100'],
            ['Verknüpft', count($report->linked) + count($report->completed), 'text-green-700 dark:text-green-400'],
            ['Neue Konten', count($report->created), 'text-blue-700 dark:text-blue-400'],
            ['Entfernt', count($report->removed), 'text-red-700 dark:text-red-400'],
            ['Klärungsfälle', count($report->review), 'text-amber-700 dark:text-amber-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl font-semibold tabular-nums mt-1 {{ $value > 0 ? $color : 'text-gray-300 dark:text-gray-600' }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    @if(! $options['removeMissing'] && $report->kept > 0)
        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl border bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 text-sm">
            <i class="fas fa-info-circle mt-0.5"></i>
            <span>{{ $report->kept }} bisherige Mitglieder stehen nicht in der Datei und bleiben Mitglied, weil der Abgleich ausgeschaltet ist.</span>
        </div>
    @endif

    <div class="space-y-4">

        {{-- Klärungsfälle --}}
        @if($report->review !== [])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-300 dark:border-amber-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-amber-50 dark:bg-amber-900/20 flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-amber-600 mt-1"></i>
                    <div>
                        <h2 class="text-base font-semibold text-amber-900 dark:text-amber-200 m-0">
                            {{ count($report->review) }} {{ count($report->review) === 1 ? 'Klärungsfall' : 'Klärungsfälle' }}
                        </h2>
                        <p class="text-sm text-amber-800 dark:text-amber-300 mt-1 mb-0">
                            Diese Zeilen werden nicht automatisch übernommen. Bitte die Datei oder das betroffene Konto prüfen und den Import danach erneut ausführen.
                        </p>
                    </div>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 list-none m-0 p-0">
                    @foreach($report->review as $case)
                        <li class="px-5 py-3 flex items-start gap-4">
                            <span class="flex-shrink-0 mt-0.5 px-2 py-0.5 rounded-md text-xs font-mono bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Zeile {{ $case['row'] }}</span>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 break-words">{{ $case['person'] }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 break-words">{{ $case['message'] }}</div>
                            </div>
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
                 x-data="{ open: {{ $section['open'] ? 'true' : 'false' }}, q: '' }">
                <button type="button" @click="open = !open"
                        class="w-full px-5 py-4 flex items-center gap-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $tone['icon'] }}">
                        <i class="fas {{ $section['icon'] }}"></i>
                    </span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-base font-semibold text-gray-900 dark:text-gray-100">{{ $section['title'] }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $section['hint'] }}</span>
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-sm font-semibold tabular-nums {{ $tone['badge'] }}">{{ count($entries) }}</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
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
                                    <th class="text-left font-medium px-5 py-2 w-1/2">Name</th>
                                    <th class="text-left font-medium px-5 py-2 w-1/2">E-Mail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($entries as $entry)
                                    <tr x-show="!q || {{ \Illuminate\Support\Js::from(mb_strtolower($entry['name'].' '.$entry['email'])) }}.includes(q.toLowerCase())">
                                        <td class="px-5 py-2 text-gray-900 dark:text-gray-100">{{ $entry['name'] }}</td>
                                        <td class="px-5 py-2 text-gray-600 dark:text-gray-400 break-all">{{ $entry['email'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        @if($changes === 0 && $report->review === [])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-6 py-10 text-center">
                <i class="fas fa-check-circle text-3xl text-green-500 mb-3"></i>
                <p class="text-base font-medium text-gray-900 dark:text-gray-100 m-0">Alles aktuell</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-0">Die Mitgliederliste stimmt bereits mit den Konten überein.</p>
            </div>
        @endif
    </div>

    {{-- Aktionsleiste --}}
    <div class="mt-6 md:sticky md:bottom-4 z-20 bg-white/95 dark:bg-gray-800/95 backdrop-blur rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg">
        <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            @if($report->dryRun && $token)
                <p class="text-sm text-gray-600 dark:text-gray-300 m-0">
                    <strong>{{ $changes }}</strong> {{ $changes === 1 ? 'Änderung' : 'Änderungen' }} bereit
                    @if($report->review !== [])
                        · <span class="text-amber-700 dark:text-amber-400">{{ count($report->review) }} {{ count($report->review) === 1 ? 'Klärungsfall wird' : 'Klärungsfälle werden' }} übersprungen</span>
                    @endif
                </p>
                <div class="flex gap-2" x-data="{ submitting: false }">
                    <a href="{{ url('users/importVerein') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 no-underline">
                        Abbrechen
                    </a>
                    <form action="{{ route('users.import.verein.confirm') }}" method="POST" class="m-0"
                          @submit="if (! confirm('Import jetzt ausführen?')) { $event.preventDefault(); return; } submitting = true">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="abgleich" value="{{ $options['removeMissing'] ? 1 : 0 }}">
                        <input type="hidden" name="send_email" value="{{ $options['sendEmail'] ? 1 : 0 }}">
                        <button type="submit" :disabled="submitting"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold bg-green-600 text-white hover:bg-green-700 disabled:opacity-60">
                            <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            Import bestätigen
                        </button>
                    </form>
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-300 m-0">Der Import wurde ausgeführt.</p>
                <div class="flex gap-2">
                    <a href="{{ url('users/vereinsmitglieder/non-members') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 no-underline">
                        Nicht-Vereinsmitglieder
                    </a>
                    <a href="{{ url('users') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold bg-gray-900 text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 no-underline">
                        Zu den Benutzerkonten
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
