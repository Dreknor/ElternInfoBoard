@can('view rueckmeldungen')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-comments text-white"></i>
                </div>
                <h6 class="text-white font-semibold mb-0">
                    Eingegangene Rückmeldungen ({{$nachricht->userRueckmeldung->count()}})
                </h6>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="divide-y divide-gray-200">
            @foreach($nachricht->userRueckmeldung as $rueckmeldung)
                <div class="p-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <!-- User Info -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm">
                                    {{substr($rueckmeldung->user->name, 0, 1)}}
                                </span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{$rueckmeldung->user->name}}</p>
                                <p class="text-xs text-gray-500">
                                    <i class="far fa-clock mr-1"></i>
                                    {{$rueckmeldung->updated_at->format('d.m.Y H:i')}}
                                </p>
                            </div>
                        </div>

                        <!-- Show Button -->
                        <button class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-colors duration-200 btnShow"
                                data-toggle="collapse"
                                data-target="#{{$rueckmeldung->id}}_rueckmeldungen_text">
                            <i class="fa fa-eye"></i>
                            <span>Anzeigen</span>
                        </button>
                    </div>

                    <!-- Feedback Text (Collapsible) -->
                    <div id="{{$rueckmeldung->id.'_rueckmeldungen_text'}}" class="hidden">
                        <div class="mt-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="prose max-w-none text-sm text-gray-700">
                                {!! $rueckmeldung->text !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($nachricht->userRueckmeldung->count() === 0)
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-3">
                    <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-500 font-medium">Noch keine Rückmeldungen eingegangen</p>
            </div>
        @endif
    </div>
@endcan
@can('view rueckmeldungen')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-comments text-white"></i>
                </div>
                <h6 class="text-white font-semibold mb-0">
                    Eingegangene Rückmeldungen ({{$nachricht->userRueckmeldung->count()}})
                </h6>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="divide-y divide-gray-200">
            @foreach($nachricht->userRueckmeldung as $rueckmeldung)
                <div class="p-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <!-- User Info -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm">
                                    {{substr($rueckmeldung->user->name, 0, 1)}}
                                </span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{$rueckmeldung->user->name}}</p>
                                <p class="text-xs text-gray-500">
                                    <i class="far fa-clock mr-1"></i>
                                    {{$rueckmeldung->updated_at->format('d.m.Y H:i')}}
                                </p>
                            </div>
                        </div>

                        <!-- Show Button -->
                        <button class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-colors duration-200"
                                onclick="document.getElementById('{{$rueckmeldung->id}}_rueckmeldungen_text').classList.toggle('hidden'); this.querySelector('span').textContent = this.querySelector('span').textContent === 'Anzeigen' ? 'Ausblenden' : 'Anzeigen';">
                            <i class="fa fa-eye"></i>
                            <span>Anzeigen</span>
                        </button>
                    </div>

                    <!-- Feedback Text (Collapsible) -->
                    <div id="{{$rueckmeldung->id.'_rueckmeldungen_text'}}" class="collapse">
                        <div class="mt-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="prose max-w-none text-sm text-gray-700">
                                {!! $rueckmeldung->text !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($nachricht->userRueckmeldung->count() === 0)
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-3">
                    <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-500 font-medium">Noch keine Rückmeldungen eingegangen</p>
            </div>
        @endif
    </div>
@endcan

