@extends('layouts.app')

@section('title', '| Links verwalten')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="ea-hero mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="ea-hero-icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <div class="min-w-0">
                    <h1 class="ea-hero-title">Links verwalten</h1>
                    <p class="ea-hero-sub">Links für alle Nutzer oder für ausgewählte Gruppen – im Board und in der App.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 flex-shrink-0">
                <a href="{{ route('links.index') }}" class="ea-btn ea-btn-secondary"><i class="fas fa-eye" aria-hidden="true"></i> Ansicht</a>
                <a href="{{ route('links.admin.create') }}" class="ea-btn ea-btn-light"><i class="fas fa-plus" aria-hidden="true"></i> Neuer Link</a>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            <span class="ea-hero-meta"><i class="fas fa-link" aria-hidden="true"></i> {{ $links->count() }} {{ $links->count() === 1 ? 'Link' : 'Links' }}</span>
            @if($links->where('is_active', false)->isNotEmpty())
                <span class="ea-hero-meta"><i class="fas fa-eye-slash" aria-hidden="true"></i> {{ $links->where('is_active', false)->count() }} ausgeblendet</span>
            @endif
            @if($links->where('pinned', true)->isNotEmpty())
                <span class="ea-hero-meta"><i class="fas fa-thumbtack" aria-hidden="true"></i> {{ $links->where('pinned', true)->count() }} auf der Startseite</span>
            @endif
        </div>
    </div>

    @forelse($categories as $category => $items)
        <section class="mb-6" aria-label="{{ $category !== '' ? $category : 'Ohne Kategorie' }}">
            <h2 class="ea-section-title"><i class="fas fa-folder-open" aria-hidden="true"></i> {{ $category !== '' ? $category : 'Ohne Kategorie' }}</h2>
            <ul class="ea-card ea-divide overflow-hidden list-none p-0 mb-0">
                @foreach($items as $link)
                    <li class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3 @unless($link->is_active) opacity-60 @endunless">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <span class="ea-card-icon"><i class="{{ $link->iconClass() }}" aria-hidden="true"></i></span>
                            <div class="min-w-0">
                                <p class="font-semibold ea-text mb-0 break-words">
                                    {{ $link->title }}
                                    @unless($link->is_active)
                                        <x-elternabfragen.badge color="slate" icon="fas fa-eye-slash">ausgeblendet</x-elternabfragen.badge>
                                    @endunless
                                    @if($link->pinned)
                                        <x-elternabfragen.badge color="blue" icon="fas fa-thumbtack">Startseite</x-elternabfragen.badge>
                                    @endif
                                    @if($studentAudience && $link->zielgruppe !== \App\Support\StudentAccounts::ZIELGRUPPE_ELTERN)
                                        <x-elternabfragen.badge color="teal" icon="fas fa-user-graduate">{{ \App\Support\AudienceField::label($link->zielgruppe) }}</x-elternabfragen.badge>
                                    @endif
                                </p>
                                <a href="{{ $link->href() }}" @if($link->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif class="text-sm ea-link break-all">{{ \Illuminate\Support\Str::limit($link->url, 80) }}</a>
                                <p class="text-xs ea-muted mt-1 mb-0">
                                    <i class="fas fa-users" aria-hidden="true"></i>
                                    @if($link->for_all)
                                        Alle Nutzer
                                    @elseif($link->groups->isEmpty())
                                        <span class="text-red-600">Keine Gruppe – für niemanden sichtbar</span>
                                    @else
                                        {{ $link->groups->pluck('name')->join(', ') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0 sm:justify-end">
                            <form method="POST" action="{{ route('links.admin.move', $link) }}">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button class="ea-icon-btn" title="Nach oben" aria-label="{{ $link->title }} nach oben verschieben" @disabled($loop->first)><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
                            </form>
                            <form method="POST" action="{{ route('links.admin.move', $link) }}">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button class="ea-icon-btn" title="Nach unten" aria-label="{{ $link->title }} nach unten verschieben" @disabled($loop->last)><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
                            </form>
                            <form method="POST" action="{{ route('links.admin.toggle', $link) }}">
                                @csrf
                                <button class="ea-icon-btn" title="{{ $link->is_active ? 'Ausblenden' : 'Einblenden' }}" aria-label="{{ $link->title }} {{ $link->is_active ? 'ausblenden' : 'einblenden' }}">
                                    <i class="fas {{ $link->is_active ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i>
                                </button>
                            </form>
                            <a href="{{ route('links.admin.edit', $link) }}" class="ea-icon-btn" title="Bearbeiten" aria-label="{{ $link->title }} bearbeiten"><i class="fas fa-pen" aria-hidden="true"></i></a>
                            <form method="POST" action="{{ route('links.admin.destroy', $link) }}" onsubmit="return confirm('Link „{{ addslashes($link->title) }}“ wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button class="ea-icon-btn" title="Löschen" aria-label="{{ $link->title }} löschen"><i class="fas fa-trash text-red-600" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="ea-card">
            <div class="ea-empty">
                <div class="ea-empty-icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <p class="font-semibold ea-text mb-1">Noch keine Links angelegt</p>
                <p class="text-sm mb-4">Legen Sie z. B. die Schulhomepage, die Mensa-Bestellung oder wichtige Formulare an.</p>
                <a href="{{ route('links.admin.create') }}" class="ea-btn ea-btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Ersten Link anlegen</a>
            </div>
        </div>
    @endforelse

    @if($links->count() > 1)
        <p class="text-xs ea-muted mt-2"><i class="fas fa-info-circle" aria-hidden="true"></i> Pfeile verschieben einen Link innerhalb seiner Kategorie. Die Reihenfolge gilt im Board und in der App; Kategorien erscheinen in der Reihenfolge ihres ersten Links.</p>
    @endif
</div>
@endsection
