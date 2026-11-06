@extends('layouts.student')
@section('title', '- Links')
@section('content')
    <h1 class="mb-1 text-2xl font-bold">Links</h1>
    <p class="mb-4 text-sm student-muted">Wichtige Seiten und Adressen von deiner Schule.</p>

    @forelse($categories as $category => $items)
        <section class="mb-5" @if($category !== '') aria-labelledby="links-cat-{{ $loop->index }}" @endif>
            @if($category !== '')
                <h2 id="links-cat-{{ $loop->index }}" class="mb-2 text-lg font-semibold">{{ $category }}</h2>
            @endif
            <ul class="student-card divide-y rounded-xl px-4" style="border-color: var(--color-card-border);">
                @foreach($items as $link)
                    @include('student.partials.link-row', ['link' => $link])
                @endforeach
            </ul>
        </section>
    @empty
        <div class="student-card rounded-xl p-4 text-sm student-muted">Zurzeit gibt es keine Links für dich.</div>
    @endforelse
@endsection
