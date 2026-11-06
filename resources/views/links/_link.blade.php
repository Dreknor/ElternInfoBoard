{{-- Ein Link als Kachel bzw. mit $row = true als Listenzeile (Handy). Erwartet $link (App\Model\Link). --}}
<a href="{{ $link->href() }}"
   @if($link->opensInNewTab()) target="_blank" rel="noopener noreferrer" @endif
   class="{{ ($row ?? false) ? 'ea-row-link' : 'ea-card h-full' }} flex items-start gap-3 p-3 sm:p-4 focus:outline-none focus:ring-2 focus:ring-inset"
   style="text-decoration: none">
    <span class="ea-card-icon flex-shrink-0"><i class="{{ $link->iconClass() }}" aria-hidden="true"></i></span>
    <span class="min-w-0 flex-1">
        <span class="block font-semibold ea-text break-words">{{ $link->title }}</span>
        @if($link->description)
            <span class="block text-sm ea-muted mt-0.5 break-words line-clamp-2">{{ $link->description }}</span>
        @endif
        <span class="block text-xs ea-muted mt-1 truncate">
            @if($link->opensInNewTab())
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span class="sr-only">Öffnet in neuem Fenster:</span>
            @endif
            {{ $link->displayHost() }}
        </span>
    </span>
</a>
