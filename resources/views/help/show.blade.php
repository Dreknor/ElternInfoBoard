@extends('layouts.app')

@section('title') - {{ $topic['title'] }} @endsection

@push('css')
<style>
    .help-prose h1 { @apply text-2xl font-bold text-gray-900 mt-6 mb-3; }
    .help-prose h2 { @apply text-xl font-bold text-gray-900 mt-6 mb-2 pb-1 border-b border-gray-200; }
    .help-prose h3 { @apply text-lg font-semibold text-gray-800 mt-4 mb-2; }
    .help-prose p  { @apply text-gray-700 leading-relaxed mb-3; }
    .help-prose ul { @apply list-disc pl-6 mb-3 space-y-1 text-gray-700; }
    .help-prose ol { @apply list-decimal pl-6 mb-3 space-y-1 text-gray-700; }
    .help-prose a  { @apply text-blue-600 hover:text-blue-800 underline; }
    .help-prose code { @apply bg-gray-100 text-pink-700 px-1.5 py-0.5 rounded text-sm; }
    .help-prose pre { @apply bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto mb-3; }
    .help-prose pre code { @apply bg-transparent text-gray-100 p-0; }
    .help-prose blockquote { @apply border-l-4 border-blue-500 bg-blue-50 pl-4 py-2 my-3 text-gray-700 italic; }
    .help-prose img { @apply rounded-lg shadow my-3 max-w-full h-auto; }
    .help-prose table { @apply min-w-full border border-gray-200 my-3; }
    .help-prose th, .help-prose td { @apply border border-gray-200 px-3 py-2 text-sm; }
    .help-prose th { @apply bg-gray-50 font-semibold; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="max-w-4xl mx-auto px-4">

        {{-- Breadcrumb --}}
        <nav class="text-sm mb-4">
            <ol class="flex items-center gap-2 text-gray-600">
                <li><a href="{{ url('hilfe') }}" class="hover:text-blue-600"><i class="fas fa-circle-question"></i> Hilfe</a></li>
                <li><i class="fas fa-chevron-right text-xs text-gray-400"></i></li>
                <li class="text-gray-800 font-medium truncate">{{ $topic['title'] }}</li>
            </ol>
        </nav>

        <article class="bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Topic Header --}}
            <header class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <i class="{{ $topic['icon'] }} text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold mb-1">{{ $topic['title'] }}</h1>
                        @if(!empty($topic['excerpt']))
                            <p class="text-blue-100 mb-0">{{ $topic['excerpt'] }}</p>
                        @endif
                    </div>
                </div>
            </header>

            {{-- Inhalt --}}
            <div class="p-6 md:p-8">
                <div class="help-prose">
                    {!! $content !!}
                </div>
            </div>
        </article>

        @if($related->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-link text-blue-600"></i>
                    Verwandte Themen
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($related as $rel)
                        <a href="{{ url('hilfe/'.$rel['slug']) }}"
                           class="flex items-center gap-3 bg-white border-2 border-gray-200 hover:border-blue-400 rounded-lg p-3 transition-all">
                            <i class="{{ $rel['icon'] }} text-blue-600 text-lg"></i>
                            <span class="font-medium text-gray-800">{{ $rel['title'] }}</span>
                            <i class="fas fa-arrow-right text-gray-400 ml-auto"></i>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-6 flex items-center justify-between text-sm">
            <a href="{{ url('hilfe') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
            </a>
            <a href="{{ url('feedback') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-comment-dots"></i> Feedback geben
            </a>
        </div>
    </div>
</div>
@endsection
