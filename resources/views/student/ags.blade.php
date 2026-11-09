@extends('layouts.student')
@section('title', '- AGs')
@php $tage = [0 => 'Sonntag', 1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag']; @endphp
@section('content')
    <h1 class="mb-1 text-2xl font-bold">AGs</h1>
    <p class="mb-4 text-sm student-muted">Anmelden können dich deine Eltern.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse($ags as $ag)
            <article class="student-card rounded-xl p-4">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="break-words font-semibold">{{ $ag->name }}</h2>
                    @if(in_array($ag->id, $enrolled, true))<span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">angemeldet</span>@endif
                </div>
                <div class="mt-1 text-sm student-muted">
                    {{ $tage[(int) $ag->weekday] ?? '' }}, {{ $ag->start_time?->format('H:i') }}–{{ $ag->end_time?->format('H:i') }} Uhr
                    @if($ag->manager) · {{ $ag->manager->name }}@endif
                </div>
                @if($ag->description)<p class="mt-2 break-words text-sm">{{ $ag->description }}</p>@endif
            </article>
        @empty
            <div class="student-card rounded-xl p-4 text-sm student-muted sm:col-span-2">Für deine Klasse gibt es zurzeit keine AGs.</div>
        @endforelse
    </div>
@endsection
