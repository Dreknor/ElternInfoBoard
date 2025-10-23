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
                    <div class="relative group">
                        <button class="text-white hover:bg-white/20 rounded p-1.5 transition-all duration-200" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-ellipsis-v text-sm" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="{{url('termin/create')}}" class="dropdown-item flex items-center space-x-2 px-3 py-1.5 hover:bg-gray-100 transition-colors text-sm">
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
