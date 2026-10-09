@props([
    'images',                // Collection von Media-Objekten
    'compact' => false,      // schmale Darstellung (z. B. Seitenspalte)
    'showNames' => false,    // Dateinamen unter den Kacheln anzeigen
    'interval' => 4000,      // Diashow-Intervall in ms
])

@php
    $mediaUrl = function ($media, ?string $conversion = null) {
        $url = url('/image/'.$media->id);

        return $conversion && $media->hasGeneratedConversion($conversion)
            ? $url.'?conversion='.$conversion
            : $url;
    };

    $items = $images->values()->map(fn ($media) => [
        'thumb' => $mediaUrl($media, 'thumb'),
        'large' => $mediaUrl($media, 'preview'),
        'original' => $mediaUrl($media),
        'name' => $media->name,
    ]);

    $count = $items->count();
    $maxTiles = $compact ? 6 : 9;
    $visible = $items->take($maxTiles);
    $hidden = $count - $visible->count();

    if ($compact) {
        $gridClass = 'grid-cols-3';
    } else {
        $gridClass = match (true) {
            $count === 2 => 'grid-cols-2',
            $count === 4 => 'grid-cols-2 sm:grid-cols-4',
            default => 'grid-cols-3',
        };
    }
@endphp

@if($count > 0)
<div x-data="{
        images: @js($items),
        open: false,
        index: 0,
        playing: false,
        timer: null,
        touchX: null,
        loaded: [],
        opened: false,
        interval: {{ (int) $interval }},
        get current() { return this.images[this.index] },
        show(i) {
            this.index = i;
            this.open = true;
            this.opened = true;
            document.body.style.overflow = 'hidden';
            this.preload();
        },
        close() {
            this.stop();
            this.open = false;
            document.body.style.overflow = '';
        },
        next() { this.index = (this.index + 1) % this.images.length; this.preload(); },
        prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; this.preload(); },
        go(i) { this.index = i; this.restart(); },
        preload() {
            const n = this.images.length;
            [0, 1, -1].forEach(o => { this.loaded[(this.index + o + n) % n] = true; });
        },
        play() {
            if (this.images.length < 2) return;
            this.playing = true;
            this.timer = setInterval(() => this.next(), this.interval);
        },
        stop() { this.playing = false; clearInterval(this.timer); this.timer = null; },
        toggle() { this.playing ? this.stop() : this.play(); },
        restart() { if (this.playing) { this.stop(); this.play(); } },
        slideshow() { this.show(0); this.play(); },
        key(e) {
            if (!this.open) return;
            if (e.key === 'Escape') this.close();
            else if (e.key === 'ArrowRight') { this.next(); this.restart(); }
            else if (e.key === 'ArrowLeft') { this.prev(); this.restart(); }
            else if (e.key === ' ') { e.preventDefault(); this.toggle(); }
        },
        swipeStart(e) { this.touchX = e.changedTouches[0].clientX },
        swipeEnd(e) {
            if (this.touchX === null) return;
            const dx = e.changedTouches[0].clientX - this.touchX;
            this.touchX = null;
            if (Math.abs(dx) < 40) return;
            dx < 0 ? this.next() : this.prev();
            this.restart();
        },
     }"
     @keydown.window="key($event)"
     {{ $attributes->merge(['class' => 'image-gallery']) }}>

    @if($count === 1)
        {{-- Einzelbild: groß anzeigen --}}
        @php $item = $items->first(); @endphp
        <button type="button" @click="show(0)"
                class="group relative block w-full overflow-hidden rounded-lg bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
            <img src="{{ $compact ? $item['thumb'] : $item['large'] }}" alt="{{ $item['name'] ?? 'Bild' }}"
                 loading="lazy" decoding="async"
                 class="mx-auto w-auto {{ $compact ? 'max-h-60' : 'max-h-[600px]' }} object-contain transition-transform duration-300 group-hover:scale-[1.02]">
            <span class="pointer-events-none absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100">
                <i class="fas fa-expand text-sm"></i>
            </span>
        </button>
        @if($showNames && $item['name'])
            <p class="mt-1 truncate text-center text-xs text-gray-600">{{ $item['name'] }}</p>
        @endif
    @else
        {{-- Kachel-Raster --}}
        <div class="grid {{ $gridClass }} {{ $compact ? 'gap-1.5' : 'gap-2' }}">
            @foreach($visible as $i => $item)
                <div>
                    <button type="button" @click="show({{ $i }})"
                            class="group relative block aspect-square w-full overflow-hidden rounded-lg bg-gray-100 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            aria-label="Bild {{ $i + 1 }} von {{ $count }} vergrößern">
                        <img src="{{ $item['thumb'] }}" alt="{{ $item['name'] ?? 'Bild '.($i + 1) }}"
                             loading="lazy" decoding="async"
                             class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-110">
                        <span class="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/20"></span>
                        @if($loop->last && $hidden > 0)
                            <span class="absolute inset-0 flex items-center justify-center bg-black/55 text-white {{ $compact ? 'text-lg' : 'text-2xl' }} font-semibold">
                                +{{ $hidden }}
                            </span>
                        @else
                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-800 shadow">
                                    <i class="fas fa-search-plus text-sm"></i>
                                </span>
                            </span>
                        @endif
                    </button>
                    @if($showNames && $item['name'])
                        <p class="mt-1 truncate text-center text-xs text-gray-600">{{ $item['name'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span><i class="fas fa-images mr-1"></i>{{ $count }} Bilder</span>
            <button type="button" @click="slideshow()"
                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-medium text-blue-600 hover:bg-blue-50">
                <i class="fas fa-play"></i> Diashow
            </button>
        </div>
    @endif

    {{-- Lightbox --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak
             x-transition.opacity.duration.200ms
             class="fixed inset-0 z-[9999] flex flex-col bg-black/95 text-white select-none"
             role="dialog" aria-modal="true" aria-label="Bildergalerie">

            {{-- Kopfzeile --}}
            <div class="flex items-center justify-between gap-2 px-3 py-2 sm:px-4">
                <span class="text-sm tabular-nums text-white/80" x-text="(index + 1) + ' / ' + images.length"></span>
                <div class="flex items-center gap-1">
                    <template x-if="images.length > 1">
                        <button type="button" @click="toggle()"
                                class="inline-flex h-10 items-center gap-2 rounded-full px-3 text-sm hover:bg-white/10"
                                :title="playing ? 'Diashow anhalten (Leertaste)' : 'Diashow starten (Leertaste)'">
                            <i class="fas" :class="playing ? 'fa-pause' : 'fa-play'"></i>
                            <span class="hidden sm:inline" x-text="playing ? 'Pause' : 'Diashow'"></span>
                        </button>
                    </template>
                    <a :href="current.original" target="_blank" rel="noopener"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full hover:bg-white/10"
                       title="Original öffnen">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <button type="button" @click="close()"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full hover:bg-white/10"
                            title="Schließen (Esc)">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            {{-- Fortschrittsbalken der Diashow --}}
            <div class="h-0.5 w-full bg-white/10">
                <div x-show="playing" class="h-full bg-white/70"
                     x-init="$watch('index', () => { $el.style.transition = 'none'; $el.style.width = '0%'; $el.offsetWidth; $el.style.transition = 'width ' + interval + 'ms linear'; $el.style.width = '100%'; }); $watch('playing', p => { $el.style.transition = 'none'; $el.style.width = '0%'; if (p) { $el.offsetWidth; $el.style.transition = 'width ' + interval + 'ms linear'; $el.style.width = '100%'; } })"
                     style="width: 0%"></div>
            </div>

            {{-- Bildbereich --}}
            <div class="relative flex min-h-0 flex-1 items-center justify-center px-2 sm:px-16" @click.self="close()"
                 @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)">
                <template x-for="(img, i) in images" :key="i">
                    <img x-show="i === index"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         :src="loaded[i] ? img.large : ''"
                         :alt="img.name || ('Bild ' + (i + 1))"
                         class="absolute max-h-full max-w-full object-contain p-2 sm:p-4">
                </template>

                <template x-if="images.length > 1">
                    <div>
                        <button type="button" @click.stop="prev(); restart()"
                                class="absolute left-2 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 hover:bg-white/25 sm:left-4"
                                aria-label="Vorheriges Bild">
                            <i class="fas fa-chevron-left text-lg"></i>
                        </button>
                        <button type="button" @click.stop="next(); restart()"
                                class="absolute right-2 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 hover:bg-white/25 sm:right-4"
                                aria-label="Nächstes Bild">
                            <i class="fas fa-chevron-right text-lg"></i>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Bildunterschrift --}}
            <p x-show="current.name" x-text="current.name"
               class="truncate px-4 pt-1 text-center text-sm text-white/70"></p>

            {{-- Vorschauleiste --}}
            <template x-if="images.length > 1">
                <div class="overflow-x-auto px-3 py-3">
                    <div class="mx-auto flex w-max gap-2"
                         x-effect="if (open) $nextTick(() => $el.querySelectorAll('button')[index]?.scrollIntoView({block: 'nearest', inline: 'center', behavior: 'smooth'}))">
                    <template x-for="(img, i) in images" :key="i">
                        <button type="button" @click="go(i)"
                                class="h-14 w-14 flex-none overflow-hidden rounded-md ring-2 transition sm:h-16 sm:w-16"
                                :class="i === index ? 'ring-white opacity-100' : 'ring-transparent opacity-50 hover:opacity-90'"
                                :aria-label="'Bild ' + (i + 1)">
                            <img :src="opened ? img.thumb : ''" alt="" class="h-full w-full object-cover">
                        </button>
                    </template>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
@endif
