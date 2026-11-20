@extends('layouts.app')

@section('title') - {{ $topic['title'] }} @endsection


@section('content')
<div class="max-w-4xl mx-auto px-3 sm:px-4 py-2 sm:py-6">

    {{-- Breadcrumb --}}
    <nav class="text-sm mb-4" aria-label="Brotkrumen">
        <ol class="flex items-center gap-2 ea-muted list-none p-0 m-0 min-w-0">
            <li class="flex-shrink-0"><a href="{{ url('hilfe') }}" class="ea-link"><i class="fas fa-circle-question" aria-hidden="true"></i> Hilfe</a></li>
            <li class="flex-shrink-0"><i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></li>
            <li class="ea-text font-medium truncate" aria-current="page">{{ $topic['title'] }}</li>
        </ol>
    </nav>

    {{-- Topic Header --}}
    <header class="ea-hero mb-4">
        <div class="flex items-start sm:items-center gap-4">
            <div class="ea-hero-icon"><i class="{{ $topic['icon'] }}" aria-hidden="true"></i></div>
            <div class="min-w-0">
                <h1 class="ea-hero-title">{{ $topic['title'] }}</h1>
                @if(!empty($topic['excerpt']))
                    <p class="ea-hero-sub">{{ $topic['excerpt'] }}</p>
                @endif
            </div>
        </div>
    </header>

    {{-- Inhalt --}}
    <article class="ea-card">
        <div class="p-5 sm:p-8">
            <div class="prose help-prose max-w-none">
                {!! $content !!}
            </div>
        </div>
    </article>

    @if($related->isNotEmpty())
        <section class="mt-8" aria-labelledby="help-related">
            <h2 id="help-related" class="ea-section-title">
                <i class="fas fa-link" aria-hidden="true"></i>
                Verwandte Themen
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($related as $rel)
                    <a href="{{ url('hilfe/'.$rel['slug']) }}" class="ea-card flex items-center gap-3 p-3">
                        <div class="ea-card-icon"><i class="{{ $rel['icon'] }}" aria-hidden="true"></i></div>
                        <span class="font-medium ea-text min-w-0">{{ $rel['title'] }}</span>
                        <i class="fas fa-arrow-right ea-muted ml-auto" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
        <a href="{{ url('hilfe') }}" class="ea-link">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Zurück zur Übersicht
        </a>
        <a href="{{ url('feedback') }}" class="ea-btn ea-btn-ghost ea-btn-sm">
            <i class="fas fa-comment-dots" aria-hidden="true"></i> Feedback geben
        </a>
    </div>
</div>
@endsection
