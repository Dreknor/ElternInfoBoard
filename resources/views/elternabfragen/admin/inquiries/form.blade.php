@extends('layouts.app')

@section('title', '| Elternabfragen · Abfrage')

@section('content')
@php
    $isNew = ! $inquiry->exists;
    $label = 'block text-sm font-medium text-gray-700 mb-1';
    $input = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500';
    $selectedGroups = array_map('intval', old('target_group_ids', $inquiry->target_group_ids ?? []));
    $check = fn (string $name) => (bool) old($name, $inquiry->{$name});
@endphp
<div class="container-fluid px-4 py-6 max-w-4xl">
    @include('elternabfragen.admin._nav', [
        'title' => $isNew ? 'Neue Abfrage' : 'Abfrage bearbeiten',
        'subtitle' => 'Die Abfrage wird erst nach der Freigabe versendet.',
    ])

    @if($inquiry->review_note && $inquiry->exists)
        <div class="mb-4 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-900 text-sm">
            <strong>Zur Überarbeitung zurückgegeben:</strong> {{ $inquiry->review_note }}
        </div>
    @endif

    @if($versions->isEmpty())
        <div class="bg-white rounded-xl border p-6 text-gray-600">
            Es gibt noch keine freigegebene Vorlage. <a href="{{ route('elternabfragen.admin.templates.create') }}" class="text-blue-700 underline">Vorlage anlegen</a>
        </div>
    @else
    <form method="POST" action="{{ $isNew ? route('elternabfragen.admin.inquiries.store') : route('elternabfragen.admin.inquiries.update', $inquiry) }}"
          x-data="{ target: @js(old('target_type', $inquiry->target_type)) }">
        @csrf
        @unless($isNew) @method('PUT') @endunless

        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="template_version_id" class="{{ $label }}">Vorlage (freigegebene Version) *</label>
                <select id="template_version_id" name="template_version_id" required class="{{ $input }}">
                    @foreach($versions as $version)
                        <option value="{{ $version->id }}" @selected((int) old('template_version_id', $inquiry->template_version_id) === $version->id)>
                            {{ $version->label() }}{{ $version->template?->category ? ' · '.$version->template->category : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="title" class="{{ $label }}">Titel *</label>
                <input id="title" name="title" required maxlength="255" value="{{ old('title', $inquiry->title) }}" class="{{ $input }}">
            </div>
            <div class="md:col-span-2">
                <label for="intro" class="{{ $label }}">Einführungstext</label>
                <textarea id="intro" name="intro" rows="4" class="{{ $input }}" placeholder="Kurze, verständliche Einleitung für die Eltern">{{ old('intro', $inquiry->intro) }}</textarea>
            </div>
            <div>
                <label for="starts_at" class="{{ $label }}">Startzeitpunkt *</label>
                <input type="datetime-local" id="starts_at" name="starts_at" required value="{{ old('starts_at', $inquiry->starts_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
            </div>
            <div>
                <label for="deadline_at" class="{{ $label }}">Rückgabefrist *</label>
                <input type="datetime-local" id="deadline_at" name="deadline_at" required value="{{ old('deadline_at', $inquiry->deadline_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 mb-3">Zielgruppe</h2>
            <div class="flex flex-wrap gap-4 mb-3 text-sm">
                <label class="flex items-center gap-2"><input type="radio" name="target_type" value="groups" x-model="target"> Klassen, Jahrgänge oder Gruppen</label>
                <label class="flex items-center gap-2"><input type="radio" name="target_type" value="all" x-model="target"> Gesamte Schule (alle aktiven Kinder)</label>
            </div>
            <div x-show="target === 'groups'" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 max-h-72 overflow-y-auto border rounded-lg p-3">
                @foreach($groups as $group)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="target_group_ids[]" value="{{ $group->id }}" @checked(in_array($group->id, $selectedGroups, true))> {{ $group->name }}
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-2">Betroffen sind Kinder, deren Klasse, Gruppe oder weitere Gruppe (z. B. Klassenstufe) ausgewählt ist. Berechtigt zur Abgabe sind sorgeberechtigte Bezugspersonen.</p>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6 grid gap-4 md:grid-cols-2">
            <h2 class="md:col-span-2 font-semibold text-gray-800">Zuständigkeit, Benachrichtigung und Erinnerungen</h2>
            <div>
                <label for="responsible_user_id" class="{{ $label }}">Verantwortliche Person</label>
                <select id="responsible_user_id" name="responsible_user_id" class="{{ $input }}">
                    <option value="">–</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((int) old('responsible_user_id', $inquiry->responsible_user_id) === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="correction_window_days" class="{{ $label }}">Selbstkorrektur durch Eltern (Tage nach Abgabe)</label>
                <input type="number" min="0" max="365" id="correction_window_days" name="correction_window_days" value="{{ old('correction_window_days', $inquiry->correction_window_days) }}" class="{{ $input }}">
            </div>
            <div class="md:col-span-2 grid gap-2 sm:grid-cols-2 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="notify_mail" value="1" @checked($check('notify_mail'))> E-Mail bei Freischaltung</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="notify_push" value="1" @checked($check('notify_push'))> App-/Web-Push und Glocke</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="auto_reminders" value="1" @checked($check('auto_reminders'))> Automatische Erinnerungen (Regeln)</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="staff_visible" value="1" @checked($check('staff_visible'))> Geprüfte Antworten für Mitarbeitende lesbar</label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">Entwurf speichern</button>
            <button type="submit" name="submit_approval" value="1" class="px-5 py-2.5 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700">Speichern und zur Freigabe einreichen</button>
            <a href="{{ $isNew ? route('elternabfragen.admin.index') : route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700">Abbrechen</a>
        </div>
    </form>
    @endif
</div>
@endsection
