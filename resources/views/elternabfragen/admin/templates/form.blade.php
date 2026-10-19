@extends('layouts.app')

@section('title', '| Elternabfragen · Vorlage')

@section('content')
@php
    $isNew = ! $version->exists;
    $draftKey = 'elternabfragen.vorlage.'.($isNew ? 'neu' : $version->id);
    $fieldsJson = old('fields_json', json_encode($version->fields ?? [], JSON_UNESCAPED_UNICODE));
    $label = 'block text-sm font-medium text-gray-700 mb-1';
    $input = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500';
@endphp
<div class="container-fluid px-4 py-6 max-w-5xl">
    @include('elternabfragen.admin._nav', [
        'title' => $isNew ? 'Neue Vorlage' : 'Vorlage bearbeiten – Version '.$version->version,
        'subtitle' => 'Eine Abfrage, ein klarer Zweck: nur die für den Zweck notwendigen Angaben erheben.',
    ])

    @if($version->status?->value === 'rejected' && $version->review_note)
        <div class="mb-4 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-900 text-sm">
            <strong>Zur Überarbeitung zurückgegeben:</strong> {{ $version->review_note }}
        </div>
    @endif

    <form method="POST" action="{{ $isNew ? route('elternabfragen.admin.templates.store') : route('elternabfragen.admin.templates.update', $version) }}"
          x-data="templateBuilder(@js(json_decode($fieldsJson, true) ?: []), {
              draftKey: @js($draftKey),
              serverUpdatedAt: @js($version->updated_at ? $version->updated_at->getTimestampMs() : 0),
              hasOldInput: @js(old('fields_json') !== null),
              errors: @js($errors->getMessages()),
          })"
          x-on:submit="sync()" x-on:input.debounce.500ms="saveDraft()" x-on:change="saveDraft()">
        @csrf
        @unless($isNew) @method('PUT') @endunless
        <input type="hidden" name="fields_json" x-ref="json" value="{{ $fieldsJson }}">
        <input type="hidden" name="draft_key" value="{{ $draftKey }}">

        <div x-show="restoredAt" x-cloak class="mb-4 p-4 rounded-lg bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-sm flex flex-wrap items-center justify-between gap-2" role="status">
            <span><i class="fas fa-history" aria-hidden="true"></i> Ungespeicherter Bearbeitungsstand vom <strong x-text="restoredAt"></strong> wurde wiederhergestellt.</span>
            <button type="button" x-on:click="discardDraft()" class="px-3 py-1.5 rounded-lg border border-amber-400 bg-white text-amber-900">Verwerfen und gespeicherten Stand laden</button>
        </div>

        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6 grid gap-4 md:grid-cols-2">
            <h2 class="md:col-span-2 font-semibold text-gray-800">Grunddaten</h2>
            <div>
                <label for="title" class="{{ $label }}">Titel *</label>
                <input id="title" name="title" required maxlength="255" value="{{ old('title', $version->title) }}" class="{{ $input }}">
            </div>
            <div>
                <label for="category" class="{{ $label }}">Kategorie</label>
                <input id="category" name="category" list="categories" maxlength="100" value="{{ old('category', $version->template?->category) }}" class="{{ $input }}" placeholder="z. B. Stammdaten, Datenschutz, Erlaubnisse">
                <datalist id="categories">@foreach($categories as $category)<option value="{{ $category }}">@endforeach</datalist>
            </div>
            <div class="md:col-span-2">
                <label for="purpose" class="{{ $label }}">Zweck *</label>
                <textarea id="purpose" name="purpose" rows="3" required class="{{ $input }}" placeholder="Wofür werden die Angaben benötigt und wie werden sie verwendet?">{{ old('purpose', $version->purpose) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label for="parent_info" class="{{ $label }}">Elterninformation</label>
                <textarea id="parent_info" name="parent_info" rows="3" class="{{ $input }}" placeholder="Hinweise, die Eltern vor dem Ausfüllen kennen sollten">{{ old('parent_info', $version->parent_info) }}</textarea>
            </div>
            <div>
                <label for="responsible_office" class="{{ $label }}">Zuständige Stelle</label>
                <input id="responsible_office" name="responsible_office" maxlength="255" value="{{ old('responsible_office', $version->responsible_office) }}" class="{{ $input }}" placeholder="z. B. Sekretariat">
            </div>
            <div>
                <label for="target_hint" class="{{ $label }}">Zielgruppe (Hinweis)</label>
                <input id="target_hint" name="target_hint" maxlength="255" value="{{ old('target_hint', $version->target_hint) }}" class="{{ $input }}" placeholder="z. B. alle neuen Klassen 5">
            </div>
            <div>
                <label for="default_deadline_days" class="{{ $label }}">Standardfrist (Tage) *</label>
                <input type="number" id="default_deadline_days" name="default_deadline_days" min="1" max="365" required value="{{ old('default_deadline_days', $version->default_deadline_days ?? 14) }}" class="{{ $input }}">
            </div>
            <div>
                <label for="retention_days" class="{{ $label }}">Aufbewahrungsfrist nach Abschluss (Tage)</label>
                <input type="number" id="retention_days" name="retention_days" min="1" max="3650" value="{{ old('retention_days', $version->retention_days) }}" class="{{ $input }}" placeholder="leer = Standard aus den Regeln">
            </div>
            <div class="md:col-span-2">
                <label for="retention_note" class="{{ $label }}">Hinweise zu Aufbewahrung und Löschung</label>
                <textarea id="retention_note" name="retention_note" rows="2" class="{{ $input }}">{{ old('retention_note', $version->retention_note) }}</textarea>
            </div>
            <label class="md:col-span-2 flex items-start gap-3">
                <input type="checkbox" name="is_sensitive" value="1" class="mt-1 h-4 w-4" @checked(old('is_sensitive', $version->is_sensitive))>
                <span class="text-sm text-gray-700"><strong>Sensible Inhalte</strong> (z. B. Gesundheitsdaten, Einwilligungen in Foto-/Videoaufnahmen) – vor der Freigabe ist eine datenschutzrechtliche Zusatzprüfung zu bestätigen.</span>
            </label>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <h2 class="font-semibold text-gray-800">Formularfelder</h2>
                <div class="flex flex-wrap gap-2">
                    <select x-model="newType" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" aria-label="Feldtyp">
                        @foreach($fieldTypes as $value => $typeLabel)
                            <option value="{{ $value }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                    <button type="button" x-on:click="add()" class="px-3 py-2 rounded-lg bg-gray-800 text-white text-sm"><i class="fas fa-plus" aria-hidden="true"></i> Feld hinzufügen</button>
                    <input type="file" x-ref="importFile" class="hidden" accept=".xlsx,.xls,.ods,.csv" x-on:change="importList($event)" aria-label="Excel-Liste auswählen">
                    <button type="button" x-on:click="$refs.importFile.click()" :disabled="importing" class="px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 text-sm">
                        <i class="fas" :class="importing ? 'fa-spinner fa-spin' : 'fa-file-excel'" aria-hidden="true"></i> Aus Excel-Liste übernehmen
                    </button>
                </div>
            </div>

            <p class="text-xs text-gray-500 mb-3">
                Excel-Liste: Jede Spaltenüberschrift der ersten Zeile wird zu einem Feld. Stehen darunter schon Werte (z. B. eine bisher ausgefüllte Liste),
                wird daraus der Feldtyp abgeleitet (Ja/Nein, Datum, Auswahl). Spalten wie Name, Vorname oder Klasse entfallen – die Zuordnung zum Kind erfolgt automatisch.
            </p>
            @php($fieldErrors = collect($errors->getMessages())->filter(fn ($messages, $key) => $key === 'fields' || str_starts_with($key, 'fields.'))->flatten())
            @if($fieldErrors->isNotEmpty())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-900 text-sm" role="alert">
                    <strong>Bitte die markierten Felder korrigieren:</strong>
                    <ul class="list-disc ml-5 mt-1">
                        @foreach($fieldErrors as $message)<li>{{ $message }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <div x-show="importMessage" x-cloak class="mb-4 p-3 rounded-lg text-sm" :class="importError ? 'bg-red-50 text-red-900' : 'bg-green-50 text-green-900'" role="status" x-text="importMessage"></div>

            <template x-if="fields.length === 0">
                <p class="text-sm text-gray-500 text-center py-6">Noch keine Felder. Wählen Sie einen Feldtyp und fügen Sie das erste Feld hinzu.</p>
            </template>

            <div class="space-y-4">
                <template x-for="(field, index) in fields" :key="field._id">
                    <div class="border rounded-lg p-4 bg-gray-50" :class="fieldError(index) ? 'border-red-400 ring-1 ring-red-300' : 'border-gray-200'" :id="'field_' + field._id">
                        <p x-show="fieldError(index)" x-cloak class="mb-3 text-sm text-red-700" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> <span x-text="fieldError(index)"></span></p>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-600" x-text="(index + 1) + '. ' + types[field.type]"></span>
                            <div class="flex gap-1">
                                <button type="button" class="px-2 py-1 text-sm rounded bg-white border" x-on:click="move(index, -1)" :disabled="index === 0" aria-label="Nach oben"><i class="fas fa-arrow-up"></i></button>
                                <button type="button" class="px-2 py-1 text-sm rounded bg-white border" x-on:click="move(index, 1)" :disabled="index === fields.length - 1" aria-label="Nach unten"><i class="fas fa-arrow-down"></i></button>
                                <button type="button" class="px-2 py-1 text-sm rounded bg-white border text-red-700" x-on:click="remove(index)" aria-label="Feld entfernen"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="{{ $label }}" :for="'label_' + field._id" x-text="field.type === 'info' ? 'Überschrift *' : 'Frage / Beschriftung *'"></label>
                                <input class="{{ $input }}" :id="'label_' + field._id" x-model="field.label" required maxlength="500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="{{ $label }}" :for="'help_' + field._id">Kurzer Hinweis</label>
                                <input class="{{ $input }}" :id="'help_' + field._id" x-model="field.help" maxlength="1000">
                            </div>
                            <template x-if="field.type === 'consent'">
                                <div class="md:col-span-2">
                                    <label class="{{ $label }}" :for="'consent_' + field._id">Vollständiger Einwilligungstext *</label>
                                    <textarea class="{{ $input }}" rows="5" :id="'consent_' + field._id" x-model="field.consent_text"></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Bleibt für Eltern vollständig sichtbar und wird mit dieser Vorlagenversion gespeichert.</p>
                                </div>
                            </template>
                            <template x-if="['checkbox', 'radio', 'select'].includes(field.type)">
                                <div class="md:col-span-2">
                                    <label class="{{ $label }}" :for="'options_' + field._id">Antwortoptionen (eine pro Zeile) *</label>
                                    <textarea class="{{ $input }}" rows="3" :id="'options_' + field._id" x-model="field.optionsText"></textarea>
                                </div>
                            </template>
                            <template x-if="['text', 'textarea', 'checkbox'].includes(field.type)">
                                <div class="grid grid-cols-2 gap-3 md:col-span-2">
                                    <div>
                                        <label class="{{ $label }}" :for="'min_' + field._id" x-text="field.type === 'checkbox' ? 'Mindestens auswählen' : 'Mindestlänge'"></label>
                                        <input type="number" min="1" class="{{ $input }}" :id="'min_' + field._id" x-model="field.min_length">
                                    </div>
                                    <div>
                                        <label class="{{ $label }}" :for="'max_' + field._id" x-text="field.type === 'checkbox' ? 'Höchstens auswählen' : 'Höchstlänge'"></label>
                                        <input type="number" min="1" class="{{ $input }}" :id="'max_' + field._id" x-model="field.max_length">
                                    </div>
                                </div>
                            </template>
                            <template x-if="field.type === 'text'">
                                <div>
                                    <label class="{{ $label }}" :for="'format_' + field._id">Formatprüfung</label>
                                    <select class="{{ $input }}" :id="'format_' + field._id" x-model="field.format">
                                        <option value="">keine</option>
                                        @foreach($formats as $value => $formatLabel)
                                            <option value="{{ $value }}">{{ $formatLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>
                            <template x-if="field.type === 'child'">
                                <p class="md:col-span-2 text-xs text-gray-500">Eltern wählen eines der Kinder aus, für die sie sorgeberechtigt sind. Gespeichert wird der Name des Kindes.</p>
                            </template>
                            <template x-if="field.type !== 'info'">
                                <div>
                                    <label class="{{ $label }}" :for="'error_' + field._id">Eigene Fehlermeldung</label>
                                    <input class="{{ $input }}" :id="'error_' + field._id" x-model="field.error_message" maxlength="300" placeholder="Verständlicher Hinweis bei fehlerhafter Eingabe">
                                </div>
                            </template>
                            <template x-if="field.type !== 'info'">
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" class="h-4 w-4" x-model="field.required"> Pflichtfeld
                                    <span x-show="field.type === 'consent' && field.required" class="text-xs text-gray-500">(Entscheidung erforderlich – Einwilligung bleibt freiwillig)</span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="fields.length > 0" x-cloak class="flex flex-wrap items-center justify-end gap-2 mt-4 pt-4 border-t border-gray-200">
                <select x-model="newType" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" aria-label="Feldtyp für neues Feld am Ende">
                    @foreach($fieldTypes as $value => $typeLabel)
                        <option value="{{ $value }}">{{ $typeLabel }}</option>
                    @endforeach
                </select>
                <button type="button" x-on:click="add()" class="px-3 py-2 rounded-lg bg-gray-800 text-white text-sm"><i class="fas fa-plus" aria-hidden="true"></i> Feld hinzufügen</button>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">Entwurf speichern</button>
            <button type="submit" name="submit_review" value="1" class="px-5 py-2.5 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700">Speichern und zur Prüfung einreichen</button>
            <button type="submit" formaction="{{ route('elternabfragen.admin.templates.preview-draft') }}" formtarget="_blank" formnovalidate class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700"><i class="fas fa-eye" aria-hidden="true"></i> Vorschau (ohne Speichern)</button>
            <a href="{{ $isNew ? route('elternabfragen.admin.templates.index') : route('elternabfragen.admin.templates.show', $version) }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700">Abbrechen</a>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
    function templateBuilder(initial, options) {
        let seq = 0;
        const prepare = (f) => Object.assign({
            key: '', type: 'text', label: '', help: '', required: false, options: [], min_length: '', max_length: '',
            format: '', error_message: '', consent_text: '',
        }, f, { _id: ++seq, optionsText: (f.options || []).join('\n'), min_length: f.min_length ?? '', max_length: f.max_length ?? '' });

        return {
            types: @js($fieldTypes),
            newType: 'yes_no',
            importing: false,
            importMessage: '',
            importError: false,
            fields: (initial || []).map(prepare),
            errors: options.errors || {},
            restoredAt: '',
            init() {
                this.restoreDraft();
                // Verstecktes Feld stets aktuell halten – auch falls das Formular ohne submit-Event abgeschickt wird
                this.$refs.json.value = JSON.stringify(this.serialize());
                this.$watch('fields', () => this.sync());
                const first = this.fields.find((f, i) => this.fieldError(i));
                if (first) this.$nextTick(() => document.getElementById('field_' + first._id)?.scrollIntoView({ block: 'center' }));
            },
            fieldError(i) { return (this.errors['fields.' + i] || [])[0] || ''; },
            add() {
                const field = prepare({ type: this.newType, required: this.newType !== 'info' });
                this.fields.push(field);
                this.$nextTick(() => {
                    document.getElementById('field_' + field._id)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    document.getElementById('label_' + field._id)?.focus({ preventScroll: true });
                });
            },
            remove(i) { if (confirm('Feld entfernen?')) { this.fields.splice(i, 1); this.errors = {}; } },
            async importList(event) {
                const file = event.target.files[0];
                event.target.value = '';
                if (!file) return;
                const body = new FormData();
                body.append('file', file);
                body.append('_token', @js(csrf_token()));
                this.importing = true;
                this.importMessage = '';
                try {
                    const res = await fetch(@js(route('elternabfragen.admin.templates.import')), { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (!res.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Die Datei konnte nicht gelesen werden.');
                    data.fields.forEach(f => this.fields.push(prepare(f)));
                    this.importError = false;
                    this.importMessage = data.fields.length + ' Feld(er) übernommen. Bitte Feldtypen, Pflichtangaben und Optionen prüfen.'
                        + (data.skipped.length ? ' Nicht übernommen (Zuordnung zum Kind erfolgt automatisch): ' + data.skipped.join(', ') + '.' : '');
                } catch (e) {
                    this.importError = true;
                    this.importMessage = e.message;
                } finally {
                    this.importing = false;
                }
            },
            move(i, d) { const f = this.fields.splice(i, 1)[0]; this.fields.splice(i + d, 0, f); this.errors = {}; },
            serialize() {
                return this.fields.map(f => ({
                    key: f.key, type: f.type, label: f.label, help: f.help, required: !!f.required,
                    options: f.optionsText.split('\n').map(o => o.trim()).filter(Boolean),
                    min_length: f.min_length || null, max_length: f.max_length || null,
                    format: f.format || null, error_message: f.error_message, consent_text: f.consent_text,
                }));
            },
            sync() {
                this.$refs.json.value = JSON.stringify(this.serialize());
                this.saveDraft();
            },
            // Bearbeitungsstand lokal im Browser sichern, damit bei Fehlern (z. B. abgelaufene Sitzung) nichts verloren geht.
            // Nach erfolgreichem Speichern wird er auf der Vorlagenseite verworfen.
            saveDraft() {
                const inputs = {};
                this.$root.querySelectorAll('input[name], textarea[name], select[name]').forEach(el => {
                    if (['_token', '_method', 'fields_json', 'draft_key', 'submit_review'].includes(el.name)) return;
                    inputs[el.name] = el.type === 'checkbox' ? el.checked : el.value;
                });
                try {
                    localStorage.setItem(options.draftKey, JSON.stringify({ savedAt: Date.now(), inputs, fields: this.serialize() }));
                } catch (e) {}
            },
            restoreDraft() {
                let draft = null;
                try { draft = JSON.parse(localStorage.getItem(options.draftKey) || 'null'); } catch (e) {}
                // Abgesendete Eingaben (nach Validierungsfehler) haben Vorrang; Stände älter als der gespeicherte werden ignoriert.
                if (!draft || options.hasOldInput || draft.savedAt <= options.serverUpdatedAt) return;
                if (JSON.stringify(draft.fields) === JSON.stringify(this.serialize())) return;
                this.fields = (draft.fields || []).map(prepare);
                Object.entries(draft.inputs || {}).forEach(([name, value]) => {
                    const el = this.$root.querySelector('[name="' + CSS.escape(name) + '"]');
                    if (!el) return;
                    if (el.type === 'checkbox') el.checked = !!value; else el.value = value;
                });
                this.restoredAt = new Date(draft.savedAt).toLocaleString('de-DE', { dateStyle: 'short', timeStyle: 'short' });
            },
            discardDraft() {
                try { localStorage.removeItem(options.draftKey); } catch (e) {}
                window.location.reload();
            },
        };
    }
</script>
@endpush
