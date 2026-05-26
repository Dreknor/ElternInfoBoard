@if(isset($termine) and !is_null($termine) and !isset($archiv))
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 mb-2">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-2.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="bg-white/20 rounded p-1.5">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h5 class="text-base font-bold text-white">
                        Aktuelle Termine
                    </h5>
                </div>
                @can('edit termin')
                    <div x-data="{
                        open: false,
                        pos: { top: 0, right: 0 },
                        toggle(event) {
                            this.open = !this.open;
                            if (this.open) {
                                const rect = event.currentTarget.getBoundingClientRect();
                                this.pos.top = rect.bottom + 4;
                                this.pos.right = window.innerWidth - rect.right;
                            }
                        }
                    }" @click.outside="open = false">
                        <button @click="toggle($event)"
                                class="text-white hover:bg-white/20 rounded p-1.5 transition-all duration-200"
                                aria-haspopup="true" :aria-expanded="open">
                            <i class="fa fa-ellipsis-v text-sm" aria-hidden="true"></i>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             :style="`position: fixed; top: ${pos.top}px; right: ${pos.right}px; z-index: 1050;`"
                             class="w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1"
                             style="display: none;">
                            <a href="{{ url('termine/create') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fa fa-plus text-blue-600"></i>
                                <span>Neuer Termin</span>
                            </a>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        <!-- Body -->
        <div class="divide-y divide-gray-100">
            @forelse($termine as $termin)
                @include('termine.termin')
            @empty
                <div class="px-4 py-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-100 rounded-full mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">Keine aktuellen Termine vorhanden</p>
                </div>
            @endforelse
        </div>
    </div>
@endif
