@extends('layouts.app')

@section('title', '| '.$inquiry->title)

@section('content')
@php
    $current = $assignment->currentResponse;
    $isCorrection = $assignment->isAnswered() && $state['can_correct'] && $editing;
    $existing = $current?->answers ?? [];
@endphp
<div class="max-w-2xl mx-auto px-4 py-6">
    @if($preview)
        <div class="mb-4 p-3 rounded-lg bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-sm" role="status">
            <i class="fas fa-eye" aria-hidden="true"></i> <strong>Vorschau aus Sicht der Eltern.</strong> Eingaben werden nicht gespeichert.
            @php($backUrl = $previewBack ?? ($inquiry->exists ? route('elternabfragen.admin.inquiries.show', $inquiry) : null))
            @if($backUrl)
                <a href="{{ $backUrl }}" class="underline ml-1">{{ $previewBackLabel ?? 'Zurück zur Abfrage' }}</a>
            @else
                Ungespeicherter Stand des Editors – schließen Sie diesen Tab, um weiterzuarbeiten.
            @endif
        </div>
        @if(! empty($previewWarnings))
            <div class="mb-4 p-3 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-900 text-sm" role="alert">
                <strong>Die Vorlage kann so noch nicht gespeichert werden:</strong>
                <ul class="list-disc ml-5 mt-1">
                    @foreach($previewWarnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @else
        <a href="{{ route('elternabfragen.index') }}" class="text-sm text-blue-700 hover:underline"><i class="fas fa-arrow-left" aria-hidden="true"></i> Alle Abfragen</a>
    @endif

    {{-- Eindeutige Zuordnung zum Kind --}}
    <div class="mt-3 mb-4 rounded-xl bg-blue-50 border border-blue-200 p-4">
        <p class="text-xs uppercase tracking-wide text-blue-800 font-semibold">Abfrage für</p>
        <p class="text-xl font-bold text-blue-900"><i class="fas fa-child" aria-hidden="true"></i> {{ $assignment->childName() }}</p>
        @if($siblings->count() > 1)
            <nav class="mt-2 flex flex-wrap gap-2" aria-label="Kind wechseln">
                @foreach($siblings as $sibling)
                    <a href="{{ route('elternabfragen.show', $sibling) }}"
                       class="px-3 py-1 rounded-full text-sm border {{ $sibling->id === $assignment->id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-800 border-blue-300 hover:bg-blue-100' }}"
                       @if($sibling->id === $assignment->id) aria-current="page" @endif>
                        {{ $sibling->child?->first_name }}
                        @if($sibling->isAnswered()) <i class="fas fa-check" aria-label="erledigt"></i> @endif
                    </a>
                @endforeach
            </nav>
        @endif
    </div>

    <header class="mb-4">
        <h1 class="text-2xl font-bold text-gray-800 break-words">{{ $inquiry->title }}</h1>
        <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-gray-600">
            <x-elternabfragen.badge :color="$assignment->status->color()">{{ $assignment->status->label() }}</x-elternabfragen.badge>
            <span><i class="far fa-clock" aria-hidden="true"></i> Rückgabe bis {{ $state['deadline']->format('d.m.Y, H:i') }} Uhr</span>
        </div>
    </header>

    {{-- Ein klarer Zweck --}}
    <section class="bg-white rounded-xl border border-gray-200 p-4 mb-4 text-sm text-gray-700 space-y-3" aria-label="Informationen zur Abfrage">
        @if($inquiry->intro)
            <p class="whitespace-pre-line">{{ $inquiry->intro }}</p>
        @endif
        <div>
            <p class="font-semibold text-gray-800">Wofür werden die Angaben benötigt?</p>
            <p class="whitespace-pre-line">{{ $version->purpose }}</p>
        </div>
        @if($version->parent_info)
            <details>
                <summary class="cursor-pointer font-semibold text-gray-800">Weitere Hinweise</summary>
                <p class="whitespace-pre-line mt-2">{{ $version->parent_info }}</p>
            </details>
        @endif
        <details>
            <summary class="cursor-pointer font-semibold text-gray-800">Datenschutz und Aufbewahrung</summary>
            <div class="mt-2 space-y-1">
                @if($version->responsible_office)<p>Zuständige Stelle: {{ $version->responsible_office }}</p>@endif
                @if($version->retention_note)<p class="whitespace-pre-line">{{ $version->retention_note }}</p>@endif
                @if($inquiry->retention_days)
                    <p>Die Angaben werden {{ $inquiry->retention_days }} Tage nach Abschluss der Abfrage gelöscht, soweit keine gesetzliche Aufbewahrungspflicht besteht.</p>
                @endif
            </div>
        </details>
    </section>

    @if(session('submitted') && $current)
        {{-- Empfangsbestätigung --}}
        <div class="mb-4 rounded-xl bg-green-50 border border-green-300 p-4" role="status">
            <p class="font-semibold text-green-900"><i class="fas fa-check-circle" aria-hidden="true"></i> Ihre Angaben sind eingegangen.</p>
            <p class="text-sm text-green-900 mt-1">
                Eingang: {{ $current->submitted_at->format('d.m.Y, H:i:s') }} Uhr · Antwortversion {{ $current->version }}
                · Prüfsumme <span class="font-mono">{{ substr($current->answer_hash, 0, 12) }}</span>
            </p>
        </div>
    @endif

    @if($editing)
        <form method="POST" action="{{ $preview ? '#' : route('elternabfragen.submit', $assignment) }}" enctype="multipart/form-data"
              x-data="elternabfrageForm()" x-on:submit="if (!$event.submitter || !$event.submitter.hasAttribute('formnovalidate')) { if (step === 'form') { $event.preventDefault(); review(); } }"
              novalidate>
            @csrf

            @if($isCorrection)
                <div class="mb-4 p-3 rounded-lg bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-sm">
                    Sie korrigieren die eingereichte Antwort (Version {{ $current->version }}). Die bisherige Version bleibt nachvollziehbar gespeichert.
                    @if($state['correction_until']) Korrektur möglich bis {{ $state['correction_until']->format('d.m.Y, H:i') }} Uhr. @endif
                </div>
            @elseif($hasDraft)
                <div class="mb-4 p-3 rounded-lg bg-blue-50 text-blue-900 text-sm">
                    <i class="fas fa-save" aria-hidden="true"></i> Ihr gespeicherter Entwurf wurde geladen. Ein Entwurf gilt noch nicht als Rückmeldung.
                </div>
            @endif

            <div x-show="step === 'form'" class="space-y-4">
                <p class="text-sm text-gray-600">Felder mit <span class="text-red-600">*</span> sind Pflichtfelder.</p>
                @foreach($fields as $field)
                    @include('elternabfragen.parent._field', [
                        'field' => $field,
                        'value' => old('answers.'.$field['key'], $answers[$field['key']] ?? null),
                        'existingFile' => $field['type'] === 'file' && is_array($existing[$field['key']] ?? null) ? $existing[$field['key']] : null,
                        'childOptions' => $childOptions ?? [],
                    ])
                @endforeach

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="submit" class="w-full sm:w-auto px-5 py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        Weiter zur Zusammenfassung <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                    @unless($preview)
                        <button type="submit" formaction="{{ route('elternabfragen.draft', $assignment) }}" formnovalidate
                                class="w-full sm:w-auto px-5 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-50">
                            <i class="fas fa-save" aria-hidden="true"></i> Entwurf speichern
                        </button>
                    @endunless
                </div>
            </div>

            {{-- Zusammenfassung vor dem endgültigen Absenden --}}
            <div x-show="step === 'summary'" x-cloak>
                <h2 class="text-lg font-semibold text-gray-800 mb-2" tabindex="-1" x-ref="summaryHeading">Bitte prüfen Sie Ihre Angaben</h2>
                <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                    <p class="text-sm text-gray-600 mb-2">Für <strong>{{ $assignment->childName() }}</strong></p>
                    <dl class="divide-y divide-gray-100">
                        <template x-for="item in summary" :key="item.label">
                            <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-600" x-text="item.label"></dt>
                                <dd class="text-sm text-gray-900 sm:col-span-2 break-words whitespace-pre-line" x-text="item.value"></dd>
                            </div>
                        </template>
                    </dl>
                </div>

                @if($isCorrection)
                    <label for="correction_reason" class="block font-medium text-gray-800 mb-1">Grund der Korrektur (optional)</label>
                    <textarea id="correction_reason" name="correction_reason" rows="2" maxlength="1000" class="block w-full rounded-lg border border-gray-300 px-3 py-2 mb-4">{{ old('correction_reason') }}</textarea>
                @endif

                <label class="flex items-start gap-3 mb-4">
                    <input type="checkbox" name="confirm" value="1" class="mt-1 h-5 w-5" required>
                    <span class="text-gray-800">Ich bestätige, dass meine Angaben richtig und vollständig sind.</span>
                </label>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" class="w-full sm:w-auto px-5 py-3 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700" @disabled($preview)>
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> Verbindlich absenden
                    </button>
                    <button type="button" x-on:click="step = 'form'" class="w-full sm:w-auto px-5 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-50">
                        <i class="fas fa-pen" aria-hidden="true"></i> Angaben ändern
                    </button>
                </div>
            </div>
        </form>

        @if($hasDraft && ! $preview)
            <form method="POST" action="{{ route('elternabfragen.draft.discard', $assignment) }}" class="mt-3" onsubmit="return confirm('Entwurf verwerfen?')">
                @csrf @method('DELETE')
                <button class="text-sm text-gray-600 underline">Entwurf verwerfen</button>
            </form>
        @endif
    @elseif($current && $state['can_view_answers'])
        <section class="bg-white rounded-xl border border-gray-200 p-4 mb-4" aria-labelledby="eingereicht">
            <h2 id="eingereicht" class="text-lg font-semibold text-gray-800">Eingereichte Angaben</h2>
            <p class="text-sm text-gray-600 mb-2">
                Von {{ $current->user?->name ?? 'unbekannt' }} am {{ $current->submitted_at->format('d.m.Y, H:i') }} Uhr · Version {{ $current->version }}
            </p>
            @include('elternabfragen.parent._answers', [
                'fields' => $current->templateVersion->fields,
                'answers' => $current->answers,
                'fileRoute' => fn ($key) => route('elternabfragen.file', [$assignment, $key]),
            ])
            <div class="flex flex-col sm:flex-row gap-3 mt-4">
                <a href="{{ route('elternabfragen.pdf', $assignment) }}" class="w-full sm:w-auto text-center px-5 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-50">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF-Bestätigung
                </a>
                @if($state['can_correct'])
                    <a href="{{ route('elternabfragen.show', [$assignment, 'korrigieren' => 1]) }}" class="w-full sm:w-auto text-center px-5 py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        <i class="fas fa-pen" aria-hidden="true"></i> Angaben korrigieren
                    </a>
                @endif
            </div>
            @if($state['can_correct'] && $state['correction_until'])
                <p class="text-xs text-gray-500 mt-2">Korrektur selbst möglich bis {{ $state['correction_until']->format('d.m.Y, H:i') }} Uhr. Danach bitte an das Sekretariat wenden.</p>
            @elseif($state['reason'])
                <p class="text-xs text-gray-500 mt-2">{{ $state['reason'] }}</p>
            @endif
        </section>

        @if($assignment->responses->count() > 1)
            <details class="bg-white rounded-xl border border-gray-200 p-4">
                <summary class="cursor-pointer font-semibold text-gray-800">Versionsverlauf ({{ $assignment->responses->count() }})</summary>
                <ul class="mt-2 text-sm text-gray-700 space-y-1">
                    @foreach($assignment->responses as $response)
                        <li>
                            Version {{ $response->version }} · {{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }}
                            @if($response->is_current) <x-elternabfragen.badge color="green">gültig</x-elternabfragen.badge> @endif
                            @if($response->correction_reason) <span class="block text-gray-500">Grund: {{ $response->correction_reason }}</span> @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    @else
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-gray-700">
            @if($current)
                <p><i class="fas fa-check-circle text-green-600" aria-hidden="true"></i> Die Abfrage wurde am {{ $current->submitted_at->format('d.m.Y') }} beantwortet.</p>
            @endif
            @if($state['reason'])
                <p>{{ $state['reason'] }}</p>
            @endif
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
                    .map(el => ({ label: el.dataset.label, value: this.valueOf(el) }));
                this.step = 'summary';
                this.$nextTick(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); this.$refs.summaryHeading.focus(); });
            },
            valueOf(el) {
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
