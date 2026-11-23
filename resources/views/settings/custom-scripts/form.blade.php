@extends('layouts.app')

@section('title') - Eigene Skripte @endsection

@php
    $isNew = ! $script->exists;
    $selectedRoles = collect(old('roles', $script->roles?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
    $fieldStyle = 'background-color: var(--color-body-bg); border: 1px solid var(--color-card-border); color: var(--color-text-primary);';
@endphp

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="rounded-xl shadow-lg overflow-hidden" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);">

        <div class="px-6 py-4 flex items-center gap-4" style="background-color: var(--color-primary); border-bottom: 3px solid var(--color-primary-dark);">
            <div class="flex items-center justify-center w-10 h-10 bg-white/20 rounded-xl flex-shrink-0">
                <i class="fas fa-code text-white text-xl"></i>
            </div>
            <div class="flex-1">
                <h5 class="text-xl font-bold text-white mb-0">{{ $isNew ? 'Neues Skript' : 'Skript bearbeiten' }}</h5>
                <p class="text-xs text-white/70 mb-0 mt-0.5">Eigene Skripte</p>
            </div>
            <a href="{{ route('settings.custom-scripts.index') }}" class="text-sm text-white/80 hover:text-white">
                <i class="fas fa-arrow-left mr-1"></i> Übersicht
            </a>
        </div>

        <div class="p-6 space-y-6" style="color: var(--color-text-primary);"
             x-data="{
                pageMode: @js(old('page_mode', $script->page_mode)),
                forUsers: @js((bool) old('for_users', $script->for_users)),
                restrictRoles: @js((bool) old('restrict_roles', $script->restrict_roles)),
             }">

            @if($script->disabled_reason)
                <div class="rounded-lg p-4 bg-red-50 border border-red-300 text-red-900 dark:bg-red-900/30 dark:text-red-100 text-sm">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $script->disabled_reason }}
                    Der Hinweis verschwindet beim nächsten Speichern.
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg p-4 bg-red-50 border border-red-300 text-red-900 dark:bg-red-900/30 dark:text-red-100 text-sm">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="post" class="space-y-6"
                  action="{{ $isNew ? route('settings.custom-scripts.store') : route('settings.custom-scripts.update', $script) }}">
                @csrf
                @unless($isNew) @method('PUT') @endunless

                {{-- Allgemein --}}
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label for="name" class="block font-semibold mb-1">Name</label>
                        <input type="text" id="name" name="name" maxlength="100" required
                               value="{{ old('name', $script->name) }}" placeholder="z. B. Chatbot oder Abmelde-Gag"
                               class="w-full rounded-lg p-2" style="{{ $fieldStyle }}">
                    </div>
                    <div>
                        <label for="sort_order" class="block font-semibold mb-1">Reihenfolge</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" max="65535"
                               value="{{ old('sort_order', $script->sort_order) }}"
                               class="w-full rounded-lg p-2" style="{{ $fieldStyle }}">
                        <p class="text-xs mt-1 opacity-75">Kleinere Zahlen werden zuerst ausgegeben.</p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block font-semibold mb-1">Beschreibung <span class="font-normal opacity-75">(optional)</span></label>
                    <input type="text" id="description" name="description" maxlength="500"
                           value="{{ old('description', $script->description) }}" placeholder="Wofür ist das Skript, wer hat es geliefert?"
                           class="w-full rounded-lg p-2" style="{{ $fieldStyle }}">
                </div>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" class="w-4 h-4" @checked(old('active', $script->active))>
                    <span class="font-semibold">Skript ausgeben (aktiv)</span>
                </label>

                <div>
                    <label for="snippet" class="block font-semibold mb-1">Snippet (HTML / JavaScript)</label>
                    <textarea id="snippet" name="snippet" rows="12" spellcheck="false" maxlength="{{ $maxLength }}" required
                              class="no-tinymce w-full rounded-lg p-3 font-mono text-sm" style="{{ $fieldStyle }}"
                              placeholder="&lt;script src=&quot;https://chatbot.example/widget.js&quot; data-id=&quot;…&quot; defer&gt;&lt;/script&gt;">{{ old('snippet', $script->snippet) }}</textarea>
                    <p class="text-xs mt-1 opacity-75">Den Code so einfügen, wie ihn der Anbieter liefert, inklusive
                        <code>&lt;script&gt;</code>-Tags. Höchstens {{ number_format($maxLength, 0, ',', '.') }} Zeichen.</p>
                </div>

                <div>
                    <label for="position" class="block font-semibold mb-1">Ausgabeort</label>
                    <select id="position" name="position" class="w-full md:w-1/2 rounded-lg p-2" style="{{ $fieldStyle }}">
                        <option value="body" @selected(old('position', $script->position) === 'body')>Ende des Seiteninhalts (empfohlen)</option>
                        <option value="head" @selected(old('position', $script->position) === 'head')>Im Seitenkopf (&lt;head&gt;)</option>
                    </select>
                </div>

                {{-- Seiten --}}
                <fieldset class="rounded-lg p-4 space-y-3" style="border: 1px solid var(--color-card-border);">
                    <legend class="px-1 font-semibold"><i class="fas fa-file-alt mr-1"></i> Seiten</legend>

                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        @foreach(['all' => 'Alle Seiten', 'only' => 'Nur auf diesen Seiten', 'except' => 'Überall außer auf diesen Seiten'] as $value => $label)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="page_mode" value="{{ $value }}" x-model="pageMode"
                                       @checked(old('page_mode', $script->page_mode) === $value)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div x-show="pageMode !== 'all'" x-cloak>
                        <label for="page_patterns" class="block font-semibold mb-1">Seiten (eine pro Zeile)</label>
                        <textarea id="page_patterns" name="page_patterns" rows="4" spellcheck="false"
                                  class="no-tinymce w-full rounded-lg p-3 font-mono text-sm" style="{{ $fieldStyle }}"
                                  placeholder="care/anwesenheit*">{{ old('page_patterns', $script->page_patterns) }}</textarea>
                        <div class="text-xs mt-1 opacity-75 space-y-1">
                            <p class="mb-0">Pfad der Seite ohne Adresse der Instanz, z.&nbsp;B. bei
                                <code>{{ url('care/anwesenheit') }}</code> nur <code>care/anwesenheit</code>.
                                <code>*</code> steht für beliebige Zeichen, Groß-/Kleinschreibung und Parameter nach <code>?</code> zählen nicht.</p>
                            <p class="mb-0">Beispiele: <code>care/anwesenheit*</code> (Anwesenheitsliste),
                                <code>login</code> (Anmeldeseite), <code>/</code> (Startseite), <code>nachrichten*</code>.</p>
                        </div>
                    </div>
                </fieldset>

                {{-- Zielgruppe --}}
                <fieldset class="rounded-lg p-4 space-y-3" style="border: 1px solid var(--color-card-border);">
                    <legend class="px-1 font-semibold"><i class="fas fa-users mr-1"></i> Zielgruppe</legend>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="for_users" value="0">
                        <input type="checkbox" name="for_users" value="1" class="w-4 h-4" x-model="forUsers"
                               @checked(old('for_users', $script->for_users))>
                        <span>Angemeldete Konten (Eltern, Mitarbeiter …)</span>
                    </label>

                    <div class="ml-7 space-y-2" x-show="forUsers" x-cloak>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="restrict_roles" value="0">
                            <input type="checkbox" name="restrict_roles" value="1" class="w-4 h-4" x-model="restrictRoles"
                                   @checked(old('restrict_roles', $script->restrict_roles))>
                            <span>Auf Rollen begrenzen</span>
                        </label>
                        <div x-show="restrictRoles" x-cloak class="space-y-1">
                            @forelse($roles as $role)
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="w-4 h-4"
                                           @checked(in_array($role->id, $selectedRoles, true))>
                                    <span>{{ $role->name }}</span>
                                </label>
                            @empty
                                <p class="text-sm opacity-75 mb-0">Es sind keine Rollen angelegt.</p>
                            @endforelse
                            <p class="text-xs opacity-75 mb-0">Nur Konten mit mindestens einer der gewählten Rollen bekommen das Skript.
                                Wird die letzte gewählte Rolle gelöscht, wird das Skript automatisch deaktiviert.</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="for_guests" value="0">
                        <input type="checkbox" name="for_guests" value="1" class="w-4 h-4"
                               @checked(old('for_guests', $script->for_guests))>
                        <span>Gäste (nicht angemeldet, z.&nbsp;B. Anmelde- und Passwortseiten)</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="for_students" value="0">
                        <input type="checkbox" name="for_students" value="1" class="w-4 h-4"
                               @checked(old('for_students', $script->for_students))>
                        <span>Schülerkonten</span>
                    </label>

                    <p class="text-xs opacity-75 mb-0">Gäste und Schülerkonten haben keine Rollen – für sie zählt nur der jeweilige Haken,
                        die Rollenbegrenzung wirkt allein auf angemeldete Konten.</p>
                </fieldset>

                {{-- Chat --}}
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="use_on_chat" value="0">
                    <input type="checkbox" name="use_on_chat" value="1" class="w-4 h-4 mt-1"
                           @checked(old('use_on_chat', $script->use_on_chat))>
                    <span>Auf der Chat-Seite verwenden
                        <span class="block text-xs opacity-75">Die Chat-Seite (<code>{{ url('chat') }}</code>, u.&nbsp;a. für den Button in der App)
                            lädt das Skript unabhängig von der Seitenregel; die Zielgruppe gilt auch dort.</span>
                    </span>
                </label>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <p class="text-xs opacity-75 mb-0">
                        @if($script->exists)
                            Zuletzt geändert am {{ $script->updated_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            @if($script->editor) von {{ $script->editor->name }} @endif
                        @else
                            Noch nicht gespeichert.
                        @endif
                    </p>
                    <div class="flex gap-2">
                        <a href="{{ route('settings.custom-scripts.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium"
                           style="border: 1px solid var(--color-card-border);">Abbrechen</a>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white" style="background-color: var(--color-primary);">
                            <i class="fas fa-save mr-1"></i> Speichern
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
