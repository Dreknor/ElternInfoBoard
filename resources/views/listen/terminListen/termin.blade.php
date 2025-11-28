<div class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-all duration-200 hover:border-teal-300 @if($eintrag->termin->lessThan(\Carbon\Carbon::now())) hidden @endif"
     style="@if($eintrag->termin->lessThan(\Carbon\Carbon::now())) display: none; @endif">

    <!-- Header Row -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-3 pb-3 border-b border-gray-200">
        <div class="text-xs font-semibold text-gray-600 uppercase">Von</div>
        <div class="text-xs font-semibold text-gray-600 uppercase">Bis</div>
        <div class="text-xs font-semibold text-gray-600 uppercase">Kommentar</div>
        <div class="text-xs font-semibold text-gray-600 uppercase col-span-2 md:col-span-1">Reserviert für</div>
    </div>

    <!-- Data Row -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-4 items-center">
        <!-- Von -->
        <div class="text-sm">
            <p class="font-medium text-gray-700">{{ $eintrag->termin->dayName }}</p>
            <p class="text-gray-600"><strong>{{ $eintrag->termin->format('d.m.Y') }}</strong></p>
        </div>

        <!-- Bis -->
        <div class="text-sm">
            <p class="text-gray-600">
                <strong>{{ $eintrag->termin->format('H:i') }}</strong> -
                @if($eintrag->ende->day != $eintrag->termin->day)
                    <p class="text-xs text-gray-500">{{ $eintrag->ende->format('d.m.Y') }}</p>
                @endif
                <strong>{{ $eintrag->ende->format('H:i') }} Uhr</strong>
            </p>
        </div>

        <!-- Kommentar -->
        <div class="text-sm text-gray-700">
            {{ $eintrag->comment ?? '-' }}
        </div>

        <!-- Reserviert für -->
        <div class="text-sm col-span-2 md:col-span-1">
            @if($eintrag->reserviert_fuer != null)
                <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                    <i class="fas fa-user mr-1"></i>
                    @if($eintrag->eingetragenePerson->id == auth()->id() or $eintrag->eingetragenePerson->sorg2 == auth()->id() or $liste->visible_for_all or auth()->user()->can('edit terminliste'))
                        {{ $eintrag->eingetragenePerson->name }}
                    @else
                        reserviert
                    @endif
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                    frei
                </span>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-2">
        @if(auth()->user()->id != $liste->besitzer and !auth()->user()->can('edit terminliste'))
            @if($eintrag->reserviert_fuer == null)
                <form method="post" action="{{ url("listen/termine/" . $eintrag->id) }}" style="display: inline;">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-check"></i>
                        Reservieren
                    </button>
                </form>
            @endif
        @else
            @if($eintrag->reserviert_fuer != null)
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200 btnAbsage"
                        data-toggle="modal"
                        data-target="#deleteEintragungModal"
                        data-terminID="{{ $eintrag->id }}">
                    <i class="fas fa-times"></i>
                    {{ $eintrag->eingetragenePerson->name }} absagen
                </button>
            @else
                <form method="post" action="{{ url("listen/termine/" . $eintrag->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-trash-alt"></i>
                        Termin löschen
                    </button>
                </form>

                <form method="post" action="{{ url("listen/termine/" . $eintrag->id) }}" style="display: inline;">
                    @csrf
                    @method('PUT')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-check"></i>
                        Reservieren
                    </button>
                </form>
            @endif

            <a href="{{ url("listen/termine/" . $eintrag->id . "/copy") }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-copy"></i>
                Kopieren
            </a>
        @endif
    </div>
</div>

