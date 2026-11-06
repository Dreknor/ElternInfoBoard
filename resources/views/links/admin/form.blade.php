@extends('layouts.app')

@section('title', $link->exists ? '| Link bearbeiten' : '| Neuer Link')

@section('content')
@php
    $selectedGroups = $link->exists ? $link->groups : collect();
    $forAll = (bool) old('for_all', $link->for_all);
    $currentIcon = old('icon', $link->icon);
@endphp
<div class="max-w-3xl mx-auto px-4 py-6">
    <div class="mb-4">
        <a href="{{ route('links.admin.index') }}" class="ea-link text-sm"><i class="fas fa-arrow-left" aria-hidden="true"></i> Zurück zur Übersicht</a>
    </div>

    <form method="POST"
          action="{{ $link->exists ? route('links.admin.update', $link) : route('links.admin.store') }}"
          class="space-y-6"
          x-data="{ forAll: @js($forAll), icon: @js($currentIcon ?? '') }">
        @csrf
        @if($link->exists) @method('PUT') @endif

        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <h1 class="ea-card-title">{{ $link->exists ? 'Link bearbeiten' : 'Neuer Link' }}</h1>
            </div>
            <div class="ea-card-body grid gap-5">
                <div>
                    <label for="title" class="ea-label">Titel <span class="text-red-600">*</span></label>
                    <input type="text" id="title" name="title" maxlength="120" required value="{{ old('title', $link->title) }}"
                           class="ea-input @error('title') ea-input-invalid @enderror" placeholder="z. B. Mensa-Bestellung">
                    @error('title')<p class="ea-hint !text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="url" class="ea-label">Adresse <span class="text-red-600">*</span></label>
                    <input type="text" id="url" name="url" maxlength="2048" required inputmode="url" autocomplete="off"
                           value="{{ old('url', $link->url) }}"
                           class="ea-input @error('url') ea-input-invalid @enderror" placeholder="https://www.beispiel.de">
                    @error('url')
                        <p class="ea-hint !text-red-600">{{ $message }}</p>
                    @else
                        <p class="ea-hint">Externe Seiten mit https://…, E-Mail mit mailto:…, Telefon mit tel:… oder Seiten dieses Boards als Pfad, z. B. <code>/termine</code>. Externe Links öffnen in einem neuen Fenster.</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="ea-label">Beschreibung</label>
                    <textarea id="description" name="description" rows="2" maxlength="500"
                              class="ea-input no-tinymce @error('description') ea-input-invalid @enderror"
                              placeholder="Kurzer Hinweis, was dort zu finden ist (optional)">{{ old('description', $link->description) }}</textarea>
                    @error('description')<p class="ea-hint !text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="category" class="ea-label">Kategorie</label>
                        <input type="text" id="category" name="category" maxlength="80" list="link-categories"
                               value="{{ old('category', $link->category) }}"
                               class="ea-input @error('category') ea-input-invalid @enderror" placeholder="z. B. Schule, Förderverein">
                        <datalist id="link-categories">
                            @foreach($categories as $category)
                                <option value="{{ $category }}">
                            @endforeach
                        </datalist>
                        @error('category')<p class="ea-hint !text-red-600">{{ $message }}</p>@else<p class="ea-hint">Links mit gleicher Kategorie werden zusammen angezeigt.</p>@enderror
                    </div>
                    <div>
                        <label for="icon" class="ea-label">Symbol</label>
                        <div class="flex items-center gap-2">
                            <span class="ea-card-icon" aria-hidden="true"><i :class="icon || 'fas fa-link'"></i></span>
                            <select id="icon" name="icon" x-model="icon" class="ea-input @error('icon') ea-input-invalid @enderror">
                                <option value="">Automatisch (nach Art des Links)</option>
                                @foreach(\App\Model\Link::ICONS as $class => $label)
                                    <option value="{{ $class }}" @selected($currentIcon === $class)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('icon')<p class="ea-hint !text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                <h2 class="ea-card-title">Sichtbarkeit</h2>
            </div>
            <div class="ea-card-body space-y-3">
                <label class="ea-toggle-row text-sm ea-text">
                    <input type="checkbox" name="for_all" value="1" class="ea-check mt-0.5" x-model="forAll" @checked($forAll)>
                    <span><strong>Für alle Nutzer</strong> – sonst nur für die unten gewählten Gruppen.</span>
                </label>

                <div x-show="!forAll" x-cloak>
                    @error('gruppen')<p class="ea-alert ea-alert-danger mb-3"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> <span>{{ $message }}</span></p>@enderror
                    @include('include.formGroups', ['groups' => true, 'selectedGroups' => $selectedGroups])
                </div>

                @include('student.partials.audience-field', [
                    'value' => $link->zielgruppe,
                    'permission' => \App\Model\Link::MANAGE_PERMISSION,
                    'hint' => 'Schüler sehen den Link, wenn er für alle oder für ihre Klasse bzw. Gruppe freigegeben ist. Links „nur Schüler“ sehen Eltern nie. Interne Links funktionieren für Schüler nur innerhalb der Schüleransicht (/schueler…).',
                ])

                <label class="ea-toggle-row text-sm ea-text">
                    <input type="checkbox" name="pinned" value="1" class="ea-check mt-0.5" @checked(old('pinned', $link->pinned))>
                    <span><strong>Auf der Startseite anzeigen</strong> – zusätzlich als Schnellzugriff im Dashboard und in der App.</span>
                </label>

                <label class="ea-toggle-row text-sm ea-text">
                    <input type="checkbox" name="is_active" value="1" class="ea-check mt-0.5" @checked(old('is_active', $link->is_active))>
                    <span><strong>Sichtbar</strong> – ausgeblendete Links sehen nur Personen, die Links verwalten.</span>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-2">
            <button class="ea-btn ea-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> Speichern</button>
            <a href="{{ route('links.admin.index') }}" class="ea-btn ea-btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
