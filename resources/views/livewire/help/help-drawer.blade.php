<div>
    {{-- Trigger via Alpine: $dispatch('help:open') --}}
    <div x-data="{}"
         x-on:help:open.window="$wire.openDrawer($event.detail?.route, $event.detail?.uri)"
         x-on:keydown.escape.window="$wire.closeDrawer()">

        {{-- Backdrop --}}
        <div
            x-show="$wire.open"
            x-transition.opacity
            x-cloak
            wire:click="closeDrawer"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm"
            style="z-index: 1080;">
        </div>

        {{-- Slideover --}}
        <aside
            x-show="$wire.open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="fixed top-0 right-0 h-full w-full sm:w-[420px] max-w-full bg-white shadow-2xl flex flex-col"
            style="z-index: 1090;">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
                <div class="flex items-center gap-2">
                    <i class="fas fa-circle-question text-2xl"></i>
                    <div>
                        <h2 class="font-bold text-lg leading-tight mb-0">Hilfe & Anleitung</h2>
                        <p class="text-xs text-blue-100 mb-0">Erste Anlaufstelle bei Fragen</p>
                    </div>
                </div>
                <button type="button" wire:click="closeDrawer"
                        class="p-2 rounded-lg hover:bg-white/20 transition-colors">
                    <i class="fas fa-xmark text-xl"></i>
                </button>
            </div>

            {{-- Suche --}}
            <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text"
                           wire:model.live.debounce.250ms="search"
                           placeholder="Hilfe-Themen durchsuchen…"
                           class="w-full pl-10 pr-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none text-sm">
                </div>
            </div>

            {{-- Inhalt --}}
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-6">

                @if($contextual->isNotEmpty() && $search === '')
                    <section>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-location-crosshairs"></i>
                            Passend zu dieser Seite
                        </h3>
                        <div class="space-y-2">
                            @foreach($contextual as $topic)
                                @include('livewire.help.partials.topic-card', ['topic' => $topic, 'highlight' => true])
                            @endforeach
                        </div>
                    </section>
                @endif

                <section>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-list"></i>
                            {{ $search !== '' ? 'Suchergebnisse' : 'Alle Themen' }}
                        </span>
                        <span class="text-gray-400 normal-case font-medium">{{ $all->count() }}</span>
                    </h3>

                    @if($all->isEmpty())
                        <p class="text-sm text-gray-500 italic py-4 text-center">
                            Keine Themen gefunden.
                        </p>
                    @else
                        <div class="space-y-2">
                            @foreach($all as $topic)
                                @include('livewire.help.partials.topic-card', ['topic' => $topic, 'highlight' => false])
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                <a href="{{ url('hilfe') }}"
                   class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                    <i class="fas fa-book-open"></i>
                    Zur Hilfe-Übersicht
                </a>
                <a href="{{ url('feedback') }}"
                   class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <i class="fas fa-comment-dots"></i>
                    Feedback
                </a>
            </div>
        </aside>
    </div>
</div>
