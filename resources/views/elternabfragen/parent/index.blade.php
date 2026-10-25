@extends('layouts.app')

@section('title', '| Elternabfragen')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    <div class="ea-hero mb-6">
        <div class="flex items-center gap-4">
            <div class="ea-hero-icon"><i class="fas fa-clipboard-check" aria-hidden="true"></i></div>
            <div class="min-w-0">
                <h1 class="ea-hero-title">Elternabfragen</h1>
                <p class="ea-hero-sub">Abfragen der Schule zu Ihren Kindern. Jede Abfrage gilt für ein Kind – bei mehreren Kindern bitte für jedes Kind ausfüllen.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            <span class="ea-hero-meta"><i class="fas fa-hourglass-half" aria-hidden="true"></i> {{ $open->count() }} offen</span>
            @if($done->isNotEmpty())
                <span class="ea-hero-meta"><i class="fas fa-check" aria-hidden="true"></i> {{ $done->count() }} erledigt</span>
            @endif
        </div>
    </div>

    <section aria-labelledby="offen" class="mb-8">
        <h2 id="offen" class="ea-section-title"><i class="fas fa-inbox" aria-hidden="true"></i> Offen ({{ $open->count() }})</h2>
        @forelse($open as $assignment)
            @php
                $state = $states[$assignment->id];
                $overdue = $state['deadline']->isPast();
                $soon = ! $overdue && $state['deadline']->diffInDays(now(), true) < 3;
            @endphp
            <a href="{{ route('elternabfragen.show', $assignment) }}" class="ea-card ea-card-accent block p-4 sm:p-5 mb-3 focus:outline-none focus:ring-2"
               @if($overdue) style="border-left-color: #ef4444" @elseif($soon) style="border-left-color: #f59e0b" @endif>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="ea-avatar">{{ mb_strtoupper(mb_substr($assignment->child?->first_name ?? '?', 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide mb-0" style="color: var(--color-primary)">
                                {{ $assignment->childName() }}
                            </p>
                            <p class="font-semibold ea-text mt-0.5 mb-0 break-words">{{ $assignment->inquiry->title }}</p>
                            <p class="text-sm ea-muted mt-1 mb-0">
                                <i class="far fa-clock" aria-hidden="true"></i>
                                Bitte bis <strong class="ea-text">{{ $state['deadline']->format('d.m.Y, H:i') }} Uhr</strong>
                                @if($overdue)
                                    <span class="text-red-600 font-medium">(Frist abgelaufen)</span>
                                @elseif($soon)
                                    <span class="text-amber-600 font-medium">· bald fällig</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        <x-elternabfragen.badge :color="$assignment->displayColor()" dot>{{ $assignment->displayLabel() }}</x-elternabfragen.badge>
                        <i class="fas fa-chevron-right ea-muted hidden sm:block" aria-hidden="true"></i>
                    </div>
                </div>
                @if($state['can_confirm'])
                    <p class="ea-alert ea-alert-info !p-2.5 !mt-3 mb-0"><i class="fas fa-user-check" aria-hidden="true"></i> <span>Bereits beantwortet – bitte prüfen und bestätigen Sie die Angaben.</span></p>
                @endif
                @unless($state['can_view_answers'])
                    <p class="text-xs ea-muted mt-3 mb-0"><i class="fas fa-info-circle" aria-hidden="true"></i> Nur zur Information – beantworten können sorgeberechtigte Personen.</p>
                @endunless
            </a>
        @empty
            <div class="ea-card">
                <div class="ea-empty">
                    <div class="ea-empty-icon !bg-emerald-100 !text-emerald-600 dark:!bg-emerald-900/40"><i class="fas fa-check" aria-hidden="true"></i></div>
                    <p class="font-semibold ea-text mb-1">Alles erledigt!</p>
                    <p class="text-sm mb-0">Zurzeit sind keine Abfragen offen.</p>
                </div>
            </div>
        @endforelse
    </section>

    @if($done->isNotEmpty())
        <section aria-labelledby="erledigt">
            <h2 id="erledigt" class="ea-section-title"><i class="fas fa-check-double" aria-hidden="true"></i> Erledigt und abgeschlossen</h2>
            <ul class="ea-card ea-divide overflow-hidden mb-0">
                @foreach($done as $assignment)
                    <li>
                        <a href="{{ route('elternabfragen.show', $assignment) }}" class="ea-row-link flex items-center justify-between gap-3 px-4 sm:px-5 py-4">
                            <span class="flex items-center gap-3 min-w-0">
                                <span class="flex items-center justify-center w-9 h-9 rounded-full flex-shrink-0 bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300"><i class="fas fa-check text-sm" aria-hidden="true"></i></span>
                                <span class="min-w-0">
                                    <span class="block font-medium ea-text break-words">{{ $assignment->inquiry->title }}</span>
                                    <span class="block text-sm ea-muted">
                                        {{ $assignment->childName() }}
                                        @if($assignment->currentResponse)
                                            · eingereicht {{ $assignment->currentResponse->submitted_at->format('d.m.Y H:i') }}
                                        @endif
                                    </span>
                                </span>
                            </span>
                            <x-elternabfragen.badge :color="$assignment->displayColor()">{{ $assignment->displayLabel() }}</x-elternabfragen.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
