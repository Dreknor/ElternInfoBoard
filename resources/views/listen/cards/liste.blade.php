{{-- Karte einer Liste für Empfänger (Eltern). Erwartet: $liste, $frist, $belegung, $gebucht, $kannWeitereBuchen --}}
@php
    $istTermin = $liste->type === 'termin';
    $ohneVorgaben = ! $istTermin && $belegung['total'] === 0 && $liste->make_new_entry;
@endphp
<article class="rounded-lg flex flex-col overflow-hidden shadow-sm hover:shadow-md transition-shadow"
         style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);"
         x-data="{ showInfo: false }">
    <div class="px-4 pt-4 pb-3 flex-1 flex flex-col gap-3">
        <div class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg"
                  style="background: {{ $istTermin ? 'var(--color-badge-termin-bg)' : 'var(--color-badge-eintrag-bg)' }}; color: {{ $istTermin ? 'var(--color-badge-termin-text)' : 'var(--color-badge-eintrag-text)' }};"
                  title="{{ $istTermin ? 'Terminliste' : 'Eintragsliste' }}">
                <i class="fas {{ $istTermin ? 'fa-calendar-check' : 'fa-list-ul' }}" aria-hidden="true"></i>
                <span class="sr-only">{{ $istTermin ? 'Terminliste' : 'Eintragsliste' }}</span>
            </span>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold mb-1 break-words" style="color: var(--color-text-primary);">{{ $liste->listenname }}</h3>
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    @if($gebucht)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <i class="fas fa-check" aria-hidden="true"></i> gebucht
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">
                            <i class="fas fa-circle text-[0.5rem]" aria-hidden="true"></i> offen
                        </span>
                    @endif
                    @if($liste->creates_pflichtstunden)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold"
                              style="background: var(--color-badge-termin-bg); color: var(--color-badge-termin-text);">
                            <i class="fas fa-clock" aria-hidden="true"></i> Pflichtstunden
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-2 text-sm mb-0">
            <div>
                <dt class="text-xs" style="color: var(--color-text-secondary);">Frist</dt>
                <dd class="font-semibold mb-0 {{ $frist['urgent'] ? 'text-amber-700 dark:text-amber-400' : '' }}"
                    @unless($frist['urgent']) style="color: var(--color-text-primary);" @endunless
                    title="{{ $liste->ende?->format('d.m.Y') }}">
                    <i class="far fa-clock mr-1" aria-hidden="true"></i>{{ $frist['text'] }}
                </dd>
            </div>
            <div>
                <dt class="text-xs" style="color: var(--color-text-secondary);">{{ $istTermin ? 'Termine' : 'Einträge' }}</dt>
                <dd class="font-semibold mb-0" style="color: var(--color-text-primary);">
                    @if($ohneVorgaben)
                        freie Eintragung
                    @else
                        {{ $belegung['free'] }} von {{ $belegung['total'] }} frei
                    @endif
                </dd>
            </div>
        </dl>

        @if($liste->comment)
            <div>
                <button type="button" @click="showInfo = !showInfo" :aria-expanded="showInfo.toString()"
                        class="inline-flex items-center gap-1 text-sm font-medium" style="color: var(--color-primary);">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <span x-text="showInfo ? 'Info ausblenden' : 'Info'">Info</span>
                </button>
                <div x-show="showInfo" x-transition x-cloak class="mt-2 text-sm leading-relaxed" style="color: var(--color-text-secondary);">
                    @safeHtml($liste->comment)
                </div>
            </div>
        @endif
    </div>

    <div class="px-4 pb-4">
        <a href="{{ url('listen/'.$liste->id) }}"
           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 font-semibold rounded-lg transition-opacity hover:opacity-90 {{ $kannWeitereBuchen ? '' : 'border' }}"
           style="{{ $kannWeitereBuchen
                ? 'background: var(--color-card-a-btn-bg); color: var(--color-card-a-btn-text);'
                : 'border-color: var(--color-card-border); color: var(--color-text-primary);' }}">
            @if(! $kannWeitereBuchen)
                <i class="fas fa-eye" aria-hidden="true"></i> Ansehen
            @elseif($istTermin)
                <i class="fas fa-calendar-plus" aria-hidden="true"></i> {{ $gebucht ? 'Weiteren Termin wählen' : 'Termin wählen' }}
            @else
                <i class="fas fa-pen" aria-hidden="true"></i> {{ $gebucht ? 'Weiter eintragen' : 'Eintragen' }}
            @endif
        </a>
    </div>
</article>
