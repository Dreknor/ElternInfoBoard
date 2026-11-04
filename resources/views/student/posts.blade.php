@extends('layouts.student')
@section('title', '- Nachrichten')
@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ $archiv ? 'Archiv' : 'Nachrichten' }}</h1>
        <div class="flex rounded-lg border text-sm" style="border-color: var(--color-card-border);">
            <a href="{{ route('student.posts') }}" class="rounded-l-lg px-3 py-1.5 {{ $archiv ? '' : 'font-semibold text-white' }}" @unless($archiv) style="background: var(--color-primary);" @endunless>Aktuell</a>
            <a href="{{ route('student.posts', ['archiv' => 1]) }}" class="rounded-r-lg px-3 py-1.5 {{ $archiv ? 'font-semibold text-white' : '' }}" @if($archiv) style="background: var(--color-primary);" @endif>Archiv</a>
        </div>
    </div>
    <div class="space-y-4">
        @forelse($posts as $post)
            @include('student.partials.post-card', ['post' => $post])
        @empty
            <div class="student-card rounded-xl p-4 text-sm student-muted">{{ $archiv ? 'Im Archiv ist nichts.' : 'Zurzeit gibt es keine Nachrichten für dich.' }}</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $posts->links() }}</div>
@endsection
