@extends('layouts.app')

@section('title') - Hilfe & Anleitung @endsection

@section('content')
<div class="max-w-6xl mx-auto px-3 sm:px-4 py-2 sm:py-6">

    {{-- Header --}}
    <div class="ea-hero mb-4 sm:mb-6">
        <div class="flex items-center gap-4">
            <div class="ea-hero-icon"><i class="fas fa-circle-question" aria-hidden="true"></i></div>
            <div class="min-w-0">
                <h1 class="ea-hero-title">Hilfe &amp; Anleitung</h1>
                <p class="ea-hero-sub">
                    Hier findest du Antworten auf häufige Fragen und Schritt-für-Schritt-Anleitungen –
                    passend zu deinen Berechtigungen.
                </p>
            </div>
        </div>
    </div>

    @if($grouped->isEmpty())
        <div class="ea-card">
            <div class="ea-empty">
                <div class="ea-empty-icon"><i class="fas fa-circle-question" aria-hidden="true"></i></div>
                <p class="font-semibold ea-text mb-1">Keine Hilfe-Themen verfügbar</p>
                <p class="text-sm mb-0">Aktuell sind für dich keine Hilfe-Themen verfügbar.</p>
            </div>
        </div>
    @endif

    @foreach($grouped as $groupKey => $topics)
        <section class="mb-6 sm:mb-8" aria-labelledby="help-group-{{ $loop->index }}">
            <h2 id="help-group-{{ $loop->index }}" class="ea-section-title">
                <i class="fas fa-folder-open" aria-hidden="true"></i>
                {{ $help->groupLabel($groupKey) }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                @foreach($topics as $topic)
                    <a href="{{ url('hilfe/'.$topic['slug']) }}" class="ea-card block p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="ea-card-icon w-11 h-11 text-lg">
                                <i class="{{ $topic['icon'] }}" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold ea-text text-base mb-1 leading-tight">{{ $topic['title'] }}</h3>
                                <p class="text-sm ea-muted mb-0 leading-snug">{{ $topic['excerpt'] }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="ea-card ea-subtle p-5 text-center mt-8">
        <p class="text-sm ea-muted mb-3">
            <i class="fas fa-circle-info" style="color: var(--color-primary);" aria-hidden="true"></i>
            Du findest deine Frage hier nicht?
        </p>
        <a href="{{ url('feedback') }}" class="ea-btn ea-btn-primary">
            <i class="fas fa-comment-dots" aria-hidden="true"></i>
            Feedback / Anfrage senden
        </a>
    </div>
</div>
@endsection
