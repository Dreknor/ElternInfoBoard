{{-- Prozessprotokoll als Zeitstrahl. Erwartet: $events --}}
<section class="ea-card">
    <div class="ea-card-header">
        <div class="ea-card-icon"><i class="fas fa-stream" aria-hidden="true"></i></div>
        <h2 class="ea-card-title">Protokoll</h2>
    </div>
    <div class="ea-card-body">
        @if($events->isEmpty())
            <p class="text-sm ea-muted mb-0">–</p>
        @else
            <ol class="ea-timeline mb-0">
                @foreach($events as $event)
                    <li class="ea-timeline-item">
                        <p class="font-medium ea-text mb-0">{{ $event->actionLabel() }}</p>
                        <p class="text-xs ea-muted mb-0">{{ $event->created_at->format('d.m.Y H:i') }} · {{ $event->user?->name ?? 'System' }}</p>
                        @if($event->note)<p class="text-xs italic ea-muted mt-1 mb-0">{{ $event->note }}</p>@endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>
