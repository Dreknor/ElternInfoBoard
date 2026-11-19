{{-- Text eines Eintrags mit Bemerkung, Kind und ggf. Name. Erwartet: $eintrag, $zeigeKind, $zeigeName --}}
<div class="flex-1 min-w-0">
    <p class="font-medium break-words mb-0" style="color: var(--color-text-primary);">{{ $eintrag->eintragung }}</p>
    @if($eintrag->bemerkung)
        <p class="text-sm mb-0 break-words" style="color: var(--color-text-secondary);"><i class="fas fa-comment-dots mr-1" aria-hidden="true"></i>{{ $eintrag->bemerkung }}</p>
    @endif
    @if(($zeigeKind && $eintrag->child) || ($zeigeName && $eintrag->user))
        <p class="text-xs mb-0" style="color: var(--color-text-secondary);">
            @if($zeigeName && $eintrag->user)
                <i class="fas fa-user mr-1" aria-hidden="true"></i>{{ $eintrag->user->name }}
            @endif
            @if($zeigeKind && $eintrag->child)
                <span class="{{ $zeigeName ? 'ml-1' : '' }}"><i class="fas fa-child mr-1" aria-hidden="true"></i>für {{ $eintrag->child->first_name }}</span>
            @endif
        </p>
    @endif
</div>
