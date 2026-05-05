@extends('layouts.app')

@section('title') - Hilfe & Anleitung @endsection

@section('content')
<div class="container-fluid py-4">
    <div class="max-w-6xl mx-auto px-4">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl shadow-lg p-6 md:p-8 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-circle-question text-4xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold mb-1">Hilfe &amp; Anleitung</h1>
                    <p class="text-blue-100 mb-0">
                        Hier findest du Antworten auf häufige Fragen und Schritt-für-Schritt-Anleitungen –
                        passend zu deinen Berechtigungen.
                    </p>
                </div>
            </div>
        </div>

        @if($grouped->isEmpty())
            <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-6 text-center">
                <i class="fas fa-triangle-exclamation text-yellow-600 text-3xl mb-2"></i>
                <p class="text-gray-700 mb-0">Aktuell sind für dich keine Hilfe-Themen verfügbar.</p>
            </div>
        @endif

        @foreach($grouped as $groupKey => $topics)
            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-800 mb-3 flex items-center gap-2 border-b-2 border-gray-200 pb-2">
                    <span class="w-1 h-6 bg-blue-600 rounded"></span>
                    {{ $help->groupLabel($groupKey) }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($topics as $topic)
                        <a href="{{ url('hilfe/'.$topic['slug']) }}"
                           class="block bg-white border-2 border-gray-200 hover:border-blue-400 hover:shadow-lg rounded-xl p-5 transition-all duration-200 group">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 rounded-lg bg-blue-100 group-hover:bg-blue-600 flex items-center justify-center flex-shrink-0 transition-colors">
                                    <i class="{{ $topic['icon'] }} text-xl text-blue-600 group-hover:text-white transition-colors"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-900 mb-1 leading-tight">{{ $topic['title'] }}</h3>
                                    <p class="text-sm text-gray-600 mb-0 leading-snug">{{ $topic['excerpt'] }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 text-center mt-8">
            <p class="text-sm text-gray-600 mb-2">
                <i class="fas fa-circle-info text-blue-600"></i>
                Du findest deine Frage hier nicht?
            </p>
            <a href="{{ url('feedback') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <i class="fas fa-comment-dots"></i>
                Feedback / Anfrage senden
            </a>
        </div>
    </div>
</div>
@endsection
