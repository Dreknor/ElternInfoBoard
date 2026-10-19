@extends('layouts.app')

@section('title', '| Elternabfragen · Änderungen')

@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl">
    @include('elternabfragen.admin._nav', [
        'title' => 'Änderungen gegenüber der Vorabfrage',
        'subtitle' => $inquiry->title.($inquiry->previousInquiry ? ' · verglichen mit „'.$inquiry->previousInquiry->title.'“' : ''),
    ])

    <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="inline-block mb-4 text-sm text-blue-700 hover:underline">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> Zurück zum Rücklauf
    </a>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-xl border p-3 text-center">
            <div class="text-2xl font-bold text-amber-700">{{ $counts['changed'] }}</div><div class="text-xs text-gray-500">mit Änderungen</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $counts['unchanged'] }}</div><div class="text-xs text-gray-500">unverändert übernommen</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $counts['without_baseline'] }}</div><div class="text-xs text-gray-500">ohne Angaben aus der Vorabfrage</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4 text-sm">
        <a href="{{ route('elternabfragen.admin.inquiries.changes', $inquiry) }}"
           class="px-3 py-1.5 rounded-lg border {{ $all ? 'bg-white border-gray-300' : 'bg-blue-600 text-white border-blue-600' }}" @unless($all) aria-current="page" @endunless>Nur Änderungen</a>
        <a href="{{ route('elternabfragen.admin.inquiries.changes', [$inquiry, 'anzeigen' => 'alle']) }}"
           class="px-3 py-1.5 rounded-lg border {{ $all ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300' }}" @if($all) aria-current="page" @endif>Alle Antworten</a>
        <span class="text-xs text-gray-500">Einwilligungen werden nicht vorbefüllt und erscheinen deshalb hier, wenn sie anders als im Vorjahr erteilt wurden. Dateien werden nicht verglichen.</span>
    </div>

    <section class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y divide-gray-100">
        @forelse($responses as $response)
            @php $changes = $response->baselineChanges(); @endphp
            <article class="p-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-gray-800">
                        {{ trim(($response->child?->first_name ?? '').' '.($response->child?->last_name ?? '')) }}
                        <span class="text-sm font-normal text-gray-500">· {{ $response->assignment?->group?->name ?? '–' }}</span>
                    </h2>
                    <span class="text-xs text-gray-500">
                        {{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }} · v{{ $response->version }}
                        · <a href="{{ route('elternabfragen.admin.responses.show', $response) }}" class="text-blue-700 hover:underline">Antwort ansehen</a>
                    </span>
                </div>
                @if(! $response->hasBaseline())
                    <p class="text-sm text-gray-500">Keine Angaben aus der Vorabfrage vorhanden (z. B. neu hinzugekommenes Kind).</p>
                @elseif($changes === [])
                    <p class="text-sm text-gray-500"><i class="fas fa-equals" aria-hidden="true"></i> Angaben unverändert übernommen.</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-500 text-xs">
                            <tr><th class="py-1 pr-3 font-medium">Angabe</th><th class="py-1 pr-3 font-medium">Bisher</th><th class="py-1 font-medium">Neu</th></tr>
                        </thead>
                        <tbody>
                            @foreach($changes as $key => $change)
                                @php $field = $fields->get($key, ['key' => $key, 'type' => 'text', 'label' => $key]); @endphp
                                <tr class="align-top">
                                    <td class="py-1 pr-3 text-gray-700">{{ $field['label'] }}</td>
                                    <td class="py-1 pr-3 text-gray-500 line-through decoration-gray-400 break-words whitespace-pre-line">{{ $schema->display($field, $change['old']) }}</td>
                                    <td class="py-1 text-gray-900 font-medium break-words whitespace-pre-line">{{ $schema->display($field, $change['new']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </article>
        @empty
            <p class="p-6 text-center text-gray-500">{{ $all ? 'Noch keine Antworten eingegangen.' : 'Bisher hat niemand Angaben gegenüber der Vorabfrage geändert.' }}</p>
        @endforelse
    </section>
</div>
@endsection
