@extends('layouts.app')

@section('title', '| Elternabfragen · Abfrage')

@section('content')
@php
    $isNew = ! $inquiry->exists;
    $selectedGroups = array_map('intval', old('target_group_ids', $inquiry->target_group_ids ?? []));
    $check = fn (string $name) => (bool) old($name, $inquiry->{$name});
@endphp
<div class="container-fluid px-4 py-6 max-w-4xl">
    @include('elternabfragen.admin._nav', [
        'title' => $isNew ? 'Neue Abfrage' : 'Abfrage bearbeiten',
        'subtitle' => 'Die Abfrage wird erst nach der Freigabe versendet.',
        'icon' => $isNew ? 'fas fa-plus' : 'fas fa-pen',
        'back' => $isNew ? ['url' => route('elternabfragen.admin.index'), 'label' => 'Alle Abfragen'] : ['url' => route('elternabfragen.admin.inquiries.show', $inquiry), 'label' => 'Zurück zur Abfrage'],
    ])

    @if($inquiry->review_note && $inquiry->exists)
        <div class="ea-alert ea-alert-danger mb-5"><i class="fas fa-undo" aria-hidden="true"></i>
            <p class="mb-0"><strong>Zur Überarbeitung zurückgegeben:</strong> {{ $inquiry->review_note }}</p>
        </div>
    @endif

    @if($versions->isEmpty())
        <div class="ea-card">
            <div class="ea-empty">
                <div class="ea-empty-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></div>
                <p class="font-semibold ea-text mb-1">Es gibt noch keine freigegebene Vorlage.</p>
                <a href="{{ route('elternabfragen.admin.templates.create') }}" class="ea-btn ea-btn-primary mt-3"><i class="fas fa-plus" aria-hidden="true"></i> Vorlage anlegen</a>
            </div>
        </div>
    @else
    <form method="POST" action="{{ $isNew ? route('elternabfragen.admin.inquiries.store') : route('elternabfragen.admin.inquiries.update', $inquiry) }}"
          class="space-y-6"
          x-data="{
              target: @js(old('target_type', $inquiry->target_type)),
              version: @js((string) old('template_version_id', $inquiry->template_version_id ?? $versions->first()?->id)),
              templateOf: @js($versions->mapWithKeys(fn ($v) => [$v->id => $v->template_id])),
          }">
        @csrf
        @unless($isNew) @method('PUT') @endunless

        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><span class="text-sm font-bold">1</span></div>
                <div>
                    <h2 class="ea-card-title">Inhalt und Zeitraum</h2>
                    <p class="ea-card-sub">Welche Vorlage, welcher Titel, bis wann?</p>
                </div>
            </div>
            <div class="ea-card-body grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="template_version_id" class="ea-label">Vorlage (freigegebene Version) *</label>
                    <select id="template_version_id" name="template_version_id" required class="ea-input" x-model="version">
                        @foreach($versions as $version)
                            <option value="{{ $version->id }}" @selected((int) old('template_version_id', $inquiry->template_version_id) === $version->id)>
                                {{ $version->label() }}{{ $version->template?->category ? ' · '.$version->template->category : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="previous_inquiry_id" class="ea-label">Angaben vorbefüllen aus (z. B. Vorjahr)</label>
                    <select id="previous_inquiry_id" name="previous_inquiry_id" class="ea-input" aria-describedby="previous_inquiry_hint">
                        <option value="">– keine Vorbefüllung –</option>
                        @foreach($previousInquiries as $previous)
                            <option value="{{ $previous->id }}" @selected((int) old('previous_inquiry_id', $inquiry->previous_inquiry_id) === $previous->id)
                                    :disabled="templateOf[version] !== {{ (int) $previous->templateVersion?->template_id }}">
                                {{ $previous->title }} · Frist {{ $previous->deadline_at->format('d.m.Y') }}
                            </option>
                        @endforeach
                    </select>
                    <p id="previous_inquiry_hint" class="ea-hint">
                        Eltern sehen die Angaben ihres Kindes aus dieser Abfrage vorausgefüllt (ohne Einwilligungen und Dateien).
                        Änderungen gegenüber der Vorabfrage werden im Rücklauf aufgelistet. Wählbar sind nur Abfragen derselben Vorlage, deren Antworten noch nicht gelöscht sind.
                    </p>
                    @error('previous_inquiry_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="title" class="ea-label">Titel *</label>
                    <input id="title" name="title" required maxlength="255" value="{{ old('title', $inquiry->title) }}" class="ea-input" placeholder="z. B. Fahrraderlaubnis 2026/27">
                </div>
                <div class="md:col-span-2">
                    <label for="intro" class="ea-label">Einführungstext</label>
                    <textarea id="intro" name="intro" rows="4" class="ea-input" placeholder="Kurze, verständliche Einleitung für die Eltern">{{ old('intro', $inquiry->intro) }}</textarea>
                </div>
                <div>
                    <label for="starts_at" class="ea-label"><i class="fas fa-play text-xs ea-muted" aria-hidden="true"></i> Startzeitpunkt *</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" required value="{{ old('starts_at', $inquiry->starts_at?->format('Y-m-d\TH:i')) }}" class="ea-input">
                </div>
                <div>
                    <label for="deadline_at" class="ea-label"><i class="fas fa-flag-checkered text-xs ea-muted" aria-hidden="true"></i> Rückgabefrist *</label>
                    <input type="datetime-local" id="deadline_at" name="deadline_at" required value="{{ old('deadline_at', $inquiry->deadline_at?->format('Y-m-d\TH:i')) }}" class="ea-input">
                </div>
            </div>
        </section>

        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><span class="text-sm font-bold">2</span></div>
                <div>
                    <h2 class="ea-card-title">Zielgruppe</h2>
                    <p class="ea-card-sub">Für welche Kinder gilt die Abfrage?</p>
                </div>
            </div>
            <div class="ea-card-body space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="ea-choice">
                        <input type="radio" name="target_type" value="groups" x-model="target">
                        <span><span class="block font-medium ea-text">Klassen, Jahrgänge oder Gruppen</span><span class="block text-xs ea-muted">Auswahl unten</span></span>
                    </label>
                    <label class="ea-choice">
                        <input type="radio" name="target_type" value="all" x-model="target">
                        <span><span class="block font-medium ea-text">Gesamte Schule</span><span class="block text-xs ea-muted">alle aktiven Kinder</span></span>
                    </label>
                </div>
                <div x-show="target === 'groups'" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 max-h-72 overflow-y-auto p-1">
                    @foreach($groups as $group)
                        <label class="ea-toggle-row !p-2.5 text-sm ea-text">
                            <input type="checkbox" class="ea-check mt-0.5" name="target_group_ids[]" value="{{ $group->id }}" @checked(in_array($group->id, $selectedGroups, true))> {{ $group->name }}
                        </label>
                    @endforeach
                </div>
                <p class="ea-hint">Betroffen sind Kinder, deren Klasse, Gruppe oder weitere Gruppe (z. B. Klassenstufe) ausgewählt ist. Berechtigt zur Abgabe sind sorgeberechtigte Bezugspersonen.</p>
            </div>
        </section>

        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><span class="text-sm font-bold">3</span></div>
                <div>
                    <h2 class="ea-card-title">Zuständigkeit, Benachrichtigung und Erinnerungen</h2>
                    <p class="ea-card-sub">Wer kümmert sich, und wie werden Eltern informiert?</p>
                </div>
            </div>
            <div class="ea-card-body grid gap-5 md:grid-cols-2">
                <div>
                    <label for="responsible_user_id" class="ea-label">Verantwortliche Person</label>
                    <select id="responsible_user_id" name="responsible_user_id" class="ea-input">
                        <option value="">–</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((int) old('responsible_user_id', $inquiry->responsible_user_id) === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="correction_window_days" class="ea-label">Selbstkorrektur durch Eltern (Tage nach Abgabe)</label>
                    <input type="number" min="0" max="365" id="correction_window_days" name="correction_window_days" value="{{ old('correction_window_days', $inquiry->correction_window_days) }}" class="ea-input">
                </div>
                <div class="md:col-span-2 grid gap-3 sm:grid-cols-2 text-sm ea-text">
                    <label class="ea-toggle-row"><input type="checkbox" class="ea-check mt-0.5" name="notify_mail" value="1" @checked($check('notify_mail'))> <span><i class="fas fa-envelope ea-muted mr-1" aria-hidden="true"></i> E-Mail bei Freischaltung</span></label>
                    <label class="ea-toggle-row"><input type="checkbox" class="ea-check mt-0.5" name="notify_push" value="1" @checked($check('notify_push'))> <span><i class="fas fa-mobile-alt ea-muted mr-1" aria-hidden="true"></i> App-/Web-Push und Glocke</span></label>
                    <label class="ea-toggle-row"><input type="checkbox" class="ea-check mt-0.5" name="auto_reminders" value="1" @checked($check('auto_reminders'))> <span><i class="fas fa-bell ea-muted mr-1" aria-hidden="true"></i> Automatische Erinnerungen (Regeln)</span></label>
                    <label class="ea-toggle-row"><input type="checkbox" class="ea-check mt-0.5" name="staff_visible" value="1" @checked($check('staff_visible'))> <span><i class="fas fa-chalkboard-teacher ea-muted mr-1" aria-hidden="true"></i> Geprüfte Antworten für Mitarbeitende lesbar</span></label>
                </div>
            </div>
        </section>

        <div class="ea-card p-4 flex flex-wrap items-center gap-3 sm:sticky sm:bottom-4 z-10 shadow-lg">
            <button type="submit" class="ea-btn ea-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> Entwurf speichern</button>
            <button type="submit" name="submit_approval" value="1" class="ea-btn ea-btn-success"><i class="fas fa-paper-plane" aria-hidden="true"></i> Speichern und zur Freigabe einreichen</button>
            <a href="{{ $isNew ? route('elternabfragen.admin.index') : route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="ea-btn ea-btn-ghost ml-auto">Abbrechen</a>
        </div>
    </form>
    @endif
</div>
@endsection
