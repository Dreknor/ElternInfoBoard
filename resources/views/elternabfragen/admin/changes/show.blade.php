@extends('layouts.app')

@section('title', '| Elternabfragen · Änderungsmeldung')

@section('content')
@php
    $changed = collect($rows)->where('changed', true);
    $fileValue = fn ($row) => is_array($row['new']) && isset($row['new']['path']);
@endphp
<div class="container-fluid px-4 py-6 max-w-5xl">
    @include('elternabfragen.admin._nav', [
        'title' => $assignment->childName(),
        'subtitle' => $inquiry->title.' · '.($assignment->group?->name ?? 'ohne Klasse'),
        'icon' => 'fas fa-exchange-alt',
        'back' => ['url' => route('elternabfragen.admin.change-requests.index'), 'label' => 'Alle Änderungsmeldungen'],
    ])

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6 min-w-0">
            <section class="ea-card">
                <div class="ea-card-header flex-wrap">
                    <x-elternabfragen.badge :color="$change->status->color()" dot class="text-sm">{{ $change->status->label() }}</x-elternabfragen.badge>
                    <span class="text-sm ea-muted">Gemeldet am {{ $change->created_at->format('d.m.Y H:i') }} von {{ $change->user?->name ?? 'unbekannt' }} · {{ $change->channel === 'app' ? 'App' : 'Web' }}</span>
                </div>
                <div class="ea-card-body space-y-4 text-sm">
                    <div class="ea-alert ea-alert-info"><i class="fas fa-comment" aria-hidden="true"></i>
                        <p class="mb-0 whitespace-pre-line"><strong>Nachricht der Eltern:</strong> {{ $change->message }}</p>
                    </div>
                    @if($outdated)
                        <div class="ea-alert ea-alert-warning"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            <p class="mb-0">Seit der Meldung wurde eine neue Antwortversion gespeichert. Der Vergleich zeigt die Unterschiede zur <strong>heute gültigen</strong> Version – bitte prüfen, ob die Meldung noch passt.</p>
                        </div>
                    @endif
                    @if($change->isPending() && $inquiry->templateVersion->record_relevant)
                        <p class="ea-muted mb-0"><i class="fas fa-folder-open" aria-hidden="true"></i> Aktenrelevant: Nach der Übernahme muss die neue Version für die Schülerakte gedruckt werden.</p>
                    @endif
                    @if(! $change->isPending() && $change->handled_note)
                        <p class="mb-0"><span class="ea-muted">Vermerk der Verwaltung ({{ $change->handler?->name ?? '–' }}, {{ $change->handled_at?->format('d.m.Y H:i') }}):</span> {{ $change->handled_note }}</p>
                    @endif
                    @if($change->response)
                        <p class="mb-0"><a href="{{ route('elternabfragen.admin.responses.show', $change->response) }}" class="ea-link">Übernommen als Version {{ $change->response->version }}</a></p>
                    @endif
                </div>
            </section>

            <section class="ea-card overflow-hidden">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-columns" aria-hidden="true"></i></div>
                    <div>
                        <h2 class="ea-card-title">Vergleich</h2>
                        <p class="ea-card-sub">{{ $changed->count() }} {{ $changed->count() === 1 ? 'Angabe' : 'Angaben' }} geändert</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="ea-table">
                        <thead><tr><th>Angabe</th><th>Bisher</th><th>Neu</th></tr></thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr @if($row['changed']) style="background: rgba(245, 158, 11, .12)" @endif>
                                    <td class="font-medium">
                                        @if($row['changed'])<i class="fas fa-pen text-amber-600 mr-1" aria-label="geändert"></i>@endif
                                        {{ $row['field']['label'] }}
                                    </td>
                                    <td class="whitespace-pre-line {{ $row['changed'] ? 'ea-muted line-through' : 'ea-muted' }}">{{ $schema->display($row['field'], $row['old']) }}</td>
                                    <td class="whitespace-pre-line {{ $row['changed'] ? 'font-semibold' : '' }}">
                                        @if($row['changed'] && $fileValue($row))
                                            <a href="{{ route('elternabfragen.admin.change-requests.file', [$change, $row['field']['key']]) }}" class="ea-link">{{ $schema->display($row['field'], $row['new']) }}</a>
                                        @else
                                            {{ $schema->display($row['field'], $row['new']) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            @if($change->isPending())
                <section class="ea-card">
                    <div class="ea-card-header">
                        <div class="ea-card-icon !bg-emerald-100 !text-emerald-600 dark:!bg-emerald-900/40"><i class="fas fa-check" aria-hidden="true"></i></div>
                        <h2 class="ea-card-title">Übernehmen</h2>
                    </div>
                    <form method="POST" action="{{ route('elternabfragen.admin.change-requests.accept', $change) }}" class="ea-card-body space-y-3 text-sm">
                        @csrf
                        <p class="ea-muted mb-0">
                            Die Angaben werden als neue, geprüfte Antwortversion von {{ $change->user?->name ?? 'den Eltern' }} gespeichert. Die bisherige Version bleibt nachvollziehbar erhalten.
                            @if($inquiry->templateVersion->requires_all_guardians) Die weiteren Sorgeberechtigten werden um Bestätigung gebeten. @endif
                            Übertragen Sie die Änderung bei Bedarf in die Schulverwaltung.
                        </p>
                        <textarea name="handled_note" rows="2" maxlength="2000" class="ea-input" placeholder="Vermerk (optional, z. B. in die Schulverwaltung übertragen)"></textarea>
                        <button class="ea-btn ea-btn-success w-full"><i class="fas fa-check" aria-hidden="true"></i> Änderung übernehmen</button>
                    </form>
                </section>
                <section class="ea-card">
                    <div class="ea-card-header">
                        <div class="ea-card-icon !bg-red-100 !text-red-600 dark:!bg-red-900/40"><i class="fas fa-times" aria-hidden="true"></i></div>
                        <h2 class="ea-card-title">Ablehnen</h2>
                    </div>
                    <form method="POST" action="{{ route('elternabfragen.admin.change-requests.reject', $change) }}" class="ea-card-body space-y-3 text-sm">
                        @csrf
                        <textarea name="handled_note" rows="3" required maxlength="2000" class="ea-input" placeholder="Begründung für die Eltern (Pflicht), z. B. Nachweis fehlt"></textarea>
                        <button class="ea-btn ea-btn-danger w-full"><i class="fas fa-times" aria-hidden="true"></i> Ablehnen</button>
                    </form>
                </section>
            @endif

            <section class="ea-card">
                <div class="ea-card-body space-y-2 text-sm">
                    <a href="{{ route('elternabfragen.admin.assignments.show', $assignment) }}" class="ea-btn ea-btn-secondary w-full"><i class="fas fa-child" aria-hidden="true"></i> Zum Fall</a>
                    @if($assignment->currentResponse)
                        <a href="{{ route('elternabfragen.admin.responses.show', $assignment->currentResponse) }}" class="ea-btn ea-btn-ghost w-full"><i class="fas fa-eye" aria-hidden="true"></i> Gültige Antwort (Version {{ $assignment->currentResponse->version }})</a>
                    @endif
                </div>
            </section>

            @if($others->isNotEmpty())
                <section class="ea-card">
                    <div class="ea-card-header">
                        <div class="ea-card-icon"><i class="fas fa-history" aria-hidden="true"></i></div>
                        <h2 class="ea-card-title">Weitere Meldungen</h2>
                    </div>
                    <ul class="ea-divide mb-0 text-sm">
                        @foreach($others as $other)
                            <li class="px-5 py-3">
                                <a href="{{ route('elternabfragen.admin.change-requests.show', $other) }}" class="ea-link">{{ $other->created_at->format('d.m.Y') }} · {{ $other->user?->name ?? 'unbekannt' }}</a>
                                <x-elternabfragen.badge :color="$other->status->color()">{{ $other->status->label() }}</x-elternabfragen.badge>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
