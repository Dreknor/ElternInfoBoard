    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-3" id="table_of_contents">
        <!-- Header mit Gradient -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 border-b border-blue-800">
            <h5 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                Aktuelle Nachrichten
            </h5>
        </div>

        @if($nachrichten != null and count($nachrichten)>0)
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Themen Bereich -->
                    <div class="lg:col-span-9">
                        <!-- Mobile Toggle Button -->
                        <button
                            class="w-full md:hidden mb-4 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 flex items-center justify-center gap-2"
                            type="button"
                            onclick="document.getElementById('Themen').classList.toggle('hidden')"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            Themen zeigen
                        </button>

                        <!-- Themen Grid -->
                        <div class="hidden md:block" id="Themen">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($nachrichten AS $nachricht)
                                    @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                        <a href="#{{$nachricht->id}}"
                                           class="anker_link group relative overflow-hidden rounded-lg shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 p-3
                                                  @if($nachricht->released == 1)
                                                      bg-gradient-to-br from-gray-100 to-gray-300 hover:from-blue-100 hover:to-blue-200 text-black
                                                  @else
                                                      bg-white border-2 border-amber-400 hover:bg-amber-50 text-gray-800
                                                  @endif
                                                  @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach
                                           ">
                                            <div class="flex items-start gap-2">
                                                <!-- Icons Container -->
                                                <div class="flex-shrink-0 flex flex-col gap-1">
                                                    @if(! is_null($nachricht->rueckmeldung))
                                                        <span class="inline-flex @if($nachricht->rueckmeldung->pflicht == 1) text-red-300 @endif">
                                                            @switch($nachricht->rueckmeldung->type)
                                                                @case('email')
                                                                    <i class="fas fa-comment-dots text-sm"></i>
                                                                    @break
                                                                @case('abfrage')
                                                                    <i class="fa fa-poll-h text-sm"></i>
                                                                    @break
                                                            @endswitch
                                                        </span>
                                                    @endif

                                                    @if($nachricht->read_receipt == 1)
                                                        <span class="inline-flex">
                                                            <i class="fas fa-book-open text-sm"></i>
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Nachricht Text -->
                                                <div class="flex-1 min-w-0">
                                                    <span class="block text-xs font-semibold leading-tight break-words
                                                        @switch($nachricht->type)
                                                            @case('pflicht')
                                                                @if($nachricht->released == 1) text-red-100 @else text-red-600 @endif
                                                                @break
                                                            @case('wahl')
                                                                @if($nachricht->released == 1) text-amber-100 @else text-amber-600 @endif
                                                                @break
                                                        @endswitch
                                                    ">
                                                        {{$nachricht->header}}
                                                    </span>
                                                </div>

                                                <!-- Arrow Icon -->
                                                <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Filter Sidebar -->
                    <div class="lg:col-span-3 lg:border-l lg:border-gray-200 lg:pl-4">
                        <div class="bg-gray-50 rounded-lg p-3 sticky top-4">
                            <h6 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filter
                            </h6>
                            <div class="space-y-2">
                                @foreach(auth()->user()->groups as $group)
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white transition-colors cursor-pointer group">
                                        <div class="relative inline-flex items-center">
                                            <input type="checkbox"
                                                   class="filter_switch sr-only peer"
                                                   id="{{\Illuminate\Support\Str::camel($group->name)}}">
                                            <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-blue-600 peer-focus:ring-2 peer-focus:ring-blue-300 transition-all duration-200">
                                                <div class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition-transform duration-200 peer-checked:translate-x-4"></div>
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 group-hover:text-gray-900">
                                            {{$group->name}}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            <div class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-200 rounded-full mb-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <p class="text-gray-700 text-sm font-medium">
                    Es sind keine Nachrichten vorhanden
                </p>
            </div>

        @endif
    </div>


    @foreach($nachrichten AS $nachricht)
        @if($nachricht->released == 1 or auth()->user()->can('edit posts') or $nachricht->author == auth()->id())
            <div
                class="nachricht @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach">
                @include('nachrichten.nachricht')
            </div>
        @endif
    @endforeach
