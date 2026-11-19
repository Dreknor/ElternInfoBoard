{{-- Karte einer Liste im Bereich „Verwaltung“. Erwartet: $liste, $frist, $belegung --}}
@php
    $istTermin = $liste->type === 'termin';
    $gebucht = $belegung['total'] - $belegung['free'];
    $anteil = $belegung['total'] > 0 ? round($gebucht / $belegung['total'] * 100) : 0;
@endphp
<article class="rounded-lg flex flex-col shadow-sm"
         style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
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
                    @if($liste->active)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <i class="fas fa-eye" aria-hidden="true"></i> veröffentlicht
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            <i class="fas fa-eye-slash" aria-hidden="true"></i> inaktiv
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
            @include('listen.partials.listen_menu')
        </div>

        <div>
            <div class="flex justify-between text-sm mb-1">
                <span style="color: var(--color-text-secondary);">{{ $istTermin ? 'Gebuchte Termine' : 'Vergebene Einträge' }}</span>
                <span class="font-semibold" style="color: var(--color-text-primary);">{{ $gebucht }} / {{ $belegung['total'] }}</span>
            </div>
            <div class="h-2 rounded-full overflow-hidden" style="background: var(--color-surface-subtle); border: 1px solid var(--color-card-border);"
                 role="progressbar" aria-valuenow="{{ $anteil }}" aria-valuemin="0" aria-valuemax="100"
                 aria-label="{{ $anteil }} Prozent belegt">
                <div class="h-full bg-emerald-500" style="width: {{ $anteil }}%;"></div>
            </div>
        </div>

        <p class="text-sm mb-0 {{ $frist['urgent'] ? 'text-amber-700 dark:text-amber-400' : '' }}"
           @unless($frist['urgent']) style="color: var(--color-text-secondary);" @endunless>
            <i class="far fa-clock mr-1" aria-hidden="true"></i>{{ $frist['text'] }}
            @if($liste->ende && ! str_starts_with($frist['text'], 'bis')) <span class="opacity-75">({{ $liste->ende->format('d.m.Y') }})</span> @endif
        </p>
    </div>
    <div class="px-4 pb-4">
        <a href="{{ url('listen/'.$liste->id) }}"
           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg border hover:opacity-90"
           style="border-color: var(--color-card-border); color: var(--color-text-primary);">
            <i class="fas fa-sliders-h" aria-hidden="true"></i> Verwalten
        </a>
    </div>
</article>
