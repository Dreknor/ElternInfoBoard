@extends('layouts.app')

@section('title', '| Links')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="ea-hero mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="ea-hero-icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <div class="min-w-0">
                    <h1 class="ea-hero-title">Links</h1>
                    <p class="ea-hero-sub">Wichtige Seiten und Angebote auf einen Blick.</p>
                </div>
            </div>
            @can('edit links')
                <a href="{{ route('links.admin.index') }}" class="ea-btn ea-btn-light flex-shrink-0">
                    <i class="fas fa-cog" aria-hidden="true"></i> Links verwalten
                </a>
            @endcan
        </div>
    </div>

    @forelse($categories as $category => $items)
        <section class="mb-6" @if($category !== '') aria-labelledby="links-cat-{{ $loop->index }}" @else aria-label="Links" @endif>
            @if($category !== '')
                <h2 id="links-cat-{{ $loop->index }}" class="ea-section-title"><i class="fas fa-folder-open" aria-hidden="true"></i> {{ $category }}</h2>
            @endif
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 list-none p-0 m-0">
                @foreach($items as $link)
                    <li>@include('links._link', ['link' => $link])</li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="ea-card">
            <div class="ea-empty">
                <div class="ea-empty-icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <p class="font-semibold ea-text mb-1">Noch keine Links</p>
                <p class="text-sm mb-0">Hier erscheinen Links, sobald die Schule welche für Sie hinterlegt.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
