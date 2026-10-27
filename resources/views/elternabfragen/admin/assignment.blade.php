@extends('layouts.app')

@section('title', '| Elternabfragen · '.$assignment->childName())

@section('content')
@php $defaultDate = now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i'); @endphp
<div class="container-fluid px-4 py-6 max-w-6xl">
    @include('elternabfragen.admin._nav', [
        'title' => $assignment->childName(),
        'subtitle' => $inquiry->title.' · '.($assignment->group?->name ?? 'ohne Klasse'),
        'icon' => 'fas fa-child',
        'back' => ['url' => route('elternabfragen.admin.inquiries.show', $inquiry), 'label' => 'Zum Rücklauf'],
    ])

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6 min-w-0">
            <section class="ea-card">
                <div class="ea-card-header flex-wrap">
                    <x-elternabfragen.badge :color="$assignment->status->color()" dot class="text-sm">{{ $assignment->status->label() }}</x-elternabfragen.badge>
                    <span class="text-sm ea-muted"><i class="far fa-clock" aria-hidden="true"></i> Frist: {{ $assignment->effectiveDeadline()->format('d.m.Y H:i') }}{{ $assignment->extended_until ? ($assignment->reopened_at ? ' (individuell)' : ' (Nachfrist)') : '' }}</span>
                    @if($assignment->reopened_at)<span class="text-sm ea-muted">· Neu gestartet am {{ $assignment->reopened_at->format('d.m.Y H:i') }}</span>@endif
                    @if($assignment->correction_until)<span class="text-sm ea-muted">· Korrektur freigegeben bis {{ $assignment->correction_until->format('d.m.Y H:i') }}</span>@endif
                </div>
                <div class="ea-card-body space-y-4 text-sm">
                    @if($assignment->has_problem)
                        <div class="ea-alert ea-alert-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><p class="mb-0"><strong>Technisches Problem:</strong> {{ $assignment->problem_note }}</p></div>
                    @endif
                    @if($assignment->isReopened())
                        <div class="ea-alert ea-alert-info"><i class="fas fa-redo" aria-hidden="true"></i><p class="mb-0">Die Abfrage ist abgeschlossen, für diese Familie aber bis {{ $assignment->extended_until->format('d.m.Y H:i') }} wieder geöffnet.</p></div>
                    @endif
                    @if($assignment->isAnswered())
                        <div class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-xl ea-subtle">
                            <p class="mb-0">
                                <i class="fas fa-folder{{ $assignment->isFiled() ? '' : '-open' }} {{ $assignment->isFiled() ? 'text-emerald-600' : 'ea-muted' }}" aria-hidden="true"></i>
                                @if($assignment->isFiled())
                                    Für die Schülerakte gedruckt am {{ $assignment->filed_at->format('d.m.Y H:i') }} von {{ $assignment->filer?->name ?? 'unbekannt' }}.
                                @elseif($assignment->filed_at)
                                    Eine frühere Version wurde am {{ $assignment->filed_at->format('d.m.Y') }} gedruckt – die gültige Antwort noch nicht.
                                @else
                                    Noch nicht für die Schülerakte gedruckt.
                                @endif
                                @if($inquiry->templateVersion->record_relevant)<x-elternabfragen.badge color="amber" icon="fas fa-folder-open">aktenrelevant</x-elternabfragen.badge>@endif
                            </p>
                            @if($canSeeAnswers && ! $inquiry->purged_at)
                                <a href="{{ route('elternabfragen.admin.records.assignment', $assignment) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-file-pdf" aria-hidden="true"></i> PDF für die Akte</a>
                            @endif
                        </div>
                        @if(! $assignment->isComplete())
                            <p class="ea-hint mb-0">Solange Bestätigungen fehlen, wird ein Ausdruck nicht als „gedruckt“ vermerkt.</p>
                        @endif
                    @endif
                    @if($assignment->note)
                        <div class="ea-alert ea-alert-neutral"><i class="fas fa-sticky-note" aria-hidden="true"></i><p class="mb-0"><strong>Bearbeitungshinweis:</strong> {{ $assignment->note }}</p></div>
                    @endif
                    <div>
                        <p class="ea-section-title"><i class="fas fa-user-friends" aria-hidden="true"></i> Berechtigte Konten (Sorgerecht zum Startzeitpunkt)</p>
                        <ul class="grid gap-2 sm:grid-cols-2 mb-0">
                            @forelse($assignment->recipients as $recipient)
                                <li class="flex items-center gap-3 p-3 rounded-xl ea-subtle">
                                    <span class="ea-avatar !w-9 !h-9 !text-xs">{{ mb_strtoupper(mb_substr($recipient->user?->name ?? '?', 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <span class="block font-medium ea-text truncate">{{ $recipient->user?->name }}</span>
                                        <span class="block text-xs ea-muted truncate">{{ $recipient->user?->email ?: 'keine E-Mail' }}</span>
                                        @if($recipient->delivery_failed_at)<span class="block text-xs text-red-600">– nicht zustellbar: {{ $recipient->delivery_error }}</span>@endif
                                    </span>
                                </li>
                            @empty
                                <li class="text-red-600">Keine berechtigten Konten.</li>
                            @endforelse
                        </ul>
                    </div>
                    @if($assignment->drafts->isNotEmpty())
                        <p class="ea-muted mb-0"><i class="fas fa-pen" aria-hidden="true"></i> Entwurf begonnen von: {{ $assignment->drafts->pluck('user.name')->filter()->implode(', ') }}</p>
                    @endif
                </div>
            </section>

            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-code-branch" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">Antwortversionen</h2>
                </div>
                <div class="ea-card-body space-y-3">
                    @forelse($assignment->responses as $response)
                        <div class="rounded-xl p-4 text-sm flex flex-wrap items-start justify-between gap-3" style="border: 1px solid {{ $response->is_current ? '#34d399' : 'var(--color-card-border)' }}">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong class="ea-text">Version {{ $response->version }}</strong>
                                    @if($response->is_current) <x-elternabfragen.badge color="green">gültig</x-elternabfragen.badge> @endif
                                    <x-elternabfragen.badge :color="$response->review_status->color()">{{ $response->review_status->label() }}</x-elternabfragen.badge>
                                </div>
                                <span class="block ea-muted">{{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }} · {{ $response->channel === 'app' ? 'App' : 'Web' }}</span>
                                @if($response->correction_reason)<span class="block ea-muted">Korrekturgrund: {{ $response->correction_reason }}</span>@endif
                                @foreach($response->confirmations as $confirmation)
                                    <span class="block text-emerald-700 dark:text-emerald-400"><i class="fas fa-user-check" aria-hidden="true"></i> Bestätigt von {{ $confirmation->user?->name ?? 'unbekannt' }} am {{ $confirmation->confirmed_at->format('d.m.Y H:i') }} · {{ $confirmation->channel === 'app' ? 'App' : 'Web' }}</span>
                                @endforeach
                                @if($response->is_current && $pendingConfirmations->isNotEmpty())
                                    <span class="block text-amber-700 dark:text-amber-400"><i class="far fa-clock" aria-hidden="true"></i> Bestätigung ausstehend: {{ $pendingConfirmations->pluck('name')->implode(', ') }}</span>
                                @endif
                            </div>
                            @if($canSeeAnswers)
                                <a href="{{ route('elternabfragen.admin.responses.show', $response) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-eye" aria-hidden="true"></i> Antwort ansehen</a>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm ea-muted mb-0">Noch keine Antwort eingereicht.</p>
                    @endforelse
                    @unless($canSeeAnswers)
                        <p class="ea-hint"><i class="fas fa-lock" aria-hidden="true"></i> Antwortinhalte sind nur mit dem Recht „Antwortinhalte einsehen“ sichtbar.</p>
                    @endunless
                </div>
            </section>

            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-bell" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">Zustellungen und Erinnerungen</h2>
                </div>
                <ul class="ea-divide mb-0 text-sm">
                    @forelse($assignment->deliveries as $delivery)
                        <li class="px-5 py-3 flex items-start gap-3 {{ $delivery->success ? 'ea-text' : 'text-red-600' }}">
                            <i class="{{ $delivery->success ? 'fas fa-check-circle text-emerald-500' : 'fas fa-times-circle' }} mt-0.5" aria-hidden="true"></i>
                            <span>
                                {{ $delivery->sent_at->format('d.m.Y H:i') }} · {{ $delivery->kindLabel() }} · {{ $delivery->channelLabel() }} · {{ $delivery->user?->name }}
                                {{ $delivery->trigger === 'manual' ? '(manuell)' : '' }}
                                @if(! $delivery->success) – {{ $delivery->error }} @endif
                            </span>
                        </li>
                    @empty
                        <li class="px-5 py-3 ea-muted">–</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside class="space-y-6">
            @can('manage elternabfragen')
                @if($inquiry->isRunning())
                    <section class="ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-tools" aria-hidden="true"></i></div>
                            <h2 class="ea-card-title">Bearbeiten</h2>
                        </div>
                        <div class="ea-card-body space-y-5 text-sm">
                            @unless($assignment->isAnswered())
                                <form method="POST" action="{{ route('elternabfragen.admin.assignments.remind', $inquiry) }}">@csrf
                                    <input type="hidden" name="scope" value="selected"><input type="hidden" name="assignment_ids[]" value="{{ $assignment->id }}">
                                    <button class="ea-btn ea-btn-primary w-full"><i class="fas fa-bell" aria-hidden="true"></i> Erinnern</button>
                                </form>
                                <form method="POST" action="{{ route('elternabfragen.admin.assignments.extend', $assignment) }}" class="space-y-2">@csrf
                                    <label class="ea-label" for="a_extend">Individuelle Nachfrist</label>
                                    <input id="a_extend" type="datetime-local" name="extended_until" required class="ea-input" value="{{ $defaultDate }}">
                                    <input name="reason" required class="ea-input" placeholder="Grund / Rückfrage (Pflicht)">
                                    <button class="ea-btn ea-btn-secondary w-full"><i class="far fa-calendar-plus" aria-hidden="true"></i> Nachfrist setzen</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('elternabfragen.admin.assignments.correction', $assignment) }}" class="space-y-2">@csrf
                                    <label class="ea-label" for="a_correction">Korrektur durch Eltern freigeben bis</label>
                                    <input id="a_correction" type="datetime-local" name="correction_until" required class="ea-input" value="{{ $defaultDate }}">
                                    <input name="reason" required class="ea-input" placeholder="Grund (Pflicht)">
                                    <button class="ea-btn ea-btn-secondary w-full"><i class="fas fa-unlock" aria-hidden="true"></i> Korrektur freigeben</button>
                                </form>
                            @endunless
                            @if($assignment->has_problem)
                                <form method="POST" action="{{ route('elternabfragen.admin.assignments.resolve', $assignment) }}" class="space-y-2 pt-4" style="border-top: 1px solid var(--color-card-border)">@csrf
                                    <input name="note" class="ea-input" placeholder="Lösung (optional)">
                                    <button class="ea-btn ea-btn-success w-full"><i class="fas fa-check" aria-hidden="true"></i> Problem gelöst</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('elternabfragen.admin.assignments.problem', $assignment) }}" class="space-y-2 pt-4" style="border-top: 1px solid var(--color-card-border)">@csrf
                                    <input name="note" required class="ea-input" placeholder="z. B. E-Mail-Adresse ungültig">
                                    <button class="ea-btn ea-btn-danger w-full"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Technisches Problem melden</button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif
                @if(in_array($inquiry->status, [\App\Enums\InquiryStatus::Running, \App\Enums\InquiryStatus::Closed], true) && ! $inquiry->purged_at)
                    <section class="ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-redo" aria-hidden="true"></i></div>
                            <h2 class="ea-card-title">Für diese Familie neu starten</h2>
                        </div>
                        <form method="POST" action="{{ route('elternabfragen.admin.assignments.restart', $assignment) }}" class="ea-card-body space-y-2 text-sm"
                              onsubmit="return confirm('Abfrage für diese Familie neu starten? Die bisherige Antwort gilt dann nicht mehr.')">@csrf
                            <p class="ea-muted mb-2">
                                Die Sorgeberechtigten werden nach aktuellem Stand neu ermittelt und benachrichtigt.
                                @if($assignment->isAnswered()) Die bisherige Antwort bleibt als Version erhalten, gilt aber nicht mehr. @endif
                                @if($inquiry->status === \App\Enums\InquiryStatus::Closed) Die Abfrage bleibt für alle anderen abgeschlossen. @endif
                            </p>
                            <label class="ea-label" for="a_restart">Abgabe möglich bis</label>
                            <input id="a_restart" type="datetime-local" name="restart_until" required class="ea-input" value="{{ now()->addWeeks(2)->setTime(23, 59)->format('Y-m-d\TH:i') }}">
                            <input name="reason" required maxlength="1000" class="ea-input" placeholder="Grund, z. B. Sorgerecht geändert (Pflicht)">
                            <button class="ea-btn ea-btn-secondary w-full"><i class="fas fa-redo" aria-hidden="true"></i> Neu starten</button>
                        </form>
                    </section>
                @endif
                <section class="ea-card">
                    <form method="POST" action="{{ route('elternabfragen.admin.assignments.note', $assignment) }}" class="ea-card-body space-y-2 text-sm">@csrf
                        <label class="ea-label" for="a_note"><i class="fas fa-sticky-note ea-muted mr-1" aria-hidden="true"></i> Bearbeitungshinweis</label>
                        <textarea id="a_note" name="note" rows="3" required class="ea-input" placeholder="z. B. Klärung widersprüchlicher Angaben mit beiden Elternteilen">{{ $assignment->note }}</textarea>
                        <button class="ea-btn ea-btn-secondary w-full">Speichern</button>
                    </form>
                </section>
            @endcan

            @include('elternabfragen.admin._protocol', ['events' => $assignment->events])
        </aside>
    </div>
</div>
@endsection
