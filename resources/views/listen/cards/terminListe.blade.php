<div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-all duration-200 hover:border-teal-300 flex flex-col" x-data="{ showInfo: false, showMenu: false }">
    <!-- Header -->
    <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-4 py-3 @if($liste->active == 0) from-cyan-500 to-cyan-600 @endif">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <h3 class="text-base font-bold text-white flex items-start gap-2 mb-0 flex-wrap">
                    <i class="fas fa-calendar-check flex-shrink-0 mt-0.5"></i>
                    <span class="flex-1">{{ $liste->listenname }}</span>
                    @if($liste->active == 0)
                        <span class="inline-flex items-center flex-shrink-0 px-2 py-0.5 bg-yellow-400 text-yellow-900 rounded-full text-xs font-semibold">
                            inaktiv
                        </span>
                    @endif
                    @if($liste->creates_pflichtstunden)
                        <span class="inline-flex items-center flex-shrink-0 px-2 py-0.5 bg-green-300 text-green-900 rounded-full text-xs font-semibold"
                              title="Für diese Liste werden automatisch Pflichtstunden-Einträge erstellt">
                            <i class="fas fa-clock mr-1"></i>
                            Pflichtstunden
                        </span>
                    @endif
                </h3>
            </div>

            <!-- Compact Actions -->
            <div class="flex items-center gap-1 flex-shrink-0">
                <!-- Info Toggle -->
                <button type="button" @click="showInfo = !showInfo"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white hover:bg-white hover:text-teal-600 transition-all duration-200"
                        title="Info anzeigen">
                    <i class="fas fa-info-circle text-sm" x-show="!showInfo"></i>
                    <i class="fas fa-times text-sm" x-show="showInfo" x-cloak></i>
                </button>

                @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                    <!-- Actions Dropdown Menu -->
                    <div class="relative">
                        <button type="button" @click="showMenu = !showMenu" @click.away="showMenu = false"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white hover:bg-white hover:text-teal-600 transition-all duration-200"
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
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50"
                             x-cloak>
                            <a href="{{ url("listen/$liste->id/edit") }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                <i class="fas fa-pencil-alt text-teal-600 w-4"></i>
                                <span>Bearbeiten</span>
                            </a>
                            <a href="{{ url("listen/$liste->id/ical/export") }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                <i class="fas fa-file-export text-teal-600 w-4"></i>
                                <span>iCal Export</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            @if($liste->active == 0)
                                <a href="{{ url("listen/$liste->id/activate") }}"
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                    <i class="fas fa-eye text-green-600 w-4"></i>
                                    <span>Veröffentlichen</span>
                                </a>
                            @else
                                <a href="{{ url("listen/$liste->id/deactivate") }}"
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                    <i class="fas fa-eye-slash text-yellow-600 w-4"></i>
                                    <span>Ausblenden</span>
                                </a>
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
             class="mt-3 pt-3 border-t border-teal-400"
             x-cloak>
            @if($liste->comment)
                <p class="text-teal-100 text-xs mb-2 leading-relaxed">{!! $liste->comment !!}</p>
            @endif

            @if($liste->groups->count() > 0)
                <div class="flex flex-wrap gap-1">
                    @foreach($liste->groups as $group)
                        <span class="inline-flex items-center px-2 py-0.5 bg-teal-200 text-teal-900 rounded-full text-xs font-medium">
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
                    <span class="font-semibold">Bisherige Buchungen:</span>
                    <span class="font-bold text-teal-600">{{ $liste->termine->where('reserviert_fuer', '!=', null)->count() }} / {{ $liste->termine->count() }}</span>
                </p>
            </div>
        @endif

        @if(isset($termine) && $termine->where('listen_id', $liste->id)->count() > 0)
            <div class="space-y-2 mb-3">
                @foreach($termine->filter(function ($eintrag) use ($liste) {
                    if ($eintrag->listen_id == $liste->id and $eintrag->termin->greaterThanOrEqualTo(\Carbon\Carbon::now())) {
                        return $eintrag;
                    }
                })->sortBy('termin') as $eintragung)
                    <div class="flex items-center justify-between bg-gray-50 rounded p-2">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-700">Ihr Termin:</p>
                            <p class="text-sm text-gray-600">{{ $eintragung->termin->format('d.m.Y H:i') }} Uhr</p>
                        </div>
                        <div class="flex items-center gap-1 ml-2">
                            <a href="{{ $eintragung->link($liste->listenname, $liste->duration)->google() }}"
                               class="inline-flex items-center justify-center p-1.5 rounded-lg bg-white hover:bg-gray-100 text-gray-600 transition-all duration-200"
                               target="_blank"
                               title="Google Kalender">
                                <img src="{{ asset('img/icon-google-cal.png') }}" height="16px" alt="Google">
                            </a>
                            <a href="{{ $eintragung->link($liste->listenname, $liste->duration)->ics() }}"
                               class="inline-flex items-center justify-center p-1.5 rounded-lg bg-white hover:bg-gray-100 text-gray-600 transition-all duration-200"
                               title="iCal Download">
                                <img src="{{ asset('img/ics-icon.png') }}" height="16px" alt="iCal">
                            </a>
                            <form action="{{ url('listen/termine/absagen/' . $eintragung->id) }}" method="post" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded transition-colors duration-200">
                                    <i class="fas fa-times mr-1"></i>
                                    Absagen
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-auto">
            @if((isset($termine) && $termine->where('listen_id', $liste->id)->count() < 1) or $liste->multiple == 1 or $liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                <a href="{{ url("listen/$liste->id") }}"
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-calendar-plus"></i>
                    Termine anzeigen
                </a>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-gray-50 border-t border-gray-200 px-4 py-2 flex items-center justify-between text-xs">
        <small class="text-gray-600">
            <i class="fas fa-calendar-alt mr-1"></i>
            <strong>{{ $liste->ende->format('d.m.Y') }}</strong>
        </small>
        @if(auth()->user()->can('edit terminliste'))
            <span class="inline-flex items-center px-2 py-0.5 bg-teal-100 text-teal-800 rounded-full text-xs font-semibold">
                {{ $liste->type }}
            </span>
        @endif
    </div>
</div>

