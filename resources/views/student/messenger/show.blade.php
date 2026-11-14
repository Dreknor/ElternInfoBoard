@extends('layouts.student')
@section('title', '- Chat')
@section('content')
    <div class="mb-3 flex items-center gap-3">
        <a href="{{ route('student.messenger') }}" class="rounded-lg px-2 py-1 hover:bg-black/5" aria-label="Zurück"><i class="fas fa-arrow-left"></i></a>
        <h1 class="min-w-0 truncate text-xl font-bold">{{ $teacher?->name ?? 'Lehrkraft' }}</h1>
    </div>

    <div class="student-card flex flex-col rounded-xl" style="min-height: 50vh;">
        <div class="flex-1 space-y-3 overflow-y-auto p-4" id="chatMessages">
            @forelse($messages as $m)
                @php $mine = (int) $m->sender_id === auth()->id(); @endphp
                <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-2 sm:max-w-[70%] {{ $mine ? 'text-white' : '' }}"
                         style="{{ $mine ? 'background: var(--color-primary);' : 'background: var(--color-widget-primary-bg, #eef2f7);' }}">
                        @if($m->trashed())
                            <p class="text-sm italic opacity-75">Nachricht gelöscht</p>
                        @else
                            <p class="whitespace-pre-line break-words text-sm">{{ $m->body }}</p>
                        @endif
                        <p class="mt-1 text-right text-[11px] opacity-75">{{ $m->created_at?->format('d.m. H:i') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-center text-sm student-muted">Schreib die erste Nachricht.</p>
            @endforelse
        </div>

        @if($conversation->is_active)
            <form method="POST" action="{{ route('student.messenger.send', $conversation->id) }}" class="flex gap-2 border-t p-3" style="border-color: var(--color-card-border);">
                @csrf
                <label for="body" class="sr-only">Nachricht</label>
                <textarea id="body" name="body" rows="2" maxlength="{{ \App\Services\Students\StudentMessenger::MAX_LENGTH }}" required
                          class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm" style="border-color: var(--color-card-border);" placeholder="Nachricht schreiben …">{{ old('body') }}</textarea>
                <button type="submit" class="shrink-0 rounded-lg px-4 py-2 font-semibold text-white" style="background: var(--color-primary);" aria-label="Senden"><i class="fas fa-paper-plane"></i></button>
            </form>
        @else
            <p class="border-t p-3 text-center text-sm student-muted" style="border-color: var(--color-card-border);">Die Lehrkraft hat diesen Chat beendet.</p>
        @endif
    </div>
    @push('js')
        <script>document.getElementById('chatMessages')?.scrollTo(0, 1e9);</script>
    @endpush
@endsection
