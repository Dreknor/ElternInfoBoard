@php
    $liste = $nachricht->rueckmeldung->liste;

    if (!$liste) {
        // Fallback wenn Liste gelöscht wurde
        echo '<div class="alert alert-warning">Die verknüpfte Terminliste wurde gelöscht.</div>';
        return;
    }

    $startDate = $nachricht->rueckmeldung->terminliste_start_date;
    $endDate = $nachricht->rueckmeldung->terminliste_end_date;

    // Hole alle Termine im gewählten Zeitraum (copy() um Mutation zu vermeiden)
    $termine = $liste->termine()
        ->whereBetween('termin', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
        ->where('termin', '>=', \Carbon\Carbon::now())
        ->orderBy('termin')
        ->get();

    // Filtere nach Nutzer
    $userTermine = $termine->where('reserviert_fuer', auth()->id());
    if (auth()->user()->sorg2) {
        $userTermine = $userTermine->merge($termine->where('reserviert_fuer', auth()->user()->sorg2));
    }

    // Freie Termine (nur wenn Nutzer noch keine Buchung hat oder Multiple erlaubt ist)
    $hasBooking = $userTermine->count() > 0;
    $canBook = !$hasBooking || $liste->multiple;
    $freieTermine = $canBook ? $termine->whereNull('reserviert_fuer') : collect();
@endphp

<div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden mb-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-4 py-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar-check text-white"></i>
            </div>
            <div class="flex-1">
                <h6 class="text-white font-semibold mb-0">
                    <a href="{{url('listen/'.$liste->id)}}" class="text-white hover:text-teal-100 transition-colors duration-200" target="_blank">
                        {{$liste->listenname}}
                        <i class="fas fa-external-link-alt text-xs ml-1"></i>
                    </a>
                </h6>
                <p class="text-teal-100 text-sm mb-0">
                    Termine vom {{$startDate->format('d.m.Y')}} bis {{$endDate->format('d.m.Y')}}
                </p>
            </div>
        </div>
    </div>

    <!-- Info & Status -->
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
        @if($liste->comment)
            <div class="mb-3">
                <p class="text-sm text-gray-700">{!! $liste->comment !!}</p>
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            @if($liste->multiple)
                <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                    <i class="fas fa-check-double mr-1"></i>
                    Mehrfachbuchungen erlaubt
                </span>
            @endif

            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{$freieTermine->count()}} freie Termine
            </span>

            @if($userTermine->count() > 0)
                <span class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                    <i class="fas fa-check-circle mr-1"></i>
                    {{$userTermine->count()}} Ihre Buchung(en)
                </span>
            @endif

            <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                <i class="fas fa-list mr-1"></i>
                {{$termine->count()}} Termine gesamt
            </span>
        </div>

        @if(config('app.debug') && $termine->count() === 0)
            <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                <strong>Debug:</strong> Zeitraum: {{$startDate->format('Y-m-d')}} bis {{$endDate->format('Y-m-d')}}
                | Liste-ID: {{$liste->id}}
                | Termine in Liste: {{$liste->termine->count()}}
            </div>
        @endif
    </div>

    <!-- Gebuchte Termine des Nutzers -->
    @if($userTermine->count() > 0)
        <div class="px-4 py-3 border-b border-gray-200">
            <h6 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-user-check text-teal-600 mr-2"></i>
                Ihre gebuchten Termine
            </h6>
            <div class="space-y-2">
                @foreach($userTermine as $termin)
                    <div class="flex items-center justify-between p-3 bg-teal-50 border border-teal-200 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">
                                {{$termin->termin->locale('de')->translatedFormat('l, d.m.Y H:i')}} Uhr
                                @if($termin->duration)
                                    - {{$termin->termin->copy()->addMinutes($termin->duration)->format('H:i')}} Uhr
                                @endif
                            </p>
                            @if($termin->comment)
                                <p class="text-sm text-gray-600 mt-1">{{$termin->comment}}</p>
                            @endif
                        </div>
                        <div class="ml-3">
                            <a href="{{url('listen/'.$liste->id)}}"
                               class="inline-flex items-center px-3 py-1 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                               title="Zur Listenverwaltung (zum Absagen)">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Zum Absagen/Löschen nutzen Sie bitte die <a href="{{url('listen/'.$liste->id)}}" class="text-teal-600 hover:underline">Terminlistenverwaltung</a>.
            </p>
        </div>
    @endif

    <!-- Freie Termine -->
    @if($nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
        @if($canBook && $freieTermine->count() > 0)
            <div class="px-4 py-3">
                <h6 class="text-sm font-semibold text-gray-900 mb-3">
                    <i class="fas fa-calendar-plus text-green-600 mr-2"></i>
                    Verfügbare Termine zum Buchen
                </h6>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($freieTermine as $termin)
                        <div class="flex items-center justify-between p-3 bg-gray-50 hover:bg-green-50 border border-gray-200 hover:border-green-300 rounded-lg transition-all duration-200">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">
                                    {{$termin->termin->locale('de')->translatedFormat('l, d.m.Y H:i')}} Uhr
                                    @if($termin->duration)
                                        - {{$termin->termin->copy()->addMinutes($termin->duration)->format('H:i')}} Uhr
                                    @endif
                                </p>
                                @if($termin->comment)
                                    <p class="text-sm text-gray-600 mt-1">{{$termin->comment}}</p>
                                @endif
                            </div>
                            <div class="ml-3">
                                <form method="post" action="{{url('listen/termine/'.$termin->id)}}" style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <i class="fas fa-check"></i>
                                        Buchen
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif(!$canBook && !$liste->multiple)
            <div class="px-4 py-3">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <i class="fas fa-info-circle text-yellow-600 text-2xl mb-2"></i>
                    <p class="text-yellow-800 font-medium">Sie haben bereits einen Termin gebucht.</p>
                    <p class="text-yellow-700 text-sm mt-1">Mehrfachbuchungen sind für diese Liste nicht erlaubt.</p>
                </div>
            </div>
        @else
            <div class="px-4 py-3">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                    <i class="fas fa-calendar-times text-gray-400 text-2xl mb-2"></i>
                    <p class="text-gray-600 font-medium">Keine freien Termine verfügbar</p>
                    <p class="text-gray-500 text-sm mt-1">Im gewählten Zeitraum sind alle Termine bereits gebucht.</p>
                </div>
            </div>
        @endif
    @else
        <div class="px-4 py-3">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <i class="fas fa-clock text-red-600 text-2xl mb-2"></i>
                <p class="text-red-800 font-medium">Buchungsfrist abgelaufen</p>
                <p class="text-red-700 text-sm mt-1">Die Frist zur Buchung endete am {{$nachricht->rueckmeldung->ende->format('d.m.Y')}}.</p>
            </div>
        </div>
    @endif
</div>

