{{-- Ein Link in der Schüleransicht (Modul „Links“). Extern: neuer Tab ohne Referrer. --}}
<li>
    <a href="{{ $link->href() }}" @if($link->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif
       class="flex min-h-[48px] items-center gap-3 py-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" style="background: var(--color-primary-soft, rgba(31,111,235,.1)); color: var(--color-primary);">
            <i class="{{ $link->iconClass() }}" aria-hidden="true"></i>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block break-words font-medium">{{ $link->title }}</span>
            @if(($withDescription ?? true) && $link->description)
                <span class="block break-words text-sm student-muted line-clamp-2">{{ $link->description }}</span>
            @endif
            <span class="block truncate text-xs student-muted">{{ $link->displayHost() }}</span>
        </span>
        <i class="fas {{ $link->opensInNewTab() ? 'fa-external-link-alt' : 'fa-chevron-right' }} shrink-0 text-sm student-muted" aria-hidden="true"></i>
        @if($link->opensInNewTab())<span class="sr-only">(öffnet in neuem Tab)</span>@endif
    </a>
</li>
