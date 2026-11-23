@extends('layouts.app')
@section('title') - Betreuungsumfang @endsection

@php
    $fmt = fn (int $m) => \App\Services\Attendance\AttendanceDay::formatMinutes($m);
    $query = array_filter([
        'from' => $filters['from']->toDateString(),
        'to' => $filters['to']->toDateString(),
        'group_id' => $filters['group_id'],
        'class_id' => $filters['class_id'],
        'child_id' => $filters['child_id'],
    ]);
@endphp

@section('content')
    <div class="container-fluid px-4 py-6">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Betreuungsumfang</h1>
                <p class="text-gray-600 mb-0">Tatsächliche Anwesenheit laut An- und Abmeldungen, minutengenau.</p>
            </div>
            <a href="{{ url('verwaltung/schickzeiten') }}" class="text-sm text-indigo-600 hover:underline">
                <i class="fas fa-arrow-left"></i> Hort-Verwaltung
            </a>
        </div>

        {{-- Filter --}}
        <form method="get" action="{{ route('anwesenheit.auswertung') }}" class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
                <label class="text-sm text-gray-700">Von
                    <input type="date" name="from" value="{{ $filters['from']->toDateString() }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded border-gray-300 text-sm">
                </label>
                <label class="text-sm text-gray-700">Bis
                    <input type="date" name="to" value="{{ $filters['to']->toDateString() }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded border-gray-300 text-sm">
                </label>
                <label class="text-sm text-gray-700">Gruppe
                    <select name="group_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                        <option value="">alle</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected($filters['group_id'] == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm text-gray-700">Klasse
                    <select name="class_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                        <option value="">alle</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected($filters['class_id'] == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm text-gray-700">Kind
                    <select name="child_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                        <option value="">alle</option>
                        @foreach($allChildren as $c)
                            <option value="{{ $c->id }}" @selected($filters['child_id'] == $c->id)>{{ $c->last_name }}, {{ $c->first_name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-3 py-2 rounded bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                        <i class="fas fa-filter"></i> Anzeigen
                    </button>
                    <a href="{{ route('anwesenheit.auswertung.export', $query) }}" class="px-3 py-2 rounded bg-green-600 text-white text-sm font-medium hover:bg-green-700" title="Excel-Export">
                        <i class="fas fa-file-excel"></i>
                    </a>
                </div>
            </div>
        </form>

        {{-- Summen --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs text-gray-500">Gesamt</div>
                <div class="text-xl font-bold text-gray-800">{{ $fmt($totals['minutes_total']) }} h</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs text-gray-500">davon Schulzeit</div>
                <div class="text-xl font-bold text-gray-800">{{ $fmt($totals['minutes_school']) }} h</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs text-gray-500">davon Ferien</div>
                <div class="text-xl font-bold text-gray-800">{{ $fmt($totals['minutes_holiday']) }} h</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-xs text-gray-500">Anwesenheitstage</div>
                <div class="text-xl font-bold text-gray-800">{{ $totals['days_present'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 {{ $totals['incomplete_days'] > 0 ? 'border-l-4 border-amber-500' : '' }}">
                <div class="text-xs text-gray-500">unvollständige Tage</div>
                <div class="text-xl font-bold {{ $totals['incomplete_days'] > 0 ? 'text-amber-600' : 'text-gray-800' }}">{{ $totals['incomplete_days'] }}</div>
            </div>
        </div>

        {{-- Je Kind --}}
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-3 py-2">Kind</th>
                    <th class="px-3 py-2">Gruppe / Klasse</th>
                    <th class="px-3 py-2 text-right">Tage</th>
                    <th class="px-3 py-2 text-right">Gesamt</th>
                    <th class="px-3 py-2 text-right">Schulzeit</th>
                    <th class="px-3 py-2 text-right">Ferien</th>
                    <th class="px-3 py-2 text-right">Ø je Tag</th>
                    <th class="px-3 py-2">Hinweise</th>
                </tr>
                </thead>
                @forelse($rows as $row)
                    @php($s = $row['summary'])
                    <tbody x-data="{ open: false }" class="border-t border-gray-200">
                    <tr class="hover:bg-gray-50 cursor-pointer" @click="open = !open">
                        <td class="px-3 py-2 font-medium text-gray-800">
                            <i class="fas fa-chevron-right text-xs text-gray-400 transition-transform" :class="open && 'rotate-90'"></i>
                            {{ $row['child']->last_name }}, {{ $row['child']->first_name }}
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ $row['child']->group?->name }}{{ $row['child']->class ? ' / '.$row['child']->class->name : '' }}</td>
                        <td class="px-3 py-2 text-right">{{ $s['days_present'] }}</td>
                        <td class="px-3 py-2 text-right font-semibold">{{ $fmt($s['minutes_total']) }}</td>
                        <td class="px-3 py-2 text-right">{{ $fmt($s['minutes_school']) }}</td>
                        <td class="px-3 py-2 text-right">{{ $fmt($s['minutes_holiday']) }}</td>
                        <td class="px-3 py-2 text-right">{{ $s['days_present'] > 0 ? $fmt(intdiv($s['minutes_total'], $s['days_present'])) : '–' }}</td>
                        <td class="px-3 py-2 space-x-1 whitespace-nowrap">
                            @if($s['incomplete_days'])<span class="inline-block px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-xs">{{ $s['incomplete_days'] }} unvollständig</span>@endif
                            @if($s['edited_days'])<span class="inline-block px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-xs">{{ $s['edited_days'] }} bearbeitet</span>@endif
                            @if($s['sick_days'])<span class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs">{{ $s['sick_days'] }} krank</span>@endif
                        </td>
                    </tr>
                    <tr x-show="open" x-cloak>
                        <td colspan="8" class="bg-gray-50 px-3 py-2">
                            <table class="w-full text-xs">
                                <thead class="text-gray-500 text-left">
                                <tr>
                                    <th class="py-1 pr-3">Datum</th>
                                    <th class="py-1 pr-3">Status</th>
                                    <th class="py-1 pr-3">Zeiten</th>
                                    <th class="py-1 pr-3 text-right">Umfang</th>
                                    <th class="py-1 pr-3">Ereignisse</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($row['days'] as $day)
                                    <tr class="border-t border-gray-200 align-top {{ $day->incomplete ? 'bg-amber-50' : '' }}">
                                        <td class="py-1 pr-3 whitespace-nowrap">
                                            {{ $day->date->locale('de')->isoFormat('dd, DD.MM.YYYY') }}
                                            @if($day->holiday)<span class="text-gray-400">(Ferien)</span>@endif
                                        </td>
                                        <td class="py-1 pr-3 whitespace-nowrap {{ $day->isPresent() ? 'text-gray-800' : 'text-gray-400' }}">
                                            {{ $day->statusLabel() }}
                                            @if($day->incomplete)<span class="text-amber-700 font-semibold">· unvollständig</span>@endif
                                            @if($day->running)<span class="text-blue-700">· läuft</span>@endif
                                            @if($day->automatic)<span class="text-gray-500">· automatisch</span>@endif
                                        </td>
                                        <td class="py-1 pr-3">
                                            @foreach($day->intervals as $i)
                                                {{ $i['from']->format('H:i') }}–{{ $i['to']->format('H:i') }}@if($i['capped'])<span title="bis Hortende gerechnet, Abmeldung fehlt">*</span>@endif{{ ! $loop->last ? ',' : '' }}
                                            @endforeach
                                        </td>
                                        <td class="py-1 pr-3 text-right font-semibold">{{ $day->isPresent() ? $day->formattedDuration() : '' }}</td>
                                        <td class="py-1 pr-3 text-gray-600">
                                            @foreach($day->events as $e)
                                                <div class="{{ $e->isCancelled() ? 'line-through text-gray-400' : '' }}">
                                                    {{ $e->occurred_at->format('H:i') }} {{ $e->typeLabel() }}
                                                    <span class="text-gray-400">({{ $e->sourceLabel() }}{{ $e->user ? ', '.$e->user->name : '' }})</span>
                                                    @if($e->isCorrected())
                                                        <span class="text-blue-700" title="{{ $e->correction_note }}">korrigiert von {{ $e->original_occurred_at->format('H:i') }} – {{ $e->correctedBy?->name }}: {{ $e->correction_note }}</span>
                                                    @endif
                                                    @if($e->isCancelled())
                                                        <span class="no-underline text-red-700">storniert – {{ $e->cancelledBy?->name }}: {{ $e->cancel_note }}</span>
                                                    @endif
                                                    @if($e->note)
                                                        <span class="text-gray-500">„{{ $e->note }}“</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                @empty
                    <tbody>
                    <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">Keine Kinder für diese Auswahl.</td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <p class="text-xs text-gray-500 mt-3">
            * Abmeldung fehlt: bis zum eingestellten Hortende gerechnet. Ohne eingestelltes Hortende zählt ein solcher Tag 0 Stunden.
            Stornierte Einträge zählen nicht. Korrekturen und Nachträge erfolgen im Tab „Verlauf“ der Anwesenheitsliste.
        </p>
    </div>
@endsection
