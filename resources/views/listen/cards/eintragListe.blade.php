<div class="rounded-lg hover:shadow-lg transition-all duration-200 flex flex-col"
     style="border: 1px solid var(--color-card-border);"
     onmouseover="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-card-b-btn-border')"
     onmouseout="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-card-border')"
     x-data="{ showInfo: false, showMenu: false }">
    <!-- Header -->
    <div class="px-4 py-3 rounded-t-lg @if($liste->active == 0) opacity-80 @endif"
         style="background: var(--color-card-b-header-bg); border-bottom: 1px solid var(--color-card-border);">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <h3 class="text-base font-bold flex items-start gap-2 mb-0 flex-wrap" style="color: var(--color-card-b-header-text);">
                    <i class="fas fa-list-ul flex-shrink-0 mt-0.5"></i>
                    <span class="flex-1">{{ $liste->listenname }}</span>
                    @if($liste->active == 0)
                        <span class="inline-flex items-center flex-shrink-0 px-2 py-0.5 bg-yellow-400 text-yellow-900 rounded-full text-xs font-semibold">
                            inaktiv
                        </span>
                    @endif
                </h3>
            </div>

            <div class="flex items-center gap-1 flex-shrink-0">
                <button type="button" @click="showInfo = !showInfo"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-200"
                        style="color: var(--color-card-b-header-text);"
                        onmouseover="this.style.background='rgba(0,0,0,0.08)'"
                        onmouseout="this.style.background=''"
                        title="Info anzeigen">
                    <i class="fas fa-info-circle text-sm" x-show="!showInfo"></i>
                    <i class="fas fa-times text-sm" x-show="showInfo" x-cloak></i>
                </button>

                @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                    <div class="relative">
                        <button type="button" @click="showMenu = !showMenu" @click.away="showMenu = false"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-200"
                                style="color: var(--color-card-b-header-text);"
                                onmouseover="this.style.background='rgba(0,0,0,0.08)'"
                                onmouseout="this.style.background=''"
                                title="Aktionen">
                            <i class="fas fa-ellipsis-v text-sm"></i>
                        </button>

                        <div x-show="showMenu"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 max-w-[calc(100vw-2rem)] rounded-lg shadow-xl py-1 z-50"
                             style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);"
                             x-cloak>
                            <a href="{{ url("listen/$liste->id/edit") }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm transition-colors duration-150"
                               style="color: var(--color-text-primary);"
                               onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-body-bg')"
                               onmouseout="this.style.background=''">
                                <i class="fas fa-pencil-alt w-4" style="color: var(--color-card-b-btn-text);"></i>
                                <span>Bearbeiten</span>
                            </a>
                            <div class="my-1" style="border-top: 1px solid var(--color-card-border);"></div>
                            @if($liste->active == 0)
                                <form action="{{ url("listen/$liste->id/activate") }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                       class="flex items-center gap-3 px-4 py-2 text-sm transition-colors duration-150 w-full text-left"
                                       style="color: var(--color-text-primary);"
                                       onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-body-bg')"
                                       onmouseout="this.style.background=''">
                                        <i class="fas fa-eye text-green-600 w-4"></i>
                                        <span>Veröffentlichen</span>
                                    </button>
                                </form>
                            @else
                                <form action="{{ url("listen/$liste->id/deactivate") }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                       class="flex items-center gap-3 px-4 py-2 text-sm transition-colors duration-150 w-full text-left"
                                       style="color: var(--color-text-primary);"
                                       onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-body-bg')"
                                       onmouseout="this.style.background=''">
                                        <i class="fas fa-eye-slash text-yellow-600 w-4"></i>
                                        <span>Ausblenden</span>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ url("listen/$liste->id/archiv") }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150">
                                <i class="fas fa-archive w-4"></i>
                                <span>Archivieren</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Details Section (Collapsible) -->
        <div x-show="showInfo" x-transition class="mt-3 pt-3" style="border-top: 1px solid rgba(0,0,0,0.1);" x-cloak>
            @if($liste->comment)
                <p class="text-xs mb-2 leading-relaxed" style="color: var(--color-card-b-header-text); opacity: 0.8;">{!! $liste->comment !!}</p>
            @endif
            @if($liste->groups->count() > 0)
                <div class="flex flex-wrap gap-1">
                    @foreach($liste->groups as $group)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                              style="background: rgba(0,0,0,0.08); color: var(--color-card-b-header-text);">
                            {{ $group->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Body -->
    <div class="px-4 py-3 flex-1 flex flex-col">
        @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
            <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--color-card-border);">
                <p class="text-sm" style="color: var(--color-text-secondary);">
                    <span class="font-semibold">Bisherige Eintragungen:</span>
                    <span class="font-bold" style="color: var(--color-text-success);">{{ $liste->eintragungen->where('user', '!=', null)->count() }}</span><span style="color: var(--color-text-secondary);"> / {{ $liste->eintragungen->count() }}</span>
                </p>
            </div>
        @endif

        @if(isset($eintragungen) && $eintragungen->where('listen_id', $liste->id)->count() > 0)
            <div class="space-y-2 mb-3">
                @foreach($eintragungen->where('listen_id', $liste->id)->sortBy('termin')->all() as $eintragung)
                    <div class="flex items-center justify-between rounded p-2" style="background: var(--color-widget-body-bg);">
                        <div class="flex-1">
                            <p class="text-sm font-medium" style="color: var(--color-text-primary);">
                                Ihre Eintragung:
                                @if(auth()->user()->sorg2 !== null)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $eintragung->user_id == auth()->id() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        <i class="fas fa-user mr-1"></i>{{ $eintragung->user?->name ?? '–' }}
                                    </span>
                                @endif
                            </p>
                            <p class="text-sm" style="color: var(--color-text-secondary);">{{ $eintragung->eintragung }}</p>
                        </div>
                        <form action="{{ url('eintragungen/absagen/' . $eintragung->id) }}" method="post" class="ml-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded transition-colors duration-200">
                                <i class="fas fa-trash-alt mr-1"></i>
                                Löschen
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-auto">
            @if((isset($eintragungen) && $eintragungen->where('listen_id', $liste->id)->count() < 1) or $liste->multiple == 1 or $liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                <a href="{{ url("listen/$liste->id") }}"
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg transition-colors duration-200"
                   style="background: var(--color-card-b-btn-bg); border: 2px solid var(--color-card-b-btn-border); color: var(--color-card-b-btn-text);"
                   onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-card-b-btn-hover')"
                   onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-card-b-btn-bg')">
                    <i class="fas fa-list-check"></i>
                    Eintragungen anzeigen
                </a>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2 flex items-center justify-between text-xs rounded-b-lg"
         style="background: var(--color-widget-body-bg); border-top: 1px solid var(--color-card-border);">
        <small style="color: var(--color-text-secondary);">
            <i class="fas fa-calendar-alt mr-1"></i>
            <strong>{{ $liste->ende->format('d.m.Y') }}</strong>
        </small>
        @if(auth()->user()->can('edit terminliste'))
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                  style="background: var(--color-badge-eintrag-bg); color: var(--color-badge-eintrag-text);">
                {{ $liste->type }}
            </span>
        @endif
    </div>
</div>

