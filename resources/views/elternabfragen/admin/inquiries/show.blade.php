@extends('layouts.app')

@section('title', '| Elternabfragen · '.$inquiry->title)

@section('content')
@php
    $status = $inquiry->status->value;
    $rules = app(\App\Settings\ElternabfragenSetting::class);
    $fourEyesBlocked = $rules->four_eyes && (int) $inquiry->created_by === auth()->id();
    $total = $statusCounts->sum();
    $answered = ($statusCounts['submitted'] ?? 0) + ($statusCounts['corrected'] ?? 0);
    $percent = $total > 0 ? round($answered / $total * 100) : 0;
    $kindLabels = [
        'release' => 'Benachrichtigt', 'reminder_before' => 'Erinnert', 'reminder_after' => 'Erinnert', 'reminder_manual' => 'Erinnert',
        'confirmation' => 'Eingang bestätigt', 'confirmation_request' => 'Um Bestätigung gebeten', 'problem_notice' => 'Zustellproblem gemeldet',
    ];
    // Lebenszyklus für die Schrittanzeige
    $lifecycle = ['draft' => 'Entwurf', 'pending_approval' => 'Freigabe', 'scheduled' => 'Geplant', 'running' => 'Läuft', 'closed' => 'Abgeschlossen'];
    $lifecycleIndex = array_search($status, array_keys($lifecycle), true);
    $statusStyles = [
        'open' => ['icon' => 'far fa-circle', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700/60 dark:text-gray-300'],
        'in_progress' => ['icon' => 'fas fa-pen', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
        'submitted' => ['icon' => 'fas fa-check', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
        'corrected' => ['icon' => 'fas fa-redo', 'class' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
        'awaiting_confirmation' => ['icon' => 'fas fa-user-friends', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
        'problem' => ['icon' => 'fas fa-exclamation-triangle', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
    ];
    // Kreisdiagramm: Umfang bei r = 42
    $circumference = 2 * M_PI * 42;
@endphp
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', [
        'title' => $inquiry->title,
        'subtitle' => $inquiry->templateVersion->label(),
        'icon' => 'fas fa-paper-plane',
        'back' => ['url' => route('elternabfragen.admin.index'), 'label' => 'Alle Abfragen'],
    ])

    {{-- Lebenszyklus --}}
    <div class="ea-card p-5 mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2">
                <x-elternabfragen.badge :color="$inquiry->status->color()" dot class="text-sm">{{ $inquiry->status->label() }}</x-elternabfragen.badge>
                @if($inquiry->isRunning() && $inquiry->deadlinePassed())
                    <x-elternabfragen.badge color="red" icon="far fa-clock">Frist abgelaufen</x-elternabfragen.badge>
                @endif
            </div>
            {{-- Aktionen --}}
            <div class="flex flex-wrap gap-2">
                @canany(['manage elternabfragen', 'approve elternabfragen'])
                    <a href="{{ route('elternabfragen.admin.inquiries.preview', $inquiry) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-eye" aria-hidden="true"></i> Elternvorschau</a>
                @endcanany
                @can('manage elternabfragen')
                    @if($status === 'draft')
                        <a href="{{ route('elternabfragen.admin.inquiries.edit', $inquiry) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-pen" aria-hidden="true"></i> Bearbeiten</a>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.submit', $inquiry) }}">@csrf
                            <button class="ea-btn ea-btn-success ea-btn-sm"><i class="fas fa-paper-plane" aria-hidden="true"></i> Zur Freigabe einreichen</button>
                        </form>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('Entwurf löschen?')">@csrf @method('DELETE')
                            <button class="ea-btn ea-btn-danger ea-btn-sm"><i class="fas fa-trash" aria-hidden="true"></i> Löschen</button>
                        </form>
                    @endif
                    @if($status === 'running')
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.sync', $inquiry) }}">@csrf
                            <button class="ea-btn ea-btn-secondary ea-btn-sm" title="Neu hinzugekommene Kinder der Zielgruppe ergänzen"><i class="fas fa-sync" aria-hidden="true"></i> Zielgruppe abgleichen</button>
                        </form>
                    @endif
                    @if(in_array($status, ['scheduled', 'running', 'closed'], true))
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.duplicate', $inquiry) }}">@csrf
                            <button class="ea-btn ea-btn-secondary ea-btn-sm" title="Inhaltliche Änderungen nur als neue Abfrage"><i class="fas fa-copy" aria-hidden="true"></i> Als neue Abfrage kopieren</button>
                        </form>
                    @endif
                    @if(in_array($status, ['scheduled', 'running'], true))
                        <a href="#beenden" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-lock" aria-hidden="true"></i> {{ $inquiry->deadlinePassed() ? 'Abschließen' : 'Vorzeitig beenden' }}</a>
                    @endif
                @endcan
                @can('export elternabfragen')
                    @if($inquiry->isReleased() && ! $inquiry->purged_at)
                        <a href="{{ route('elternabfragen.admin.exports.create', $inquiry) }}" class="ea-btn ea-btn-primary ea-btn-sm"><i class="fas fa-file-export" aria-hidden="true"></i> Export</a>
                    @endif
                @endcan
                @can('view elternabfragen answers')
                    @if($inquiry->isReleased() && ! $inquiry->purged_at)
                        <a href="#akte" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-folder-open" aria-hidden="true"></i> PDF für die Schülerakte</a>
                    @endif
                @endcan
            </div>
        </div>
        <ol class="ea-steps" aria-label="Ablauf der Abfrage">
            @foreach($lifecycle as $key => $text)
                @php
                    $i = $loop->index;
                    $done = $i < $lifecycleIndex || ($status === 'closed' && $key === 'closed');
                    $isCurrent = $i === $lifecycleIndex && $status !== 'closed';
                @endphp
                <li class="ea-step {{ $done ? 'ea-step-done' : '' }} {{ $isCurrent ? 'ea-step-current' : '' }}" @if($isCurrent) aria-current="step" @endif>
                    <span class="ea-step-dot">@if($done)<i class="fas fa-check" aria-hidden="true"></i>@else{{ $i + 1 }}@endif</span>
                    <span class="ea-step-label">{{ $text }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    @if($inquiry->review_note)
        <div class="ea-alert ea-alert-neutral mb-5"><i class="fas fa-comment-dots" aria-hidden="true"></i><p class="mb-0"><strong>Prüfvermerk:</strong> {{ $inquiry->review_note }}</p></div>
    @endif
    @if($inquiry->templateVersion->record_relevant && $unfiledCount > 0)
        <div class="ea-alert ea-alert-warning mb-5"><i class="fas fa-folder-open" aria-hidden="true"></i>
            <p class="mb-0"><strong>Aktenrelevant:</strong> {{ $unfiledCount }} vollständige {{ $unfiledCount === 1 ? 'Antwort wurde' : 'Antworten wurden' }} noch nicht für die Schülerakte gedruckt.
                @if($purgeDate && $purgeDate->isPast()) Die Aufbewahrungsfrist ist abgelaufen – die Löschung wird zurückgehalten, bis alle Antworten gedruckt sind.
                @else Solange werden die Antworten nicht automatisch gelöscht. @endif
                @can('view elternabfragen answers') <a href="{{ route('elternabfragen.admin.records.inquiry', [$inquiry, 'nur_neue' => 1]) }}" class="ea-link font-semibold">Noch nicht gedruckte jetzt als PDF erstellen</a> @endcan
            </p>
        </div>
    @endif
    @if($inquiry->purged_at)
        <div class="ea-alert ea-alert-neutral mb-5"><i class="fas fa-trash-restore" aria-hidden="true"></i><p class="mb-0">Antworten wurden am {{ $inquiry->purged_at->format('d.m.Y') }} nach Ablauf der Aufbewahrungsfrist gelöscht.</p></div>
    @endif

    <div class="grid gap-6 xl:grid-cols-4">
        <div class="xl:col-span-3 space-y-6 min-w-0">
            {{-- Kennzahlen --}}
            @if($inquiry->isReleased())
                <div class="grid gap-4 lg:grid-cols-[minmax(0,260px)_1fr]">
                    <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="ea-card p-5 flex items-center gap-5 {{ request('status') ? '' : 'ea-stat-active' }}">
                        <svg viewBox="0 0 100 100" class="w-24 h-24 flex-shrink-0 -rotate-90" role="img" aria-label="Rücklauf {{ $percent }} Prozent">
                            <circle cx="50" cy="50" r="42" fill="none" stroke-width="10" style="stroke: color-mix(in srgb, var(--color-text-secondary) 18%, transparent)"/>
                            <circle cx="50" cy="50" r="42" fill="none" stroke-width="10" stroke-linecap="round" class="stroke-emerald-500"
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference * (1 - $percent / 100) }}"/>
                        </svg>
                        <div>
                            <p class="text-3xl font-bold ea-text mb-0 leading-none">{{ $percent }} %</p>
                            <p class="text-sm ea-muted mb-0 mt-1">Rücklauf</p>
                            <p class="text-xs ea-muted mb-0 mt-1">{{ $answered }} von {{ $total }} Kindern</p>
                        </div>
                    </a>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($statusOptions as $value => $text)
                            @php $style = $statusStyles[$value] ?? $statusStyles['open']; @endphp
                            <a href="{{ route('elternabfragen.admin.inquiries.show', [$inquiry, 'status' => $value]) }}"
                               class="ea-stat {{ request('status') === $value ? 'ea-stat-active' : '' }}">
                                <span class="ea-stat-icon {{ $style['class'] }}"><i class="{{ $style['icon'] }}" aria-hidden="true"></i></span>
                                <span class="min-w-0">
                                    <span class="ea-stat-value block {{ $value === 'problem' && ($statusCounts[$value] ?? 0) > 0 ? '!text-red-600' : '' }}">{{ $statusCounts[$value] ?? 0 }}</span>
                                    <span class="ea-stat-label block leading-tight">{{ $text }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Änderungen gegenüber der Vorabfrage --}}
            @if($changeCounts && $inquiry->isReleased() && ! $inquiry->purged_at)
                <div class="ea-alert ea-alert-warning flex-wrap items-center justify-between">
                    <p class="mb-0 flex-1 min-w-[16rem]">
                        <i class="fas fa-history mr-1" aria-hidden="true"></i>
                        Vorbefüllt aus „{{ $inquiry->previousInquiry?->title ?? 'gelöschte Abfrage' }}“:
                        <strong>{{ $changeCounts['changed'] }}</strong> {{ $changeCounts['changed'] === 1 ? 'Kind' : 'Kinder' }} mit geänderten Angaben,
                        {{ $changeCounts['unchanged'] }} unverändert, {{ $changeCounts['without_baseline'] }} ohne Vorjahresangaben.
                    </p>
                    @can('view elternabfragen answers')
                        <a href="{{ route('elternabfragen.admin.inquiries.changes', $inquiry) }}" class="ea-btn ea-btn-warning ea-btn-sm">
                            <i class="fas fa-list" aria-hidden="true"></i> Änderungen anzeigen
                        </a>
                    @endcan
                </div>
            @endif

            {{-- Vorschau vor dem Start --}}
            @if($preview)
                <section class="ea-card">
                    <div class="ea-card-header">
                        <div class="ea-card-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                        <div>
                            <h2 class="ea-card-title">Erwartete Zielgruppe</h2>
                            <p class="ea-card-sub">{{ $inquiry->targetLabel() }}</p>
                        </div>
                    </div>
                    <div class="ea-card-body space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="ea-stat">
                                <span class="ea-stat-icon bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"><i class="fas fa-child" aria-hidden="true"></i></span>
                                <span><span class="ea-stat-value block">{{ $preview['children'] }}</span><span class="ea-stat-label block">Kinder</span></span>
                            </div>
                            <div class="ea-stat">
                                <span class="ea-stat-icon bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300"><i class="fas fa-user-friends" aria-hidden="true"></i></span>
                                <span><span class="ea-stat-value block">{{ $preview['accounts'] }}</span><span class="ea-stat-label block">berechtigte Elternkonten</span></span>
                            </div>
                        </div>
                        @if($preview['by_group'])
                            <div class="flex flex-wrap gap-2">
                                @foreach($preview['by_group'] as $groupName => $n)
                                    <span class="ea-chip !py-1 text-xs">{{ $groupName }} <strong>{{ $n }}</strong></span>
                                @endforeach
                            </div>
                        @endif
                        @if($preview['without_accounts'])
                            <div class="ea-alert ea-alert-danger"><i class="fas fa-user-slash" aria-hidden="true"></i>
                                <p class="mb-0"><strong>{{ count($preview['without_accounts']) }} Kinder ohne sorgeberechtigtes Konto</strong> (werden als technisches Problem markiert): {{ implode(', ', $preview['without_accounts']) }}</p>
                            </div>
                        @endif
                        @if($preview['without_email'] && $inquiry->notify_mail)
                            <div class="ea-alert ea-alert-warning"><i class="fas fa-envelope" aria-hidden="true"></i>
                                <p class="mb-0">Ohne E-Mail-Adresse: {{ implode(', ', $preview['without_email']) }}</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Freigabe --}}
            @if($status === 'pending_approval')
                @can('approve elternabfragen')
                    <section id="pruefung" class="ea-card scroll-mt-4" style="border: 2px solid #f59e0b">
                        <div class="ea-card-header">
                            <div class="ea-card-icon !bg-amber-100 !text-amber-700"><i class="fas fa-user-check" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="ea-card-title">Verbindliche Freigabe</h2>
                                <p class="ea-card-sub">Erst mit der Freigabe werden Aufträge erzeugt und Eltern benachrichtigt.</p>
                            </div>
                        </div>
                        <div class="ea-card-body">
                            @if($fourEyesBlocked)
                                <div class="ea-alert ea-alert-warning"><i class="fas fa-user-lock" aria-hidden="true"></i><p class="mb-0">Vier-Augen-Prinzip: Diese Abfrage muss von einer anderen Person freigegeben werden.</p></div>
                            @else
                                <div class="grid gap-6 lg:grid-cols-2">
                                    <form method="POST" action="{{ route('elternabfragen.admin.inquiries.approve', $inquiry) }}" class="space-y-3">
                                        @csrf
                                        <label class="ea-toggle-row text-sm ea-text">
                                            <input type="checkbox" name="confirm" value="1" class="ea-check mt-0.5" required>
                                            <span>Zielgruppe, betroffene Kinder, Frist, Absender, Pflichtfelder, Hinweise und Benachrichtigungskanäle sind geprüft
                                                (<a href="{{ route('elternabfragen.admin.inquiries.preview', $inquiry) }}" class="ea-link" target="_blank">Elternvorschau</a>).</span>
                                        </label>
                                        <textarea name="note" rows="2" class="ea-input" placeholder="Prüfvermerk (optional)"></textarea>
                                        <button class="ea-btn ea-btn-success w-full">
                                            <i class="fas fa-check-double" aria-hidden="true"></i>
                                            {{ $inquiry->starts_at->isPast() ? 'Freigeben und jetzt starten' : 'Freigeben (Start '.$inquiry->starts_at->format('d.m.Y H:i').')' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('elternabfragen.admin.inquiries.reject', $inquiry) }}" class="space-y-3 lg:pl-6 lg:border-l ea-border">
                                        @csrf
                                        <p class="text-sm ea-muted mb-0">Etwas passt nicht? Geben Sie die Abfrage mit einer kurzen Begründung zurück.</p>
                                        <textarea name="note" rows="3" required class="ea-input" placeholder="Was soll überarbeitet werden?"></textarea>
                                        <button class="ea-btn ea-btn-danger w-full"><i class="fas fa-undo" aria-hidden="true"></i> Zur Überarbeitung zurückgeben</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </section>
                @endcan
            @endif

            {{-- Rücklauf --}}
            @if($inquiry->isReleased() && ! $inquiry->purged_at)
                <section class="ea-card overflow-hidden">
                    <div class="ea-card-header">
                        <div class="ea-card-icon"><i class="fas fa-clipboard-list" aria-hidden="true"></i></div>
                        <div class="flex-1">
                            <h2 class="ea-card-title">Rücklauf je Kind</h2>
                            <p class="ea-card-sub">Status, berechtigte Konten und Benachrichtigungen – ohne Antwortinhalte</p>
                        </div>
                    </div>
                    <form method="GET" class="px-5 py-4 flex flex-wrap items-end gap-3 ea-subtle" style="border-bottom: 1px solid var(--color-card-border)">
                        <div>
                            <label for="f_status" class="ea-label !text-xs">Status</label>
                            <select id="f_status" name="status" class="ea-input !py-2">
                                <option value="">alle</option>
                                @foreach($statusOptions as $value => $text)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f_klasse" class="ea-label !text-xs">Klasse</label>
                            <select id="f_klasse" name="klasse" class="ea-input !py-2">
                                <option value="">alle</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" @selected((int) request('klasse') === $group->id)>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f_frist" class="ea-label !text-xs">Frist</label>
                            <select id="f_frist" name="frist" class="ea-input !py-2">
                                <option value="">alle</option>
                                <option value="nachfrist" @selected(request('frist') === 'nachfrist')>mit Nachfrist</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label for="f_q" class="ea-label !text-xs">Kind oder Elternkonto</label>
                            <input id="f_q" name="q" value="{{ request('q') }}" class="ea-input !py-2" placeholder="Name suchen …">
                        </div>
                        <button class="ea-btn ea-btn-secondary"><i class="fas fa-filter" aria-hidden="true"></i> Filtern</button>
                    </form>

                    <form method="POST" action="{{ route('elternabfragen.admin.assignments.remind', $inquiry) }}" x-data="{ selected: [] }">
                        @csrf
                        @can('manage elternabfragen')
                            @if($inquiry->isRunning())
                                <div class="px-5 py-3 flex flex-wrap items-center gap-2 text-sm" style="border-bottom: 1px solid var(--color-card-border)">
                                    <button name="scope" value="open" class="ea-btn ea-btn-primary ea-btn-sm" onclick="return confirm('Alle offenen Fälle erinnern? Fälle innerhalb der Wartezeit werden übersprungen.')">
                                        <i class="fas fa-bell" aria-hidden="true"></i> Alle offenen erinnern
                                    </button>
                                    <button name="scope" value="selected" class="ea-btn ea-btn-secondary ea-btn-sm" :disabled="selected.length === 0">
                                        Ausgewählte erinnern (<span x-text="selected.length"></span>)
                                    </button>
                                    <span class="text-xs ea-muted ml-auto"><i class="fas fa-info-circle" aria-hidden="true"></i> Regeln: erste Erinnerung frühestens nach {{ $rules->first_reminder_after_hours }} h, danach höchstens alle {{ $rules->reminder_interval_hours }} h, max. {{ $rules->max_reminders }}×.</span>
                                </div>
                            @endif
                        @endcan
                        <div class="overflow-x-auto">
                            <table class="ea-table">
                                <thead>
                                    <tr>
                                        <th class="w-10"><span class="sr-only">Auswahl</span></th>
                                        <th>Kind</th>
                                        <th>Klasse</th>
                                        <th class="min-w-[13rem]">Status</th>
                                        <th>Berechtigte Konten</th>
                                        <th>Letzte Benachrichtigung</th>
                                        <th>Hinweise</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignments as $assignment)
                                        @php
                                            $deliveries = $lastDeliveries->get($assignment->id, collect());
                                            $last = $deliveries->sortByDesc('sent_at')->first();
                                            $blocked = $reminderBlocks[$assignment->id] ?? null;
                                            $initials = mb_strtoupper(mb_substr($assignment->child?->first_name ?? '?', 0, 1).mb_substr($assignment->child?->last_name ?? '', 0, 1));
                                        @endphp
                                        <tr class="{{ $assignment->has_problem ? 'bg-red-50/60 dark:bg-red-900/10' : '' }}">
                                            <td>
                                                @if(! $assignment->isComplete())
                                                    <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->id }}" x-model="selected" class="ea-check mt-1" aria-label="{{ $assignment->childName() }} auswählen" @disabled($blocked !== null) title="{{ $blocked }}">
                                                @endif
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <span class="ea-avatar !w-8 !h-8 !text-xs">{{ $initials }}</span>
                                                    <span class="font-medium whitespace-nowrap">{{ $assignment->childName() }}</span>
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap">{{ $assignment->group?->name ?? '–' }}</td>
                                            <td>
                                                <x-elternabfragen.badge :color="$assignment->status->color()" dot>{{ $assignment->status->label() }}</x-elternabfragen.badge>
                                                @if($assignment->currentResponse)
                                                    <span class="block text-xs ea-muted mt-1">{{ $assignment->currentResponse->submitted_at->format('d.m. H:i') }} · v{{ $assignment->currentResponse->version }} · {{ $assignment->currentResponse->review_status->label() }}</span>
                                                    @if($assignment->currentResponse->baselineChanges() !== [])
                                                        <span class="block text-xs text-amber-600 dark:text-amber-400"><i class="fas fa-history" aria-hidden="true"></i> {{ count($assignment->currentResponse->baselineChanges()) }} Änderung(en) zur Vorabfrage</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="text-xs">
                                                @forelse($assignment->recipients as $recipient)
                                                    <span class="block whitespace-nowrap {{ $recipient->delivery_failed_at ? 'text-red-600' : '' }}" title="{{ $recipient->delivery_error }}">
                                                        {{ $recipient->user?->name }} @if($recipient->delivery_failed_at)<i class="fas fa-exclamation-triangle" aria-label="nicht zustellbar"></i>@endif
                                                    </span>
                                                @empty
                                                    <span class="text-red-600">keine</span>
                                                @endforelse
                                            </td>
                                            <td class="text-xs whitespace-nowrap">
                                                @if($last)
                                                    {{ $kindLabels[$last->kind] ?? $last->kind }} {{ \Carbon\Carbon::parse($last->sent_at)->format('d.m. H:i') }}
                                                    @if($assignment->reminder_count) <span class="block ea-muted">{{ $assignment->reminder_count }}× erinnert</span> @endif
                                                @else <span class="ea-muted">–</span> @endif
                                            </td>
                                            <td class="text-xs max-w-xs">
                                                @if($assignment->reopened_at)<span class="block"><i class="fas fa-redo" aria-hidden="true"></i> Neu gestartet am {{ $assignment->reopened_at->format('d.m.Y') }}</span>@endif
                                                @if($assignment->extended_until)<span class="block"><i class="far fa-calendar-plus" aria-hidden="true"></i> {{ $assignment->reopened_at ? 'Frist' : 'Nachfrist' }} bis {{ $assignment->extended_until->format('d.m.Y') }}</span>@endif
                                                @if($assignment->isFiled())<span class="block text-emerald-700 dark:text-emerald-400"><i class="fas fa-folder" aria-hidden="true"></i> Akte: gedruckt {{ $assignment->filed_at->format('d.m.Y') }}</span>
                                                @elseif($inquiry->templateVersion->record_relevant && $assignment->isComplete())<span class="block text-amber-600"><i class="fas fa-folder-open" aria-hidden="true"></i> Akte: noch nicht gedruckt</span>@endif
                                                @if($assignment->has_problem)<span class="block text-red-600">{{ \Illuminate\Support\Str::limit($assignment->problem_note, 80) }}</span>@endif
                                                @if($assignment->note)<span class="block ea-muted">{{ \Illuminate\Support\Str::limit($assignment->note, 80) }}</span>@endif
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('elternabfragen.admin.assignments.show', $assignment) }}" class="ea-btn ea-btn-ghost ea-btn-sm">Details <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8"><div class="ea-empty !py-8"><i class="fas fa-filter text-2xl mb-2" aria-hidden="true"></i>Keine Fälle für diesen Filter.</div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-info" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">Eckdaten</h2>
                </div>
                <dl class="ea-facts px-5 py-2 mb-0">
                    <div><dt><i class="fas fa-layer-group" aria-hidden="true"></i> Vorlage</dt><dd><a href="{{ route('elternabfragen.admin.templates.show', $inquiry->templateVersion) }}" class="ea-link">{{ $inquiry->templateVersion->label() }}</a></dd></div>
                    @if($inquiry->previous_inquiry_id)
                        <div><dt><i class="fas fa-history" aria-hidden="true"></i> Vorbefüllt aus</dt>
                            <dd>@if($inquiry->previousInquiry)<a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry->previousInquiry) }}" class="ea-link">{{ $inquiry->previousInquiry->title }}</a>@else – @endif</dd>
                        </div>
                    @endif
                    <div><dt><i class="fas fa-users" aria-hidden="true"></i> Zielgruppe</dt><dd>{{ $inquiry->targetLabel() }}</dd></div>
                    <div><dt><i class="fas fa-play" aria-hidden="true"></i> Start</dt><dd>{{ $inquiry->starts_at->format('d.m.Y H:i') }}</dd></div>
                    <div><dt><i class="fas fa-flag-checkered" aria-hidden="true"></i> Frist</dt><dd>{{ $inquiry->deadline_at->format('d.m.Y H:i') }}</dd></div>
                    <div><dt><i class="far fa-user" aria-hidden="true"></i> Verantwortlich</dt><dd>{{ $inquiry->responsible?->name ?? '–' }}</dd></div>
                    <div><dt><i class="fas fa-bullhorn" aria-hidden="true"></i> Kanäle</dt><dd>{{ collect([$inquiry->notify_mail ? 'E-Mail' : null, $inquiry->notify_push ? 'App/Web-Push' : null])->filter()->implode(', ') ?: '–' }}</dd></div>
                    <div><dt><i class="fas fa-bell" aria-hidden="true"></i> Autom. Erinnerungen</dt><dd>{{ $inquiry->auto_reminders ? 'ja' : 'nein' }}</dd></div>
                    <div><dt><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> Für Mitarbeitende lesbar</dt><dd>{{ $inquiry->staff_visible ? 'ja (nur geprüfte Antworten)' : 'nein' }}</dd></div>
                    <div><dt><i class="fas fa-pen" aria-hidden="true"></i> Selbstkorrektur</dt><dd>{{ $inquiry->correction_window_days }} Tage</dd></div>
                    <div><dt><i class="fas fa-archive" aria-hidden="true"></i> Aufbewahrung</dt><dd>{{ $inquiry->retention_days ? $inquiry->retention_days.' Tage nach Abschluss' : 'unbefristet (manuelle Löschung)' }}@if($purgeDate && ! $inquiry->purged_at)<span class="block text-xs ea-muted">Löschung ab {{ $purgeDate->format('d.m.Y') }}</span>@endif</dd></div>
                    @if($inquiry->templateVersion->record_relevant)<div><dt><i class="fas fa-folder-open" aria-hidden="true"></i> Schülerakte</dt><dd>aktenrelevant</dd></div>@endif
                    @if($inquiry->approved_at)<div><dt><i class="fas fa-user-check" aria-hidden="true"></i> Freigegeben</dt><dd>{{ $inquiry->approved_at->format('d.m.Y H:i') }} von {{ $inquiry->approver?->name }}</dd></div>@endif
                    @if($inquiry->expected_children !== null)<div><dt><i class="fas fa-calculator" aria-hidden="true"></i> Erwartet</dt><dd>{{ $inquiry->expected_children }} Kinder / {{ $inquiry->expected_accounts }} Konten</dd></div>@endif
                    @if($inquiry->closed_at)<div><dt><i class="fas fa-lock" aria-hidden="true"></i> Abgeschlossen</dt><dd>{{ $inquiry->closed_at->format('d.m.Y H:i') }}@if($inquiry->closedEarly()) <span class="text-amber-600">(vorzeitig)</span>@endif</dd></div>@endif
                </dl>
            </section>

            @can('view elternabfragen answers')
                @if($inquiry->isReleased() && ! $inquiry->purged_at)
                    <section id="akte" class="ea-card scroll-mt-4">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-folder-open" aria-hidden="true"></i></div>
                            <div class="flex-1">
                                <h2 class="ea-card-title">PDF für die Schülerakte</h2>
                                <p class="ea-card-sub">Ein Blatt je Kind mit vollständiger Antwort</p>
                            </div>
                        </div>
                        <form method="GET" action="{{ route('elternabfragen.admin.records.inquiry', $inquiry) }}" class="ea-card-body space-y-3 text-sm">
                            <div>
                                <label for="r_klasse" class="ea-label">Klasse</label>
                                <select id="r_klasse" name="klasse" class="ea-input">
                                    <option value="">alle Klassen</option>
                                    @foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach
                                </select>
                            </div>
                            <label class="flex items-center gap-2 ea-text"><input type="checkbox" class="ea-check" name="nur_neue" value="1" @checked($unfiledCount > 0)> Nur noch nicht gedruckte Antworten</label>
                            <button class="ea-btn ea-btn-secondary w-full"><i class="fas fa-file-pdf" aria-hidden="true"></i> PDF erstellen</button>
                            <p class="ea-hint mb-0">Das Erstellen wird je Kind als „gedruckt“ vermerkt. Einzelne Blätter gibt es in den Details eines Falls.</p>
                        </form>
                    </section>
                @endif
            @endcan

            @can('manage elternabfragen')
                @if(in_array($status, ['scheduled', 'running', 'closed'], true) && ! $inquiry->purged_at)
                    <section id="vorabfrage" class="ea-card scroll-mt-4">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-history" aria-hidden="true"></i></div>
                            <h2 class="ea-card-title">Vorabfrage (z. B. Vorjahr)</h2>
                        </div>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.previous', $inquiry) }}" class="ea-card-body space-y-3">
                            @csrf @method('PUT')
                            <select name="previous_inquiry_id" class="ea-input" aria-label="Vorabfrage" aria-describedby="vorabfrage_hint">
                                <option value="">– keine –</option>
                                @foreach($previousInquiries as $previous)
                                    <option value="{{ $previous->id }}" @selected($inquiry->previous_inquiry_id === $previous->id)>{{ $previous->title }} · Frist {{ $previous->deadline_at->format('d.m.Y') }}</option>
                                @endforeach
                            </select>
                            @error('previous_inquiry_id')<p class="text-sm text-red-600 mb-0">{{ $message }}</p>@enderror
                            <p id="vorabfrage_hint" class="ea-hint !mt-0">
                                Wer noch nicht geantwortet hat, sieht die Angaben daraus vorausgefüllt. Bereits abgegebene Antworten werden sofort verglichen.
                                @if($previousInquiries->isEmpty()) Es gibt keine andere Abfrage derselben Vorlage mit noch vorhandenen Antworten. @endif
                            </p>
                            <button class="ea-btn ea-btn-secondary w-full">Speichern und vergleichen</button>
                        </form>
                    </section>
                @endif
                @if(in_array($status, ['scheduled', 'running'], true))
                    <section class="ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="far fa-calendar-alt" aria-hidden="true"></i></div>
                            <h2 class="ea-card-title">Frist ändern</h2>
                        </div>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.deadline', $inquiry) }}" class="ea-card-body space-y-3">
                            @csrf
                            <input type="datetime-local" name="deadline_at" required value="{{ $inquiry->deadline_at->format('Y-m-d\TH:i') }}" class="ea-input" aria-label="Neue Frist">
                            <input name="reason" required maxlength="1000" class="ea-input" placeholder="Begründung (wird protokolliert)">
                            <button class="ea-btn ea-btn-secondary w-full">Frist ändern</button>
                        </form>
                    </section>
                    <section class="ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-cog" aria-hidden="true"></i></div>
                            <h2 class="ea-card-title">Einstellungen</h2>
                        </div>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.settings', $inquiry) }}" class="ea-card-body space-y-3 text-sm">
                            @csrf @method('PUT')
                            <div>
                                <label for="s_responsible" class="ea-label">Verantwortliche Person</label>
                                <select id="s_responsible" name="responsible_user_id" class="ea-input">
                                    <option value="">–</option>
                                    @foreach($users as $u)<option value="{{ $u->id }}" @selected($inquiry->responsible_user_id === $u->id)>{{ $u->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label for="s_correction" class="ea-label">Selbstkorrektur (Tage)</label>
                                <input id="s_correction" type="number" name="correction_window_days" min="0" max="365" value="{{ $inquiry->correction_window_days }}" class="ea-input">
                            </div>
                            <div class="space-y-2 ea-text">
                                <label class="flex items-center gap-2"><input type="checkbox" class="ea-check" name="notify_mail" value="1" @checked($inquiry->notify_mail)> E-Mail</label>
                                <label class="flex items-center gap-2"><input type="checkbox" class="ea-check" name="notify_push" value="1" @checked($inquiry->notify_push)> App/Web-Push</label>
                                <label class="flex items-center gap-2"><input type="checkbox" class="ea-check" name="auto_reminders" value="1" @checked($inquiry->auto_reminders)> Automatische Erinnerungen</label>
                                <label class="flex items-center gap-2"><input type="checkbox" class="ea-check" name="staff_visible" value="1" @checked($inquiry->staff_visible)> Für Mitarbeitende lesbar</label>
                            </div>
                            <button class="ea-btn ea-btn-secondary w-full">Speichern</button>
                        </form>
                    </section>
                    <section id="beenden" class="ea-card scroll-mt-4">
                        <div class="ea-card-header">
                            <div class="ea-card-icon !bg-red-100 !text-red-600 dark:!bg-red-900/40"><i class="fas fa-lock" aria-hidden="true"></i></div>
                            <h2 class="ea-card-title">{{ $inquiry->deadlinePassed() ? 'Abfrage abschließen' : 'Abfrage vorzeitig beenden' }}</h2>
                        </div>
                        <div class="ea-card-body space-y-3 text-sm">
                            <p class="ea-muted mb-0">
                                @if($status === 'scheduled')
                                    Die Abfrage wird nicht gestartet, Eltern werden nicht benachrichtigt.
                                @else
                                    Danach sind keine Abgaben, Korrekturen, Bestätigungen und Erinnerungen mehr möglich. Bisherige Antworten bleiben erhalten.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('elternabfragen.admin.inquiries.close', $inquiry) }}" class="space-y-3"
                                  onsubmit="return confirm('Abfrage jetzt beenden? Das kann nicht rückgängig gemacht werden.')">
                                @csrf
                                <input name="note" maxlength="1000" class="ea-input" placeholder="Begründung (optional, wird protokolliert)">
                                <button class="ea-btn ea-btn-danger w-full"><i class="fas fa-lock" aria-hidden="true"></i> Jetzt beenden</button>
                            </form>
                        </div>
                    </section>
                @endif
            @endcan

            @can('manage elternabfragen')
                @if($status === 'closed' && ! $inquiry->purged_at)
                    <section class="ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon !bg-red-100 !text-red-600 dark:!bg-red-900/40"><i class="fas fa-trash-alt" aria-hidden="true"></i></div>
                            <h2 class="ea-card-title">Antworten löschen</h2>
                        </div>
                        <div class="ea-card-body space-y-3 text-sm">
                            <p class="ea-muted mb-0">
                                @if($inquiry->retention_days) Ohne Zutun werden die Antworten @if($purgeDate) ab {{ $purgeDate->format('d.m.Y') }} @endif automatisch gelöscht. Hier lassen sie sich vorher löschen, z. B. nach einem Widerruf.
                                @else Die Aufbewahrung ist unbefristet. Löschen Sie die Antworten, sobald sie nicht mehr benötigt werden, z. B. am Ende der Schulzeit. @endif
                                Erhalten bleiben die Abfrage und eine anonyme Statistik.
                            </p>
                            @if($inquiry->templateVersion->record_relevant && $unfiledCount > 0)
                                <p class="text-amber-700 dark:text-amber-400 mb-0"><i class="fas fa-folder-open" aria-hidden="true"></i> Erst möglich, wenn alle vollständigen Antworten für die Schülerakte gedruckt sind.</p>
                            @else
                                <form method="POST" action="{{ route('elternabfragen.admin.inquiries.purge', $inquiry) }}" class="space-y-3"
                                      onsubmit="return confirm('Alle Antworten dieser Abfrage endgültig löschen? Das kann nicht rückgängig gemacht werden.')">
                                    @csrf
                                    <input name="purge_reason" required maxlength="1000" class="ea-input" placeholder="Grund (wird protokolliert)">
                                    <button class="ea-btn ea-btn-danger w-full"><i class="fas fa-trash-alt" aria-hidden="true"></i> Antworten endgültig löschen</button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif
            @endcan

            @include('elternabfragen.admin._protocol', ['events' => $events])
        </aside>
    </div>
</div>
@endsection
