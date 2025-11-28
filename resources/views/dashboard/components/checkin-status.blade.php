@if($careChildren && $careChildren->count() > 0)
    <div class="col-12 mb-4">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-4 py-3 border-b border-teal-800">
                <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-user-check"></i>
                    CheckIn-Status Ihrer Kinder
                </h5>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($careChildren as $child)
                        <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                            <!-- Header -->
                            <div class="@if($child->checkedIn()) bg-gradient-to-r from-teal-500 to-teal-600 text-white @else bg-gradient-to-r from-orange-500 to-amber-600 text-white @endif px-4 py-3">
                                <h6 class="font-bold text-base mb-0">
                                    {{$child->first_name}} {{$child->last_name}}
                                </h6>
                                @if($child->group)
                                    <p class="text-xs opacity-90 mb-0">{{$child->group->name}}</p>
                                @endif
                            </div>

                            <!-- Body -->
                            <div class="p-4 bg-gray-50">
                                @if($child->krankmeldungToday())
                                    <!-- Krankmeldung -->
                                    <div class="flex items-start gap-2 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                                        <i class="fas fa-notes-medical text-red-600 mt-1"></i>
                                        <div>
                                            <p class="text-red-800 font-semibold text-sm mb-0">Krankgemeldet</p>
                                            <p class="text-red-600 text-xs mb-0">Heute nicht in der Einrichtung</p>
                                        </div>
                                    </div>
                                @elseif(!$child->checkedIn() and $child->checkIns()->where('date', today())->first()?->checked_out)
                                    <!-- Ausgecheckt -->
                                    <div class="flex items-start gap-2 p-3 bg-gray-100 border-l-4 border-gray-400 rounded">
                                        <i class="fas fa-sign-out-alt text-gray-600 mt-1"></i>
                                        <div>
                                            <p class="text-gray-800 font-semibold text-sm mb-0">Abgemeldet</p>
                                            <p class="text-gray-600 text-xs mb-0">
                                                um {{$child->checkIns()->where('date', today())->first()?->updated_at?->format('H:i')}} Uhr
                                            </p>
                                        </div>
                                    </div>
                                @elseif($child->checkedIn())
                                    <!-- Eingecheckt -->
                                    <div class="space-y-3">
                                        <div class="flex items-start gap-2 p-3 bg-teal-50 border-l-4 border-teal-500 rounded">
                                            <i class="fas fa-user-check text-teal-600 mt-1"></i>
                                            <div class="flex-1">
                                                <p class="text-teal-800 font-semibold text-sm mb-0">Angemeldet</p>
                                                <p class="text-teal-600 text-xs mb-0">Derzeit in der Einrichtung</p>
                                            </div>
                                        </div>

                                        <!-- Schickzeiten -->
                                        @if($child->getSchickzeitenForToday()->count() > 0)
                                            <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <i class="far fa-clock text-blue-600 text-sm"></i>
                                                    <span class="font-semibold text-blue-800 text-sm">Schickzeit heute:</span>
                                                </div>
                                                @foreach($child->getSchickzeitenForToday() as $schickzeit)
                                                    <p class="text-blue-700 text-sm mb-0 ml-6">
                                                        @if($schickzeit->type == 'genau')
                                                            <i class="fas fa-dot-circle text-xs mr-1"></i>
                                                            Genau {{$schickzeit->time?->format('H:i')}} Uhr
                                                        @else
                                                            <i class="fas fa-arrow-right text-xs mr-1"></i>
                                                            @if(!is_null($schickzeit->time_ab))
                                                                Ab {{$schickzeit->time_ab?->format('H:i')}} Uhr
                                                            @endif
                                                            @if(!is_null($schickzeit->time_ab) && !is_null($schickzeit->time_spaet))
                                                                -
                                                            @endif
                                                            @if(!is_null($schickzeit->time_spaet))
                                                                Spät. {{$schickzeit->time_spaet?->format('H:i')}} Uhr
                                                            @endif
                                                        @endif
                                                    </p>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="bg-gray-100 border border-gray-200 rounded p-2">
                                                <p class="text-gray-600 text-xs mb-0 text-center">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Keine Schickzeit für heute hinterlegt
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <!-- Nicht eingecheckt -->
                                    <div class="flex items-start gap-2 p-3 bg-gray-100 border-l-4 border-gray-400 rounded">
                                        <i class="fas fa-times-circle text-gray-600 mt-1"></i>
                                        <div>
                                            <p class="text-gray-800 font-semibold text-sm mb-0">Nicht angemeldet</p>
                                            <p class="text-gray-600 text-xs mb-0">Heute noch nicht eingecheckt</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @can('view child')
                    <div class="text-center mt-4 pt-4 border-t border-gray-200">
                        <a href="{{ url('/care/children') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-child mr-2"></i>
                            Zur Kinderübersicht
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endif

