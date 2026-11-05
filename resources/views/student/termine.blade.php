@extends('layouts.student')
@section('title', '- Termine')
@section('content')
    <h1 class="mb-4 text-2xl font-bold">Termine</h1>
    @forelse($termine as $monat => $eintraege)
        <section class="student-card mb-4 rounded-xl p-4">
            <h2 class="font-semibold">{{ $monat }}</h2>
            <ul class="divide-y" style="border-color: var(--color-card-border);">
                @foreach($eintraege as $termin)
                    @include('student.partials.termin-row', ['termin' => $termin])
                @endforeach
            </ul>
        </section>
    @empty
        <div class="student-card rounded-xl p-4 text-sm student-muted">Es stehen keine Termine an.</div>
    @endforelse
@endsection
