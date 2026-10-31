@extends('layouts.app')

@section('title', '| Elternabfragen')

@can('manage elternabfragen')
    @section('ea-actions')
        <a href="{{ route('elternabfragen.admin.inquiries.create') }}" class="ea-btn ea-btn-light">
            <i class="fas fa-plus" aria-hidden="true"></i> Neue Abfrage
        </a>
    @endsection
@endcan

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => 'Abfragen & Rücklauf', 'subtitle' => 'Abfragen vorbereiten, freigeben, Rücklauf überwachen und exportieren'])

    {{-- Filter --}}
    <form method="GET" class="ea-card p-4 mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px]">
            <label for="status" class="ea-label">Status</label>
            <select id="status" name="status" class="ea-input" onchange="this.form.submit()">
                <option value="">Alle nicht abgeschlossenen</option>
                @foreach(\App\Enums\InquiryStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label for="q" class="ea-label">Suche</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs ea-muted" aria-hidden="true"></i>
                <input id="q" name="q" value="{{ request('q') }}" class="ea-input pl-9" placeholder="Titel der Abfrage">
            </div>
        </div>
        <button class="ea-btn ea-btn-secondary"><i class="fas fa-filter" aria-hidden="true"></i> Filtern</button>
    </form>

    @if($inquiries->isEmpty())
        <div class="ea-card">
            <div class="ea-empty">
                <div class="ea-empty-icon"><i class="fas fa-clipboard" aria-hidden="true"></i></div>
                <p class="font-semibold ea-text mb-1">Keine Abfragen gefunden.</p>
                @can('manage elternabfragen')
                    <p class="text-sm mb-0">Abfragen entstehen aus freigegebenen <a class="ea-link" href="{{ route('elternabfragen.admin.templates.index') }}">Vorlagen</a>.</p>
                @endcan
            </div>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach($inquiries as $inquiry)
                @php
                    $c = $counts->get($inquiry->id, collect());
                    $total = $c->sum();
                    $answered = ($c['submitted'] ?? 0) + ($c['corrected'] ?? 0);
                    $percent = $total > 0 ? round($answered / $total * 100) : 0;
                    $statusIcon = match ($inquiry->status->value) {
                        'draft' => 'fas fa-pen',
                        'pending_approval' => 'fas fa-hourglass-half',
                        'scheduled' => 'far fa-calendar-check',
                        'running' => 'fas fa-paper-plane',
                        default => 'fas fa-lock',
                    };
                @endphp
                <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="ea-card p-5 flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="ea-card-icon"><i class="{{ $statusIcon }}" aria-hidden="true"></i></div>
                            <div class="min-w-0">
                                <p class="font-semibold ea-text mb-0 break-words">{{ $inquiry->title }}</p>
                                <p class="text-xs ea-muted mb-0 mt-0.5">{{ $inquiry->templateVersion?->label() }}</p>
                            </div>
                        </div>
                        <x-elternabfragen.badge :color="$inquiry->status->color()" dot>{{ $inquiry->status->label() }}</x-elternabfragen.badge>
                    </div>

                    <div>
                        @if($total > 0)
                            <div class="flex items-baseline justify-between mb-1.5">
                                <span class="text-xs font-medium ea-muted">Rücklauf</span>
                                <span class="text-sm font-semibold ea-text">{{ $answered }}/{{ $total }} <span class="text-xs font-normal ea-muted">· {{ $percent }} %</span></span>
                            </div>
                            <div class="ea-progress" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Rücklauf">
                                <div class="ea-progress-bar" style="width: {{ $percent }}%"></div>
                            </div>
                            @if(($c['problem'] ?? 0) > 0)
                                <p class="text-xs text-red-600 mt-2 mb-0"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ $c['problem'] }} Problem(e)</p>
                            @endif
                        @else
                            <p class="text-xs ea-muted mb-0"><i class="far fa-clock" aria-hidden="true"></i> noch nicht gestartet</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-3 text-xs ea-muted" style="border-top: 1px solid var(--color-card-border)">
                        <span class="whitespace-nowrap">
                            <i class="far fa-calendar" aria-hidden="true"></i>
                            {{ $inquiry->starts_at->format('d.m.Y') }} – {{ $inquiry->deadline_at->format('d.m.Y') }}
                            @if($inquiry->isRunning() && $inquiry->deadlinePassed())
                                <span class="text-red-600 font-semibold">· Frist abgelaufen</span>
                            @endif
                        </span>
                        <span><i class="far fa-user" aria-hidden="true"></i> {{ $inquiry->responsible?->name ?? '–' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-5">{{ $inquiries->links() }}</div>
    @endif
</div>
@endsection
