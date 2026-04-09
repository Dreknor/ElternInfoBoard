<div class="border border-gray-200 rounded-lg hover:shadow-lg transition-all duration-200 hover:border-blue-300 flex flex-col" x-data="{ showInfo: false, showMenu: false }">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 rounded-t-lg @if($liste->active == 0) from-cyan-500 to-cyan-600 @endif">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <h3 class="text-base font-bold text-white flex items-start gap-2 mb-0 flex-wrap">
                    <i class="fas fa-list-ul flex-shrink-0 mt-0.5"></i>
                    <span class="flex-1">{{ $liste->listenname }}</span>
                    @if($liste->active == 0)
                        <span class="inline-flex items-center flex-shrink-0 px-2 py-0.5 bg-yellow-400 text-yellow-900 rounded-full text-xs font-semibold">
                            inaktiv
                        </span>
                    @endif
                </h3>
            </div>

            <!-- Compact Actions -->
            <div class="flex items-center gap-1 flex-shrink-0">
                <!-- Info Toggle -->
                <button type="button" @click="showInfo = !showInfo"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white hover:bg-white hover:text-blue-600 transition-all duration-200"
                        title="Info anzeigen">
                    <i class="fas fa-info-circle text-sm" x-show="!showInfo"></i>
                    <i class="fas fa-times text-sm" x-show="showInfo" x-cloak></i>
                </button>

                @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                    <!-- Actions Dropdown Menu -->
                    <div class="relative">
                        <button type="button" @click="showMenu = !showMenu" @click.away="showMenu = false"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white hover:bg-white hover:text-blue-600 transition-all duration-200"
                                title="Aktionen">
                            <i class="fas fa-ellipsis-v text-sm"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="showMenu"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50"
                             x-cloak>
                            <a href="{{ url("listen/$liste->id/edit") }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                <i class="fas fa-pencil-alt text-blue-600 w-4"></i>
                                <span>Bearbeiten</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            @if($liste->active == 0)
                                <form action="{{ url("listen/$liste->id/activate") }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150 w-full text-left">
                                        <i class="fas fa-eye text-green-600 w-4"></i>
                                        <span>Veröffentlichen</span>
                                    </button>
                                </form>
                            @else
                                <form action="{{ url("listen/$liste->id/deactivate") }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150 w-full text-left">
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
        <div x-show="showInfo"
             x-transition
             class="mt-3 pt-3 border-t border-blue-400"
             x-cloak>
            @if($liste->comment)
                <p class="text-blue-100 text-xs mb-2 leading-relaxed">{!! $liste->comment !!}</p>
            @endif

            @if($liste->groups->count() > 0)
                <div class="flex flex-wrap gap-1">
                    @foreach($liste->groups as $group)
                        <span class="inline-flex items-center px-2 py-0.5 bg-blue-200 text-blue-900 rounded-full text-xs font-medium">
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
            <div class="mb-3 pb-3 border-b border-gray-200">
                <p class="text-sm text-gray-600">
                    <span class="font-semibold">Bisherige Eintragungen:</span>
                    <span class="font-bold text-blue-600">{{ $liste->eintragungen->where('user', '!=', null)->count() }} / {{ $liste->eintragungen->count() }}</span>
                </p>
            </div>
        @endif

        @if(isset($eintragungen) && $eintragungen->where('listen_id', $liste->id)->count() > 0)
            <div class="space-y-2 mb-3">
                @foreach($eintragungen->where('listen_id', $liste->id)->sortBy('termin')->all() as $eintragung)
                    <div class="flex items-center justify-between bg-gray-50 rounded p-2">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-700">Ihre Eintragung:</p>
                            <p class="text-sm text-gray-600">{{ $eintragung->eintragung }}</p>
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
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-list-check"></i>
                    Eintragungen anzeigen
                </a>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-gray-50 border-t border-gray-200 px-4 py-2 flex items-center justify-between text-xs rounded-b-lg">
        <small class="text-gray-600">
            <i class="fas fa-calendar-alt mr-1"></i>
            <strong>{{ $liste->ende->format('d.m.Y') }}</strong>
        </small>
        @if(auth()->user()->can('edit terminliste'))
            <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                {{ $liste->type }}
            </span>
        @endif
    </div>
</div>

