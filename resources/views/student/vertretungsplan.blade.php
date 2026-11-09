@extends('layouts.student')
@section('title', '- Vertretungsplan')
@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-bold">Vertretungsplan</h1>
        @if($klasse)<span class="rounded-full border px-3 py-0.5 text-sm" style="border-color: var(--color-card-border);">{{ $klasse }}</span>@endif
        @if($week)<span class="rounded-full border px-3 py-0.5 text-sm" style="border-color: var(--color-card-border);">{{ $week->type }}-Woche</span>@endif
    </div>

    @foreach($news as $item)
        <div class="mb-3 rounded-lg border-l-4 border-amber-400 bg-amber-50 p-3 text-sm text-amber-900">
            <i class="fas fa-bullhorn mr-1"></i>{{ $item->news }}
        </div>
    @endforeach

    @if($absences->isNotEmpty())
        <section class="student-card mb-4 rounded-xl p-4">
            <h2 class="mb-2 font-semibold"><i class="fas fa-user-times mr-2"></i>Abwesende Lehrkräfte</h2>
            <div class="flex flex-wrap gap-2 text-sm">
                @foreach($absences as $absence)
                    <span class="rounded-full border px-3 py-1" style="border-color: var(--color-card-border);">{{ $absence->name }} <span class="student-muted">{{ $absence->start_date?->format('d.m.') }}–{{ $absence->end_date?->format('d.m.') }}</span></span>
                @endforeach
            </div>
        </section>
    @endif

    @forelse($vertretungen as $datum => $eintraege)
        <section class="student-card mb-4 rounded-xl p-4">
            <h2 class="font-semibold">{{ \Carbon\Carbon::parse($datum)->locale('de')->isoFormat('dddd, D. MMMM') }}</h2>
            <ul class="divide-y" style="border-color: var(--color-card-border);">
                @foreach($eintraege as $v)
                    @include('student.partials.vertretung-row', ['v' => $v])
                @endforeach
            </ul>
        </section>
    @empty
        <div class="student-card rounded-xl p-4 text-sm student-muted">Für deine Klasse gibt es keine Vertretungen.</div>
    @endforelse
@endsection
