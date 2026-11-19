{{-- Kachel für einen einzelnen Termin. Erwartet: $termin, $liste, $eigeneTermine, $gesperrt, $canManage, $preview --}}
@php
    $vergangen = $termin->termin->lessThan(now());
    $frei = $termin->reserviert_fuer === null;
    $eigen = $eigeneTermine->contains('id', $termin->id);
    $buchbar = $frei && ! $vergangen && ! $gesperrt;
    $namenSichtbar = ! $frei && app(\App\Services\App\ListenAnsicht::class)->namesVisible(auth()->user(), $liste, $preview, $eigen);
    $zeit = $termin->termin->format('H:i').'–'.$termin->ende->format('H:i');
    if ($termin->ende->toDateString() !== $termin->termin->toDateString()) {
        $zeit .= ' ('.$termin->ende->format('d.m.').')';
    }

    if ($eigen) {
        $status = 'Ihr Termin';
    } elseif ($frei) {
        $status = $vergangen ? 'nicht gebucht' : 'frei';
    } elseif ($namenSichtbar) {
        $status = $termin->eingetragenePerson?->name ?? 'vergeben';
    } else {
        $status = 'vergeben';
    }
    $kind = ! $frei && $termin->child && ($namenSichtbar || $eigen) ? $termin->child->first_name : null;

    $kachel = match (true) {
        $eigen => 'border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30',
        $buchbar => 'border-2 border-sky-500 hover:bg-sky-50 dark:hover:bg-sky-900/30 cursor-pointer',
        $frei && ! $vergangen => 'border-2 border-dashed border-sky-300 dark:border-sky-700',
        default => 'border',
    };
    $label = $termin->termin->locale('de')->isoFormat('dd, DD.MM.').' '.$zeit.' Uhr';
@endphp
<div class="relative rounded-lg {{ $kachel }} {{ $vergangen ? 'opacity-60' : '' }}"
     @if($vergangen) x-show="showPast" x-cloak
     @elseif(! $eigen && ! $frei) x-show="!onlyFree" @endif
     @if(! $frei && ! $eigen) style="background: var(--color-surface-subtle); border-color: var(--color-card-border);" @endif>
    @if($buchbar)
        <button type="button"
                @click="buchen = { id: {{ $termin->id }}, label: @js($label), kommentar: @js($termin->comment ?? '') }"
                class="w-full h-full text-left p-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                aria-label="{{ $zeit }} Uhr, frei – Termin reservieren{{ $termin->comment ? ', '.$termin->comment : '' }}">
    @else
        <div class="p-3" aria-label="{{ $zeit }} Uhr, {{ $status }}{{ $kind ? ', für '.$kind : '' }}">
    @endif
            <span class="block font-bold tabular-nums {{ $canManage ? 'pr-7' : '' }}" style="color: var(--color-text-primary);">{{ $zeit }}</span>
            <span class="flex items-center gap-1 text-sm mt-0.5 min-w-0
                {{ $eigen ? 'text-emerald-800 dark:text-emerald-300 font-semibold' : '' }}
                {{ $buchbar ? 'text-sky-700 dark:text-sky-300 font-semibold' : '' }}"
                  @if(! $eigen && ! $buchbar) style="color: var(--color-text-secondary);" @endif>
                <i class="fas {{ $eigen ? 'fa-check-circle' : ($frei ? 'fa-plus-circle' : ($namenSichtbar ? 'fa-user' : 'fa-lock')) }} flex-shrink-0" aria-hidden="true"></i>
                <span class="truncate">{{ $status }}</span>
            </span>
            @if($kind)
                <span class="block text-xs truncate" style="color: var(--color-text-secondary);">für {{ $kind }}</span>
            @endif
            @if($termin->comment)
                <span class="block text-xs mt-1 break-words" style="color: var(--color-text-secondary);">{{ $termin->comment }}</span>
            @endif
    @if($buchbar)
        </button>
    @else
        </div>
    @endif

    @if($canManage)
        <div class="absolute top-1 right-1" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click.stop="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
                    class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-black/10 dark:hover:bg-white/10"
                    style="color: var(--color-text-secondary);" aria-label="Aktionen für Termin {{ $label }}">
                <i class="fas fa-ellipsis-v text-xs" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-transition x-cloak role="menu"
                 class="absolute right-0 mt-1 w-48 rounded-lg shadow-xl py-1 z-40"
                 style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
                @php $item = 'flex w-full items-center gap-3 px-3 py-2 text-sm text-left hover:bg-black/5 dark:hover:bg-white/10'; @endphp
                <a href="{{ url('listen/termine/'.$termin->id.'/copy') }}" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                    <i class="fas fa-copy w-4" aria-hidden="true"></i> Kopieren
                </a>
                @if($frei)
                    <form method="post" action="{{ url('listen/termine/'.$termin->id) }}" class="mb-0"
                          onsubmit="return confirm(@js('Termin '.$label.' löschen?'))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" role="menuitem" class="{{ $item }} text-red-600 dark:text-red-400">
                            <i class="fas fa-trash-alt w-4" aria-hidden="true"></i> Termin löschen
                        </button>
                    </form>
                @else
                    <button type="button" role="menuitem" class="{{ $item }} text-red-600 dark:text-red-400"
                            @click="open = false; absage = { id: {{ $termin->id }}, label: @js($label.($termin->eingetragenePerson ? ' ('.$termin->eingetragenePerson->name.')' : '')), eltern: false }">
                        <i class="fas fa-times w-4" aria-hidden="true"></i> Absagen …
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
