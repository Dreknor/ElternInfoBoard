@extends('layouts.student')
@section('title', '- Chat')
@section('content')
    <h1 class="mb-1 text-2xl font-bold">Chat mit Lehrkräften</h1>
    <p class="mb-4 text-sm student-muted">Du kannst Lehrkräften deiner Klasse und deiner Gruppen schreiben.</p>

    <div class="grid gap-4 md:grid-cols-3">
        <section class="md:col-span-2">
            <div class="space-y-3">
                @forelse($conversations as $c)
                    <a href="{{ route('student.messenger.show', $c->id) }}" class="student-card flex items-center gap-3 rounded-xl p-4 hover:shadow">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: var(--color-widget-primary-bg, #e7f1fb); color: var(--color-primary);"><i class="fas fa-chalkboard-teacher"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold">{{ $c->teacher?->name ?? 'Lehrkraft' }}</span>
                            <span class="block truncate text-sm student-muted">
                                @if(! $c->is_active)
                                    Chat beendet
                                @elseif($c->latestMessage)
                                    {{ \Illuminate\Support\Str::limit($c->latestMessage->body, 80) }}
                                @else
                                    Noch keine Nachricht
                                @endif
                            </span>
                        </span>
                        @if($c->unread_count > 0)
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: var(--color-primary);">{{ $c->unread_count }}</span>
                        @endif
                    </a>
                @empty
                    <div class="student-card rounded-xl p-4 text-sm student-muted">Du hast noch keinen Chat.</div>
                @endforelse
            </div>
        </section>

        <aside class="student-card h-fit rounded-xl p-4">
            <h2 class="mb-2 font-semibold"><i class="fas fa-plus-circle"></i> Neuer Chat</h2>
            @if($teachers->isEmpty())
                <p class="text-sm student-muted">Zurzeit kannst du keiner Lehrkraft schreiben.</p>
            @else
                <form method="POST" action="{{ route('student.messenger.start') }}" class="space-y-3">
                    @csrf
                    <label for="teacher_id" class="block text-sm student-muted">Lehrkraft</label>
                    <select id="teacher_id" name="teacher_id" class="w-full rounded-lg border px-3 py-2" style="border-color: var(--color-card-border);" required>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full rounded-lg px-4 py-2 font-semibold text-white" style="background: var(--color-primary);">Chat öffnen</button>
                </form>
            @endif
        </aside>
    </div>
@endsection
