@extends('layouts.app')

@section('title', '| Elternabfragen · Vorlagen')

@can('manage elternabfragen')
    @section('ea-actions')
        <a href="{{ route('elternabfragen.admin.templates.create') }}" class="ea-btn ea-btn-light">
            <i class="fas fa-plus" aria-hidden="true"></i> Neue Vorlage
        </a>
    @endsection
@endcan

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => 'Vorlagen', 'subtitle' => 'Geprüfte, versionierte Vorlagen für wiederkehrende Abfragen', 'icon' => 'fas fa-layer-group'])

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <a href="{{ route('elternabfragen.admin.templates.index') }}" class="ea-chip {{ $archive ? '' : 'ea-chip-active' }}" @unless($archive) aria-current="page" @endunless><i class="fas fa-folder-open" aria-hidden="true"></i> Aktiv</a>
        <a href="{{ route('elternabfragen.admin.templates.index', ['archiv' => 1]) }}" class="ea-chip {{ $archive ? 'ea-chip-active' : '' }}" @if($archive) aria-current="page" @endif><i class="fas fa-archive" aria-hidden="true"></i> Archiv</a>
    </div>

    @if($templates->isEmpty())
        <div class="ea-card">
            <div class="ea-empty">
                <div class="ea-empty-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></div>
                <p class="font-semibold ea-text mb-1">Keine Vorlagen vorhanden.</p>
                <p class="text-sm mb-0">Eine Vorlage beschreibt Zweck und Formularfelder einer wiederkehrenden Abfrage.</p>
            </div>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach($templates as $template)
                @php $latest = $template->latestVersion; $approved = $template->approvedVersion; @endphp
                <article class="ea-card p-5 flex flex-col gap-4">
                    <div class="flex items-start gap-3">
                        <div class="ea-card-icon"><i class="fas fa-file-signature" aria-hidden="true"></i></div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('elternabfragen.admin.templates.show', $latest) }}" class="font-semibold ea-text hover:underline break-words">{{ $template->title }}</a>
                            <p class="text-xs ea-muted mb-0 mt-0.5">{{ $template->category ?? 'ohne Kategorie' }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @if($approved)
                            <x-elternabfragen.badge color="green" icon="fas fa-check">Version {{ $approved->version }} freigegeben</x-elternabfragen.badge>
                        @else
                            <x-elternabfragen.badge color="gray">noch nicht freigegeben</x-elternabfragen.badge>
                        @endif
                        @if($latest && $latest->id !== $approved?->id)
                            <x-elternabfragen.badge :color="$latest->status->color()" dot>v{{ $latest->version }}: {{ $latest->status->label() }}</x-elternabfragen.badge>
                        @endif
                        @if($latest?->is_sensitive) <x-elternabfragen.badge color="red" icon="fas fa-user-shield">sensibel</x-elternabfragen.badge> @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-auto pt-3" style="border-top: 1px solid var(--color-card-border)">
                        <a href="{{ route('elternabfragen.admin.templates.show', $latest) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-eye" aria-hidden="true"></i> Ansehen</a>
                        @if($approved && ! $archive)
                            @can('manage elternabfragen')
                                <a href="{{ route('elternabfragen.admin.inquiries.create', ['vorlage' => $approved->id]) }}" class="ea-btn ea-btn-success ea-btn-sm ml-auto"><i class="fas fa-paper-plane" aria-hidden="true"></i> Abfrage erstellen</a>
                            @endcan
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
