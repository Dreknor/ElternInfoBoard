@extends('layouts.student')
@section('title', '- Stundenplan')
@php $tage = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag']; @endphp
@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Stundenplan</h1>
        @if(count($klassen) > 1)
            <form method="GET" class="flex items-center gap-2">
                <label for="klasse" class="text-sm student-muted">Klasse</label>
                <select id="klasse" name="klasse" onchange="this.form.submit()" class="rounded-lg border px-2 py-1 text-sm" style="background: var(--color-input-bg, #fff); border-color: var(--color-input-border, #cbd5e1);">
                    @foreach($klassen as $k)<option value="{{ $k }}" @selected($k === $klasse)>{{ $k }}</option>@endforeach
                </select>
            </form>
        @elseif($klasse)
            <span class="rounded-full border px-3 py-0.5 text-sm" style="border-color: var(--color-card-border);">{{ $klasse }}</span>
        @endif
    </div>

    @if(! $klasse)
        <div class="student-card rounded-xl p-4 text-sm student-muted">Für deine Klasse ist kein Stundenplan hinterlegt.</div>
    @else
        @php
            $cell = function ($tag, $slot) use ($timetable, $vertretungen) {
                $v = collect($vertretungen[$tag] ?? [])->firstWhere('stunde', $slot['Stunde']);
                $entry = $timetable[$tag][(int) $slot['Stunde']] ?? null;
                return [$v, $entry];
            };
        @endphp

        {{-- Handy: ein Tag nach dem anderen --}}
        <div class="md:hidden" x-data="{ tag: {{ $today }} }">
            <div class="mb-3 grid grid-cols-5 gap-1" role="tablist">
                @foreach($tage as $nr => $name)
                    <button type="button" role="tab" @click="tag = {{ $nr }}" :aria-selected="tag === {{ $nr }}"
                            class="rounded-lg border py-2 text-sm" style="border-color: var(--color-card-border);"
                            :class="tag === {{ $nr }} ? 'font-semibold text-white' : ''"
                            :style="tag === {{ $nr }} ? 'background: var(--color-primary)' : ''">{{ mb_substr($name, 0, 2) }}</button>
                @endforeach
            </div>
            @foreach($tage as $nr => $name)
                <section x-show="tag === {{ $nr }}" @if($nr !== $today) x-cloak @endif class="student-card rounded-xl p-3">
                    <h2 class="mb-2 font-semibold">{{ $name }}</h2>
                    <ul class="divide-y" style="border-color: var(--color-card-border);">
                        @foreach($zeitslots as $slot)
                            @php [$v, $entry] = $cell($nr, $slot); @endphp
                            <li class="flex gap-3 py-2">
                                <div class="w-14 shrink-0 text-xs student-muted"><div class="font-bold">{{ $slot['Stunde'] }}.</div>{{ $slot['ZeitVon'] }}</div>
                                <div class="min-w-0 text-sm">
                                    @if($v)
                                        <span class="rounded bg-red-100 px-1.5 text-xs font-bold text-red-800">Vertretung</span>
                                        @if($v->altFach)<span class="line-through student-muted">{{ $v->altFach }}</span>@endif
                                        <span class="font-semibold">{{ $v->neuFach }}</span>
                                        @if($v->comment)<div class="text-xs italic student-muted">{{ $v->comment }}</div>@endif
                                    @elseif($entry)
                                        <span class="font-semibold">{{ $entry['PlFa'] ?? '' }}</span>
                                        <span class="student-muted">{{ implode(', ', $entry['PlRa'] ?? []) }}</span>
                                    @else
                                        <span class="student-muted">–</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        {{-- Tablet/Desktop: Wochentabelle --}}
        <div class="student-card hidden overflow-x-auto rounded-xl md:block">
            <table class="w-full table-fixed text-sm">
                <thead>
                    <tr class="text-left text-white" style="background: var(--color-primary);">
                        <th class="w-20 px-3 py-2">Zeit</th>
                        @foreach($tage as $name)<th class="px-3 py-2">{{ $name }}</th>@endforeach
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--color-card-border);">
                    @foreach($zeitslots as $slot)
                        <tr>
                            <td class="px-3 py-2 align-top text-xs student-muted"><div class="font-bold">{{ $slot['Stunde'] }}.</div>{{ $slot['ZeitVon'] }}–{{ $slot['ZeitBis'] }}</td>
                            @foreach($tage as $nr => $name)
                                @php [$v, $entry] = $cell($nr, $slot); @endphp
                                <td class="break-words px-3 py-2 align-top">
                                    @if($v)
                                        <div class="rounded border-l-4 border-red-500 bg-red-50 p-1.5 text-red-900">
                                            @if($v->altFach)<div class="text-xs line-through">{{ $v->altFach }}</div>@endif
                                            <div class="font-semibold">{{ $v->neuFach }}</div>
                                            @if($v->comment)<div class="text-xs italic">{{ $v->comment }}</div>@endif
                                        </div>
                                    @elseif($entry)
                                        <div class="font-semibold">{{ $entry['PlFa'] ?? '' }}</div>
                                        <div class="text-xs student-muted">{{ implode(', ', $entry['PlRa'] ?? []) }}</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
