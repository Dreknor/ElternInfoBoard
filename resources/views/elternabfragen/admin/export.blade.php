@extends('layouts.app')

@section('title', '| Elternabfragen · Export')

@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl">
    @include('elternabfragen.admin._nav', [
        'title' => 'Export',
        'subtitle' => $inquiry->title,
        'icon' => 'fas fa-file-export',
        'back' => ['url' => route('elternabfragen.admin.inquiries.show', $inquiry), 'label' => 'Zum Rücklauf'],
    ])

    @if(session('download'))
        <div class="ea-alert ea-alert-success mb-5 items-center"><i class="fas fa-check-circle" aria-hidden="true"></i>
            <p class="mb-0 flex-1">Der Export wurde erstellt und protokolliert.</p>
            <a href="{{ route('elternabfragen.admin.exports.download', session('download')) }}" class="ea-btn ea-btn-success ea-btn-sm"><i class="fas fa-download" aria-hidden="true"></i> Exportdatei herunterladen</a>
        </div>
    @endif

    {{-- Drei Schritte: auswählen → Vorschau → erstellen --}}
    <ol class="ea-steps mb-6" aria-label="Ablauf des Exports">
        <li class="ea-step {{ $preview ? 'ea-step-done' : 'ea-step-current' }}"><span class="ea-step-dot">@if($preview)<i class="fas fa-check" aria-hidden="true"></i>@else 1 @endif</span><span class="ea-step-label">Auswahl</span></li>
        <li class="ea-step {{ $preview ? 'ea-step-current' : '' }}"><span class="ea-step-dot">2</span><span class="ea-step-label">Vorschau prüfen</span></li>
        <li class="ea-step"><span class="ea-step-dot">3</span><span class="ea-step-label">Datei erstellen</span></li>
    </ol>

    <form method="POST" action="{{ route('elternabfragen.admin.exports.preview', $inquiry) }}" class="ea-card mb-6">
        @csrf
        <div class="ea-card-header">
            <div class="ea-card-icon"><i class="fas fa-sliders-h" aria-hidden="true"></i></div>
            <div><h2 class="ea-card-title">Auswahl</h2><p class="ea-card-sub">Was soll exportiert werden – und wofür?</p></div>
        </div>
        <div class="ea-card-body space-y-5">
            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label for="group_id" class="ea-label">Ebene</label>
                    <select id="group_id" name="group_id" class="ea-input">
                        <option value="">gesamte Abfrage</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) ($input['group_id'] ?? 0) === $group->id)>Klasse {{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="format" class="ea-label">Format</label>
                    <select id="format" name="format" class="ea-input">
                        <option value="xlsx" @selected($input['format'] === 'xlsx')>Excel (XLSX)</option>
                        <option value="csv" @selected($input['format'] === 'csv')>CSV (Semikolon)</option>
                    </select>
                </div>
                <label class="ea-toggle-row self-end text-sm ea-text"><input type="checkbox" class="ea-check mt-0.5" name="include_open" value="1" @checked($input['include_open'])> auch offene Fälle (ohne Antwort)</label>
            </div>
            <fieldset>
                <legend class="ea-label">Felder</legend>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-sm ea-text">
                    @foreach($columns as $key => $label)
                        <label class="ea-toggle-row !p-2.5"><input type="checkbox" class="ea-check mt-0.5" name="columns[]" value="{{ $key }}" @checked(in_array($key, $input['columns'], true))> <span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </fieldset>
            <div>
                <label for="target" class="ea-label">Ziel des Exports</label>
                <input id="target" name="target" maxlength="255" value="{{ $input['target'] }}" class="ea-input" placeholder="z. B. Übertragung in die Schulverwaltung (manuell)">
                <p class="ea-hint">Pflichtangabe für das Exportprotokoll (Zweckbindung).</p>
            </div>
        </div>
        <div class="ea-card-footer">
            <button class="ea-btn ea-btn-primary"><i class="fas fa-search" aria-hidden="true"></i> Vorschau anzeigen</button>
        </div>
    </form>

    @if($preview)
        <section class="ea-card mb-6" style="border: 2px solid var(--color-primary)">
            <div class="ea-card-header">
                <div class="ea-card-icon"><i class="fas fa-clipboard-check" aria-hidden="true"></i></div>
                <h2 class="ea-card-title">Vorschau vor dem Herunterladen</h2>
            </div>
            <div class="ea-card-body space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="ea-stat">
                        <span class="ea-stat-icon bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"><i class="fas fa-table" aria-hidden="true"></i></span>
                        <span><span class="ea-stat-value block">{{ $preview['records'] }}</span><span class="ea-stat-label block">Datensätze</span></span>
                    </div>
                    <div class="ea-stat">
                        <span class="ea-stat-icon bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300"><i class="fas fa-code-branch" aria-hidden="true"></i></span>
                        <span><span class="ea-stat-value block !text-base">{{ $preview['version'] }}</span><span class="ea-stat-label block">Antwortversion</span></span>
                    </div>
                </div>
                <dl class="ea-facts mb-0">
                    <div><dt><i class="fas fa-columns" aria-hidden="true"></i> Ausgewählte Felder</dt><dd>{{ implode(', ', $preview['columns']) }}</dd></div>
                    <div><dt><i class="fas fa-bullseye" aria-hidden="true"></i> Ziel / verantwortlich</dt><dd>{{ $input['target'] ?: '– (bitte angeben)' }} / {{ auth()->user()->name }}</dd></div>
                </dl>
                @if($preview['open'] || $preview['missing'] || $preview['clarification'] || $preview['unreviewed'])
                    <div class="flex flex-wrap gap-2">
                        @if($preview['open'])<x-elternabfragen.badge color="amber" icon="far fa-circle">{{ $preview['open'] }} ohne Antwort</x-elternabfragen.badge>@endif
                        @if($preview['missing'])<x-elternabfragen.badge color="red" icon="fas fa-exclamation-circle">{{ $preview['missing'] }} mit fehlenden Pflichtangaben</x-elternabfragen.badge>@endif
                        @if($preview['clarification'])<x-elternabfragen.badge color="amber" icon="fas fa-question-circle">{{ $preview['clarification'] }} mit Klärungsbedarf</x-elternabfragen.badge>@endif
                        @if($preview['unreviewed'])<x-elternabfragen.badge color="gray" icon="far fa-eye">{{ $preview['unreviewed'] }} noch ungeprüft</x-elternabfragen.badge>@endif
                    </div>
                @endif
            </div>
            <form method="POST" action="{{ route('elternabfragen.admin.exports.store', $inquiry) }}" class="ea-card-footer flex flex-wrap items-center gap-3">
                @csrf
                @foreach($input['columns'] as $column)<input type="hidden" name="columns[]" value="{{ $column }}">@endforeach
                <input type="hidden" name="group_id" value="{{ $input['group_id'] }}">
                <input type="hidden" name="format" value="{{ $input['format'] }}">
                <input type="hidden" name="include_open" value="{{ $input['include_open'] ? 1 : 0 }}">
                <input type="hidden" name="target" value="{{ $input['target'] }}">
                <button class="ea-btn ea-btn-success" @disabled(! $input['target'])><i class="fas fa-file-download" aria-hidden="true"></i> Export erstellen (protokolliert)</button>
                @unless($input['target'])<span class="text-xs text-red-600">Bitte oben das Ziel des Exports angeben.</span>@endunless
            </form>
        </section>
    @endif

    <section class="ea-card">
        <div class="ea-card-header">
            <div class="ea-card-icon"><i class="fas fa-history" aria-hidden="true"></i></div>
            <h2 class="ea-card-title">Exportprotokoll</h2>
        </div>
        <ul class="ea-divide mb-0 text-sm">
            @forelse($exports as $export)
                <li class="px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                    <span class="ea-text">
                        <i class="fas {{ $export->format === 'csv' ? 'fa-file-csv' : 'fa-file-excel' }} text-emerald-600 mr-1" aria-hidden="true"></i>
                        {{ $export->created_at->format('d.m.Y H:i') }} · {{ $export->creator?->name }} · {{ $export->record_count }} Datensätze · {{ strtoupper($export->format) }} · Ziel: {{ $export->target }}
                        · {{ $export->download_count }}× geladen
                    </span>
                    @if($export->isAvailable())
                        <a href="{{ route('elternabfragen.admin.exports.download', $export) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-download" aria-hidden="true"></i> Herunterladen (bis {{ $export->expires_at->format('d.m.Y') }})</a>
                    @else
                        <span class="text-xs ea-muted">Datei gelöscht</span>
                    @endif
                </li>
            @empty
                <li class="px-5 py-4 ea-muted">Noch keine Exporte.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
