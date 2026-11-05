<li class="flex items-start gap-3 py-3">
    <div class="w-14 shrink-0 rounded-lg py-1 text-center text-white" style="background: var(--color-primary);">
        <div class="text-lg font-bold leading-tight">{{ $termin->start->format('d') }}</div>
        <div class="text-[11px] uppercase">{{ $termin->start->locale('de')->isoFormat('MMM') }}</div>
    </div>
    <div class="min-w-0">
        <div class="break-words font-medium">{{ $termin->terminname }}</div>
        <div class="text-sm student-muted">
            @if($termin->fullDay)
                {{ $termin->start->format('d.m.') }}@if(! $termin->ende->isSameDay($termin->start)) – {{ $termin->ende->format('d.m.Y') }}@endif, ganztägig
            @else
                {{ $termin->start->format('d.m.Y H:i') }} – {{ $termin->ende->isSameDay($termin->start) ? $termin->ende->format('H:i') : $termin->ende->format('d.m.Y H:i') }} Uhr
            @endif
        </div>
    </div>
</li>
