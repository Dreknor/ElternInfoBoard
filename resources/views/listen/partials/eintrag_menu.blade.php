{{-- „…“-Menü für einen offenen Eintrag (nur Verwaltung) --}}
<div class="relative flex-shrink-0" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
            class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-black/10 dark:hover:bg-white/10"
            style="color: var(--color-text-secondary);" aria-label="Aktionen für „{{ $eintrag->eintragung }}“">
        <i class="fas fa-ellipsis-v text-sm" aria-hidden="true"></i>
    </button>
    <div x-show="open" x-transition x-cloak role="menu"
         class="absolute right-0 mt-1 w-44 rounded-lg shadow-xl py-1 z-40"
         style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
        @php $item = 'flex w-full items-center gap-3 px-3 py-2 text-sm text-left hover:bg-black/5 dark:hover:bg-white/10'; @endphp
        <form method="post" action="{{ route('listen.eintragungen.copy', $eintrag->id) }}" class="mb-0">
            @csrf
            <button type="submit" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                <i class="fas fa-copy w-4" aria-hidden="true"></i> Kopieren
            </button>
        </form>
        <form method="post" action="{{ url('listen/eintragungen/'.$eintrag->id) }}" class="mb-0"
              onsubmit="return confirm(@js('Eintrag „'.$eintrag->eintragung.'“ löschen?'))">
            @csrf
            @method('DELETE')
            <button type="submit" role="menuitem" class="{{ $item }} text-red-600 dark:text-red-400">
                <i class="fas fa-trash-alt w-4" aria-hidden="true"></i> Löschen
            </button>
        </form>
    </div>
</div>
