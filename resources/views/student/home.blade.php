@extends('layouts.student')
@section('title', '- Start')
@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold">Hallo {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name }}!</h1>
        @if($klasse)<p class="student-muted">{{ \Illuminate\Support\Str::startsWith($klasse, 'Klasse') ? $klasse : 'Klasse '.$klasse }}</p>@endif
    </div>

    @if(! $child)
        <div class="student-card mb-5 rounded-xl p-4 text-sm">
            Dein Zugang ist noch keiner Klasse zugeordnet. Bitte wende dich an das Sekretariat.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="lg:col-span-2" aria-labelledby="h-posts">
            <div class="mb-3 flex items-center justify-between">
                <h2 id="h-posts" class="text-lg font-semibold"><i class="fas fa-newspaper mr-2"></i>Neueste Nachrichten</h2>
                <a href="{{ route('student.posts') }}" class="text-sm" style="color: var(--color-primary);">Alle</a>
            </div>
            <div class="space-y-4">
                @forelse($posts as $post)
                    @include('student.partials.post-card', ['post' => $post])
                @empty
                    <div class="student-card rounded-xl p-4 text-sm student-muted">Zurzeit gibt es keine Nachrichten für dich.</div>
                @endforelse
            </div>
        </section>

        <div class="space-y-5">
            <section class="student-card rounded-xl p-4" aria-labelledby="h-vertretung">
                <div class="flex items-center justify-between">
                    <h2 id="h-vertretung" class="font-semibold"><i class="fas fa-exchange-alt mr-2"></i>Vertretungen heute und morgen</h2>
                </div>
                @if($vertretungen->isEmpty())
                    <p class="mt-2 text-sm student-muted">Keine Änderungen.</p>
                @else
                    <ul class="divide-y" style="border-color: var(--color-card-border);">
                        @foreach($vertretungen as $v)
                            @include('student.partials.vertretung-row', ['v' => $v, 'showDate' => true])
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('student.vertretungsplan') }}" class="mt-2 inline-block text-sm" style="color: var(--color-primary);">Zum Vertretungsplan</a>
            </section>

            <section class="student-card rounded-xl p-4" aria-labelledby="h-termine">
                <h2 id="h-termine" class="font-semibold"><i class="fas fa-calendar-alt mr-2"></i>Nächste Termine</h2>
                @if($termine->isEmpty())
                    <p class="mt-2 text-sm student-muted">Keine Termine.</p>
                @else
                    <ul class="divide-y" style="border-color: var(--color-card-border);">
                        @foreach($termine as $termin)
                            @include('student.partials.termin-row', ['termin' => $termin])
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('student.termine') }}" class="mt-2 inline-block text-sm" style="color: var(--color-primary);">Alle Termine</a>
            </section>

            <a href="{{ route('student.ags') }}" class="student-card flex items-center justify-between rounded-xl p-4 md:hidden">
                <span class="font-semibold"><i class="fas fa-users mr-2"></i>AGs</span><i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
@endsection
