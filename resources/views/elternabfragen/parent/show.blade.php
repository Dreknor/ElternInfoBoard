@extends('layouts.app')

@section('title', '| '.$inquiry->title)

@section('content')
@php
    $current = $assignment->currentResponse;
    $isCorrection = $assignment->isAnswered() && $state['can_correct'] && $editing;
    $existing = $current?->answers ?? [];
    $overdue = $state['deadline']->isPast();
@endphp
<div class="max-w-2xl mx-auto px-4 py-6">
    @if($preview)
        <div class="ea-alert ea-alert-warning mb-4" role="status">
            <i class="fas fa-eye" aria-hidden="true"></i>
            <p class="mb-0">
                <strong>Vorschau aus Sicht der Eltern.</strong> Eingaben werden nicht gespeichert.
                @php($backUrl = $previewBack ?? ($inquiry->exists ? route('elternabfragen.admin.inquiries.show', $inquiry) : null))
                @if($backUrl)
                    <a href="{{ $backUrl }}" class="underline ml-1">{{ $previewBackLabel ?? 'Zurück zur Abfrage' }}</a>
                @else
                    Ungespeicherter Stand des Editors – schließen Sie diesen Tab, um weiterzuarbeiten.
                @endif
            </p>
        </div>
        @if(! empty($previewWarnings))
            <div class="ea-alert ea-alert-danger mb-4" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <div>
                    <strong>Die Vorlage kann so noch nicht gespeichert werden:</strong>
                    <ul class="list-disc ml-5 mt-1 mb-0">
                        @foreach($previewWarnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    @else
        <a href="{{ route('elternabfragen.index') }}" class="inline-flex items-center gap-2 mb-3 text-sm ea-link"><i class="fas fa-arrow-left" aria-hidden="true"></i> Alle Abfragen</a>
    @endif

    {{-- Kopf: Abfrage und eindeutige Zuordnung zum Kind --}}
    <header class="ea-hero mb-5">
        <div class="flex items-center gap-3 mb-4">
            <span class="ea-hero-icon !w-11 !h-11 !rounded-full text-base font-bold">{{ mb_strtoupper(mb_substr($assignment->child?->first_name ?? '?', 0, 1)) }}</span>
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wider font-semibold mb-0" style="color: rgba(255,255,255,.75)">Abfrage für</p>
                <p class="text-lg font-bold mb-0 break-words" style="color:#fff">{{ $assignment->childName() }}</p>
            </div>
        </div>
        <h1 class="ea-hero-title">{{ $inquiry->title }}</h1>
        <div class="flex flex-wrap items-center gap-2 mt-3">
            <x-elternabfragen.badge :color="$assignment->displayColor()" dot class="!ring-0">{{ $assignment->displayLabel() }}</x-elternabfragen.badge>
            <span class="ea-hero-meta" @if($overdue) style="background: rgba(239,68,68,.35)" @endif><i class="far fa-clock" aria-hidden="true"></i> Rückgabe bis {{ $state['deadline']->format('d.m.Y, H:i') }} Uhr</span>
        </div>
        @if($siblings->count() > 1)
            <nav class="mt-4 pt-4 flex flex-wrap items-center gap-2" style="border-top: 1px solid rgba(255,255,255,.2)" aria-label="Kind wechseln">
                <span class="text-xs mr-1" style="color: rgba(255,255,255,.75)">Kind wechseln:</span>
                @foreach($siblings as $sibling)
                    <a href="{{ route('elternabfragen.show', $sibling) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium transition hover:no-underline"
                       style="{{ $sibling->id === $assignment->id ? 'background:#fff;color:var(--color-primary-dark)' : 'background:rgba(255,255,255,.15);color:#fff' }}"
                       @if($sibling->id === $assignment->id) aria-current="page" @endif>
                        {{ $sibling->child?->first_name }}
                        @if($sibling->isAnswered()) <i class="fas fa-check-circle" aria-label="erledigt"></i> @endif
                    </a>
                @endforeach
            </nav>
        @endif
    </header>

    {{-- Ein klarer Zweck --}}
    <section class="ea-card mb-5" aria-label="Informationen zur Abfrage">
        <div class="ea-card-body space-y-4 text-sm ea-text">
            @if($inquiry->intro)
                <p class="whitespace-pre-line mb-0">{{ $inquiry->intro }}</p>
            @endif
            <div class="flex items-start gap-3">
                <div class="ea-card-icon"><i class="fas fa-bullseye" aria-hidden="true"></i></div>
                <div>
                    <p class="font-semibold mb-0.5">Wofür werden die Angaben benötigt?</p>
                    <p class="whitespace-pre-line ea-muted mb-0">{{ $version->purpose }}</p>
                </div>
            </div>
            @if($version->requires_all_guardians)
                <div class="ea-alert ea-alert-info">
                    <i class="fas fa-user-friends" aria-hidden="true"></i>
                    <p class="mb-0"><strong>Gemeinsame Erklärung:</strong> Eine sorgeberechtigte Person füllt die Abfrage aus, alle weiteren Sorgeberechtigten bestätigen die Angaben anschließend.</p>
                </div>
            @endif
        </div>
        <div class="ea-divide" style="border-top: 1px solid var(--color-card-border)">
            @if($version->parent_info)
                <details class="group px-5 py-3 text-sm">
                    <summary class="cursor-pointer font-semibold ea-text flex items-center justify-between list-none">
                        <span><i class="fas fa-info-circle ea-muted mr-2" aria-hidden="true"></i>Weitere Hinweise</span>
                        <i class="fas fa-chevron-down text-xs ea-muted transition group-open:rotate-180" aria-hidden="true"></i>
                    </summary>
                    <p class="whitespace-pre-line mt-2 mb-0 ea-muted">{{ $version->parent_info }}</p>
                </details>
            @endif
            <details class="group px-5 py-3 text-sm">
                <summary class="cursor-pointer font-semibold ea-text flex items-center justify-between list-none">
                    <span><i class="fas fa-shield-alt ea-muted mr-2" aria-hidden="true"></i>Datenschutz und Aufbewahrung</span>
                    <i class="fas fa-chevron-down text-xs ea-muted transition group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <div class="mt-2 space-y-1 ea-muted">
                    @if($version->responsible_office)<p class="mb-0">Zuständige Stelle: {{ $version->responsible_office }}</p>@endif
                    @if($version->retention_note)<p class="whitespace-pre-line mb-0">{{ $version->retention_note }}</p>@endif
                    @if($inquiry->retention_days)
                        <p class="mb-0">Die Angaben werden {{ $inquiry->retention_days }} Tage nach Abschluss der Abfrage gelöscht, soweit keine gesetzliche Aufbewahrungspflicht besteht.</p>
                    @endif
                </div>
            </details>
        </div>
    </section>

    @if(session('submitted') && $current)
        {{-- Empfangsbestätigung --}}
        <div class="ea-card mb-5 overflow-hidden" role="status" style="border-color: #34d399">
            <div class="flex items-start gap-4 p-5">
                <span class="flex items-center justify-center w-12 h-12 rounded-full flex-shrink-0 bg-emerald-500 text-white text-xl shadow"><i class="fas fa-check" aria-hidden="true"></i></span>
                <div>
                    <p class="font-semibold text-lg ea-text mb-0.5">Ihre Angaben sind eingegangen.</p>
                    <p class="text-sm ea-muted mb-0">
                        Eingang: {{ $current->submitted_at->format('d.m.Y, H:i:s') }} Uhr · Antwortversion {{ $current->version }}
                        · Prüfsumme <span class="font-mono">{{ substr($current->answer_hash, 0, 12) }}</span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($editing)
        <form method="POST" action="{{ $preview ? '#' : route('elternabfragen.submit', $assignment) }}" enctype="multipart/form-data"
              x-data="elternabfrageForm()" x-on:submit="if (!$event.submitter || !$event.submitter.hasAttribute('formnovalidate')) { if (step === 'form') { $event.preventDefault(); review(); } }"
              novalidate>
            @csrf

            {{-- Schrittanzeige --}}
            <ol class="ea-steps mb-6" aria-label="Fortschritt">
                <li class="ea-step" :class="step === 'form' ? 'ea-step-current' : 'ea-step-done'">
                    <span class="ea-step-dot"><span x-show="step === 'form'">1</span><i x-show="step !== 'form'" x-cloak class="fas fa-check" aria-hidden="true"></i></span>
                    <span class="ea-step-label">Ausfüllen</span>
                </li>
                <li class="ea-step" :class="step === 'summary' ? 'ea-step-current' : ''">
                    <span class="ea-step-dot">2</span>
                    <span class="ea-step-label">Prüfen &amp; bestätigen</span>
                </li>
                <li class="ea-step">
                    <span class="ea-step-dot">3</span>
                    <span class="ea-step-label">Eingereicht</span>
                </li>
            </ol>

            @if($isCorrection)
                <div class="ea-alert ea-alert-warning mb-4"><i class="fas fa-pen" aria-hidden="true"></i>
                    <p class="mb-0">
                        Sie korrigieren die eingereichte Antwort (Version {{ $current->version }}). Die bisherige Version bleibt nachvollziehbar gespeichert.
                        @if($version->requires_all_guardians) Nach der Korrektur müssen die weiteren Sorgeberechtigten die neuen Angaben erneut bestätigen. @endif
                        @if($state['correction_until']) Korrektur möglich bis {{ $state['correction_until']->format('d.m.Y, H:i') }} Uhr. @endif
                    </p>
                </div>
            @elseif($hasDraft)
                <div class="ea-alert ea-alert-info mb-4"><i class="fas fa-save" aria-hidden="true"></i>
                    <p class="mb-0">Ihr gespeicherter Entwurf wurde geladen. Ein Entwurf gilt noch nicht als Rückmeldung.</p>
                </div>
            @elseif($prefilledFrom ?? null)
                <div class="ea-alert ea-alert-info mb-4" role="status"><i class="fas fa-history" aria-hidden="true"></i>
                    <p class="mb-0">
                        <strong>Angaben aus „{{ $prefilledFrom->title }}“ übernommen.</strong>
                        Bitte prüfen Sie alles und ändern Sie, was nicht mehr stimmt. Einwilligungen und Dateien müssen neu angegeben werden.
                    </p>
                </div>
            @endif

            <div x-show="step === 'form'" class="space-y-4">
                <p class="text-sm ea-muted mb-0">Felder mit <span class="text-red-600">*</span> sind Pflichtfelder.</p>
                @foreach($fields as $field)
                    @include('elternabfragen.parent._field', [
                        'field' => $field,
                        'value' => old('answers.'.$field['key'], $answers[$field['key']] ?? null),
                        'existingFile' => $field['type'] === 'file' && is_array($existing[$field['key']] ?? null) ? $existing[$field['key']] : null,
                        'childOptions' => $childOptions ?? [],
                    ])
                @endforeach

                <div class="ea-card p-4 flex flex-col sm:flex-row gap-3 sm:sticky sm:bottom-4 z-10 shadow-lg">
                    <button type="submit" class="ea-btn ea-btn-primary ea-btn-lg w-full sm:w-auto">
                        Weiter zur Zusammenfassung <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                    @unless($preview)
                        <button type="submit" formaction="{{ route('elternabfragen.draft', $assignment) }}" formnovalidate
                                class="ea-btn ea-btn-secondary ea-btn-lg w-full sm:w-auto">
                            <i class="fas fa-save" aria-hidden="true"></i> Entwurf speichern
                        </button>
                    @endunless
                </div>
            </div>

            {{-- Zusammenfassung vor dem endgültigen Absenden --}}
            <div x-show="step === 'summary'" x-cloak>
                <div class="ea-card mb-4">
                    <div class="ea-card-header">
                        <div class="ea-card-icon"><i class="fas fa-clipboard-list" aria-hidden="true"></i></div>
                        <div>
                            <h2 class="ea-card-title" tabindex="-1" x-ref="summaryHeading">Bitte prüfen Sie Ihre Angaben</h2>
                            <p class="ea-card-sub">Für <strong>{{ $assignment->childName() }}</strong></p>
                        </div>
                    </div>
                    <dl class="ea-divide mb-0">
                        <template x-for="item in summary" :key="item.label">
                            <div class="px-5 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium ea-muted" x-text="item.label"></dt>
                                <dd class="text-sm ea-text sm:col-span-2 break-words whitespace-pre-line mb-0 mt-1 sm:mt-0" x-text="item.value"></dd>
                            </div>
                        </template>
                    </dl>
                </div>

                <div class="ea-card p-5 space-y-4">
                    @if($isCorrection)
                        <div>
                            <label for="correction_reason" class="ea-label">Grund der Korrektur (optional)</label>
                            <textarea id="correction_reason" name="correction_reason" rows="2" maxlength="1000" class="ea-input">{{ old('correction_reason') }}</textarea>
                        </div>
                    @endif

                    <label class="ea-choice">
                        <input type="checkbox" name="confirm" value="1" required>
                        <span class="ea-text">Ich bestätige, dass meine Angaben richtig und vollständig sind.</span>
                    </label>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" class="ea-btn ea-btn-success ea-btn-lg w-full sm:w-auto" @disabled($preview)>
                            <i class="fas fa-paper-plane" aria-hidden="true"></i> Verbindlich absenden
                        </button>
                        <button type="button" x-on:click="step = 'form'" class="ea-btn ea-btn-secondary ea-btn-lg w-full sm:w-auto">
                            <i class="fas fa-pen" aria-hidden="true"></i> Angaben ändern
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @if($hasDraft && ! $preview)
            <form method="POST" action="{{ route('elternabfragen.draft.discard', $assignment) }}" class="mt-3 text-center" onsubmit="return confirm('Entwurf verwerfen?')">
                @csrf @method('DELETE')
                <button class="ea-btn ea-btn-ghost ea-btn-sm"><i class="fas fa-trash-alt" aria-hidden="true"></i> Entwurf verwerfen</button>
            </form>
        @endif
    @elseif($current && $state['can_view_answers'])
        @if(! empty($confirmations))
            {{-- Bestätigung durch alle Sorgeberechtigten --}}
            <section class="ea-card mb-5" @if($state['can_confirm'] ?? false) style="border: 2px solid var(--color-primary)" @endif aria-labelledby="bestaetigung">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-user-check" aria-hidden="true"></i></div>
                    <h2 id="bestaetigung" class="ea-card-title">
                        @if($state['can_confirm'] ?? false)
                            Ihre Bestätigung wird benötigt
                        @else
                            Bestätigung durch alle Sorgeberechtigten
                        @endif
                    </h2>
                </div>
                <div class="ea-card-body space-y-4">
                    <ul class="space-y-2 text-sm mb-0">
                        @foreach($confirmations as $row)
                            <li class="flex items-center gap-3 p-3 rounded-xl ea-subtle">
                                @if($row['state'] === 'pending')
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/40 flex-shrink-0"><i class="far fa-clock" aria-hidden="true"></i></span>
                                    <span><span class="font-medium ea-text">{{ $row['name'] }}</span> <span class="ea-muted">– Bestätigung ausstehend</span></span>
                                @else
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 flex-shrink-0"><i class="fas fa-check" aria-hidden="true"></i></span>
                                    <span><span class="font-medium ea-text">{{ $row['name'] }}</span>
                                        <span class="ea-muted">– {{ $row['state'] === 'submitted' ? 'eingereicht' : 'bestätigt' }} am {{ $row['at']?->format('d.m.Y, H:i') }} Uhr</span></span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if($state['can_confirm'] ?? false)
                        <form method="POST" action="{{ route('elternabfragen.confirm', $assignment) }}" class="space-y-3">
                            @csrf
                            <p class="text-sm ea-muted mb-0">Bitte prüfen Sie die unten aufgeführten Angaben. Sind sie nicht richtig, können Sie sie stattdessen korrigieren – dann müssen die anderen Sorgeberechtigten erneut bestätigen.</p>
                            <label class="ea-choice">
                                <input type="checkbox" name="confirm" value="1" required>
                                <span class="ea-text">Ich habe die Angaben geprüft und bin mit ihnen einverstanden.</span>
                            </label>
                            <button class="ea-btn ea-btn-success ea-btn-lg w-full sm:w-auto">
                                <i class="fas fa-check" aria-hidden="true"></i> Angaben bestätigen
                            </button>
                        </form>
                    @elseif($state['awaiting_confirmation'] ?? false)
                        <p class="text-sm ea-muted mb-0">Die Abfrage ist erst abgeschlossen, wenn alle Sorgeberechtigten bestätigt haben. Sie wurden darüber benachrichtigt.</p>
                    @endif
                </div>
            </section>
        @endif

        <section class="ea-card mb-5" aria-labelledby="eingereicht">
            <div class="ea-card-header">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg flex-shrink-0 bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300"><i class="fas fa-check" aria-hidden="true"></i></div>
                <div>
                    <h2 id="eingereicht" class="ea-card-title">Eingereichte Angaben</h2>
                    <p class="ea-card-sub">Von {{ $current->user?->name ?? 'unbekannt' }} am {{ $current->submitted_at->format('d.m.Y, H:i') }} Uhr · Version {{ $current->version }}</p>
                </div>
            </div>
            <div class="px-5">
                @include('elternabfragen.parent._answers', [
                    'fields' => $current->templateVersion->fields,
                    'answers' => $current->answers,
                    'fileRoute' => fn ($key) => route('elternabfragen.file', [$assignment, $key]),
                ])
            </div>
            <div class="ea-card-footer">
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('elternabfragen.pdf', $assignment) }}" class="ea-btn ea-btn-secondary w-full sm:w-auto">
                        <i class="fas fa-file-pdf text-red-500" aria-hidden="true"></i> PDF-Bestätigung
                    </a>
                    @if($state['can_correct'])
                        <a href="{{ route('elternabfragen.show', [$assignment, 'korrigieren' => 1]) }}" class="ea-btn ea-btn-primary w-full sm:w-auto">
                            <i class="fas fa-pen" aria-hidden="true"></i> Angaben korrigieren
                        </a>
                    @endif
                </div>
                @if($state['can_correct'] && $state['correction_until'])
                    <p class="ea-hint">Korrektur selbst möglich bis {{ $state['correction_until']->format('d.m.Y, H:i') }} Uhr. Danach bitte an das Sekretariat wenden.</p>
                @elseif($state['reason'])
                    <p class="ea-hint">{{ $state['reason'] }}</p>
                @endif
            </div>
        </section>

        @if($assignment->responses->count() > 1)
            <details class="ea-card group">
                <summary class="cursor-pointer px-5 py-4 font-semibold ea-text flex items-center justify-between list-none">
                    <span><i class="fas fa-code-branch ea-muted mr-2" aria-hidden="true"></i>Versionsverlauf ({{ $assignment->responses->count() }})</span>
                    <i class="fas fa-chevron-down text-xs ea-muted transition group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <ol class="ea-timeline px-5 pb-5 ml-5 mb-0">
                    @foreach($assignment->responses as $response)
                        <li class="ea-timeline-item">
                            <p class="mb-0 ea-text">
                                Version {{ $response->version }} · {{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }}
                                @if($response->is_current) <x-elternabfragen.badge color="green">gültig</x-elternabfragen.badge> @endif
                            </p>
                            @if($response->correction_reason) <p class="text-xs ea-muted mb-0">Grund: {{ $response->correction_reason }}</p> @endif
                        </li>
                    @endforeach
                </ol>
            </details>
        @endif
    @else
        <div class="ea-card">
            <div class="ea-empty !py-8">
                @if($current)
                    <div class="ea-empty-icon !bg-emerald-100 !text-emerald-600 dark:!bg-emerald-900/40"><i class="fas fa-check" aria-hidden="true"></i></div>
                    <p class="ea-text mb-1">Die Abfrage wurde am {{ $current->submitted_at->format('d.m.Y') }} beantwortet.</p>
                @else
                    <div class="ea-empty-icon"><i class="fas fa-info" aria-hidden="true"></i></div>
                @endif
                @if($state['reason'])
                    <p class="text-sm mb-0">{{ $state['reason'] }}</p>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('js')
