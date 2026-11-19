{{-- „…“-Menü mit Verwaltungsaktionen einer Liste (Übersicht und Detailseite) --}}
@php $istAdmin = auth()->user()->can('edit terminliste'); @endphp
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
            class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 transition-colors {{ $buttonClass ?? '' }}"
            style="{{ $buttonStyle ?? 'color: var(--color-text-primary);' }}"
            aria-label="Aktionen für {{ $liste->listenname }}" title="Aktionen">
        <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
    </button>
    <div x-show="open" x-transition.origin.top.right x-cloak role="menu"
         class="absolute right-0 mt-1 w-56 max-w-[calc(100vw-2rem)] rounded-lg shadow-xl py-1 z-50 text-left"
         style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
        @php $item = 'flex w-full items-center gap-3 px-4 py-2 text-sm hover:bg-black/5 dark:hover:bg-white/10 transition-colors'; @endphp
        @if($istAdmin)
            <a href="{{ url("listen/$liste->id/edit") }}" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                <i class="fas fa-pencil-alt w-4" aria-hidden="true"></i> Bearbeiten
            </a>
        @endif
        @if($mitVorschau ?? true)
            <a href="{{ route('listen.preview', $liste->id) }}" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                <i class="fas fa-eye w-4" aria-hidden="true"></i> Vorschau
            </a>
        @endif
        @if($liste->active == 0)
            <form action="{{ url("listen/$liste->id/activate") }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                    <i class="fas fa-bullhorn w-4 text-emerald-600" aria-hidden="true"></i> Veröffentlichen
                </button>
            </form>
        @else
            <form action="{{ url("listen/$liste->id/deactivate") }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                    <i class="fas fa-eye-slash w-4 text-amber-600" aria-hidden="true"></i> Ausblenden
                </button>
            </form>
        @endif
        @if($liste->type !== 'termin')
            <form action="{{ route('listen.copy', $liste->id) }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                    <i class="fas fa-copy w-4" aria-hidden="true"></i> Liste kopieren
                </button>
            </form>
        @endif
        @if($istAdmin)
            <div class="my-1" style="border-top: 1px solid var(--color-card-border);"></div>
            <a href="{{ url("listen/$liste->id/archiv") }}" role="menuitem"
               class="{{ $item }} text-red-600 dark:text-red-400"
               onclick="return confirm(@js('Liste „'.$liste->listenname.'“ archivieren?'))">
                <i class="fas fa-archive w-4" aria-hidden="true"></i> Archivieren
            </a>
        @endif
    </div>
</div>
