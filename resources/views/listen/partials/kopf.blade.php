{{-- Kopf der Detailseiten (Termin- und Eintragslisten). Erwartet: $liste, $preview, $canManage, $frist, $zaehler (Text) --}}
@php $istTermin = $liste->type === 'termin'; @endphp
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ url('listen') }}"
       class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg hover:opacity-80"
       style="color: var(--color-text-primary); border: 1px solid var(--color-card-border); background: var(--color-card-bg);">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        Zurück zur Übersicht
    </a>
</div>

@if($preview)
    @include('listen.partials.preview_banner')
@endif

<header class="rounded-lg p-4 sm:p-6 shadow-sm" style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
    <div class="flex items-start gap-3">
        <span class="hidden sm:inline-flex flex-shrink-0 items-center justify-center w-12 h-12 rounded-lg text-xl"
              style="background: {{ $istTermin ? 'var(--color-badge-termin-bg)' : 'var(--color-badge-eintrag-bg)' }}; color: {{ $istTermin ? 'var(--color-badge-termin-text)' : 'var(--color-badge-eintrag-text)' }};">
            <i class="fas {{ $istTermin ? 'fa-calendar-check' : 'fa-list-ul' }}" aria-hidden="true"></i>
        </span>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold mb-2 break-words" style="color: var(--color-text-primary);">{{ $liste->listenname }}</h1>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @if($liste->active == 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        <i class="fas fa-eye-slash" aria-hidden="true"></i> inaktiv
                    </span>
                @endif
                @if($liste->creates_pflichtstunden)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                          style="background: var(--color-badge-termin-bg); color: var(--color-badge-termin-text);"
                          title="Für diese Liste werden automatisch Pflichtstunden-Einträge erstellt">
                        <i class="fas fa-clock" aria-hidden="true"></i> Pflichtstunden
                    </span>
                @endif
                <span class="{{ $frist['urgent'] ? 'text-amber-700 dark:text-amber-400 font-semibold' : '' }}"
                      @unless($frist['urgent']) style="color: var(--color-text-secondary);" @endunless>
                    <i class="far fa-clock mr-1" aria-hidden="true"></i>{{ $frist['text'] }}
                </span>
                @if($zaehler)
                    <span style="color: var(--color-text-secondary);" aria-hidden="true">·</span>
                    <span class="font-semibold" style="color: var(--color-text-primary);">{{ $zaehler }}</span>
                @endif
            </div>
        </div>
        @if($canManage)
            @include('listen.partials.listen_menu', ['mitVorschau' => false])
        @endif
    </div>

    @if($liste->comment)
        <div class="mt-4 text-sm leading-relaxed prose-sm max-w-none" style="color: var(--color-text-secondary);">
            @safeHtml($liste->comment)
        </div>
    @endif

    @if($canManage)
        <div class="mt-4 pt-4 flex flex-wrap items-center gap-2" style="border-top: 1px solid var(--color-card-border);">
            <span class="text-xs font-semibold uppercase tracking-wide mr-1" style="color: var(--color-text-secondary);">Verwaltung</span>
            @isset($werkzeuge)
                @include($werkzeuge)
            @endisset
            <a href="{{ route('listen.preview', $liste->id) }}"
               class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg"
               style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                <i class="fas fa-eye" aria-hidden="true"></i> Vorschau
            </a>
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg"
                        style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                    <i class="fas fa-file-export" aria-hidden="true"></i> Export
                    <i class="fas fa-chevron-down text-xs" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-transition x-cloak role="menu"
                     class="absolute left-0 mt-1 w-64 max-w-[calc(100vw-2rem)] rounded-lg shadow-xl py-1 z-50"
                     style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
                    @php $item = 'flex w-full items-center gap-3 px-4 py-2 text-sm text-left hover:bg-black/5 dark:hover:bg-white/10'; @endphp
                    <a href="{{ url('listen/'.$liste->id.'/export') }}" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                        <i class="fas fa-print w-4" aria-hidden="true"></i> Druckansicht
                    </a>
                    @if(! $istTermin)
                        <button type="button" onclick="generatePDF()" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                            <i class="fas fa-file-pdf w-4" aria-hidden="true"></i> PDF herunterladen
                        </button>
                    @endif
                    @can('edit terminliste')
                        <a href="{{ route('listen.export-excel.termine', ['id' => $liste->id]) }}" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                            <i class="fas fa-file-excel w-4" aria-hidden="true"></i> Excel
                        </a>
                    @endcan
                    @if($istTermin)
                        <div class="my-1" style="border-top: 1px solid var(--color-card-border);"></div>
                        <a href="{{ route('listen.ical.export', $liste) }}" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                            <i class="fas fa-calendar-day w-4" aria-hidden="true"></i>
                            <span>iCal: Termine einzeln</span>
                        </a>
                        <a href="{{ route('listen.ical.export', $liste) }}?block=1" role="menuitem" class="{{ $item }}" style="color: var(--color-text-primary);">
                            <i class="fas fa-layer-group w-4" aria-hidden="true"></i>
                            <span>iCal: als Blöcke</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</header>
