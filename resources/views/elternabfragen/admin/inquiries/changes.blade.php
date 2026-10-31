@extends('layouts.app')

@section('title', '| Elternabfragen · Änderungen')

@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl">
    @include('elternabfragen.admin._nav', [
        'title' => 'Änderungen gegenüber der Vorabfrage',
        'subtitle' => $inquiry->title.($inquiry->previousInquiry ? ' · verglichen mit „'.$inquiry->previousInquiry->title.'“' : ''),
        'icon' => 'fas fa-history',
        'back' => ['url' => route('elternabfragen.admin.inquiries.show', $inquiry), 'label' => 'Zurück zum Rücklauf'],
    ])

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="ea-stat">
            <span class="ea-stat-icon bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"><i class="fas fa-exchange-alt" aria-hidden="true"></i></span>
            <span><span class="ea-stat-value block">{{ $counts['changed'] }}</span><span class="ea-stat-label block">mit Änderungen</span></span>
        </div>
        <div class="ea-stat">
            <span class="ea-stat-icon bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"><i class="fas fa-equals" aria-hidden="true"></i></span>
            <span><span class="ea-stat-value block">{{ $counts['unchanged'] }}</span><span class="ea-stat-label block">unverändert übernommen</span></span>
        </div>
        <div class="ea-stat">
            <span class="ea-stat-icon bg-gray-100 text-gray-600 dark:bg-gray-700/60 dark:text-gray-300"><i class="fas fa-user-plus" aria-hidden="true"></i></span>
            <span><span class="ea-stat-value block">{{ $counts['without_baseline'] }}</span><span class="ea-stat-label block">ohne Angaben aus der Vorabfrage</span></span>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <a href="{{ route('elternabfragen.admin.inquiries.changes', $inquiry) }}"
           class="ea-chip {{ $all ? '' : 'ea-chip-active' }}" @unless($all) aria-current="page" @endunless>Nur Änderungen</a>
        <a href="{{ route('elternabfragen.admin.inquiries.changes', [$inquiry, 'anzeigen' => 'alle']) }}"
           class="ea-chip {{ $all ? 'ea-chip-active' : '' }}" @if($all) aria-current="page" @endif>Alle Antworten</a>
        <span class="text-xs ea-muted basis-full sm:basis-auto sm:ml-2">Einwilligungen werden nicht vorbefüllt und erscheinen deshalb hier, wenn sie anders als im Vorjahr erteilt wurden. Dateien werden nicht verglichen.</span>
    </div>

    <section class="ea-card ea-divide">
        @forelse($responses as $response)
            @php $changes = $response->baselineChanges(); @endphp
            <article class="p-5">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-3">
                        <span class="ea-avatar !w-9 !h-9 !text-xs">{{ mb_strtoupper(mb_substr($response->child?->first_name ?? '?', 0, 1).mb_substr($response->child?->last_name ?? '', 0, 1)) }}</span>
                        <div>
                            <h2 class="font-semibold ea-text text-base mb-0">{{ trim(($response->child?->first_name ?? '').' '.($response->child?->last_name ?? '')) }}</h2>
                            <p class="text-xs ea-muted mb-0">{{ $response->assignment?->group?->name ?? '–' }} · {{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }} · v{{ $response->version }}</p>
                        </div>
                    </div>
                    <a href="{{ route('elternabfragen.admin.responses.show', $response) }}" class="ea-btn ea-btn-ghost ea-btn-sm">Antwort ansehen <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                </div>
                @if(! $response->hasBaseline())
                    <p class="text-sm ea-muted mb-0"><i class="fas fa-user-plus" aria-hidden="true"></i> Keine Angaben aus der Vorabfrage vorhanden (z. B. neu hinzugekommenes Kind).</p>
                @elseif($changes === [])
                    <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-0"><i class="fas fa-equals" aria-hidden="true"></i> Angaben unverändert übernommen.</p>
                @else
                    <div class="rounded-xl overflow-hidden" style="border: 1px solid var(--color-card-border)">
                        <table class="ea-table">
                            <thead>
                                <tr><th>Angabe</th><th>Bisher</th><th>Neu</th></tr>
                            </thead>
                            <tbody>
                                @foreach($changes as $key => $change)
                                    @php $field = $fields->get($key, ['key' => $key, 'type' => 'text', 'label' => $key]); @endphp
                                    <tr>
                                        <td class="font-medium">{{ $field['label'] }}</td>
                                        <td class="break-words whitespace-pre-line"><span class="ea-muted line-through">{{ $schema->display($field, $change['old']) }}</span></td>
                                        <td class="break-words whitespace-pre-line"><span class="inline-block px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 font-medium">{{ $schema->display($field, $change['new']) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>
        @empty
            <div class="ea-empty">
                <div class="ea-empty-icon"><i class="fas fa-check" aria-hidden="true"></i></div>
                <p class="mb-0">{{ $all ? 'Noch keine Antworten eingegangen.' : 'Bisher hat niemand Angaben gegenüber der Vorabfrage geändert.' }}</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