<script>
    function elternabfrageForm() {
        return {
            step: 'form',
            summary: [],
            review() {
                const form = this.$root;
                // Pflichtfelder und Formate sofort im Browser prüfen (Server prüft erneut)
                const invalid = Array.from(form.querySelectorAll('[data-field] input, [data-field] select, [data-field] textarea'))
                    .find(el => !el.checkValidity());
                if (invalid) {
                    if (invalid.dataset.error) { invalid.setCustomValidity(invalid.dataset.error); invalid.addEventListener('input', () => invalid.setCustomValidity(''), { once: true }); }
                    invalid.reportValidity();
                    return;
                }
                this.summary = Array.from(form.querySelectorAll('[data-field]'))
                    .filter(el => el.dataset.type !== 'info')
                    .map(el => ({ label: el.dataset.label, value: this.displayValue(el) }));
                this.step = 'summary';
                this.$nextTick(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); this.$refs.summaryHeading.focus(); });
            },
            displayValue(el) {
                const type = el.dataset.type;
                if (['yes_no', 'consent', 'radio', 'checkbox', 'child'].includes(type)) {
                    const checked = Array.from(el.querySelectorAll('input:checked')).map(i => i.dataset.choice);
                    return checked.length ? checked.join(', ') : '–';
                }
                const input = el.querySelector('input, select, textarea');
                if (!input) return '–';
                if (type === 'file') {
                    if (input.files && input.files.length) return input.files[0].name;
                    const existing = el.querySelector('[data-existing]');
                    return existing ? 'bisherige Datei' : '–';
                }
                if (type === 'date' && input.value) {
                    const [y, m, d] = input.value.split('-');
                    return `${d}.${m}.${y}`;
                }
                return input.value.trim() || '–';
            },
        };
    }
</script>
@endpush
