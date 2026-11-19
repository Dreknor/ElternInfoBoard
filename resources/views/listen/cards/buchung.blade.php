{{-- Eigene Buchung (Termin oder Eintrag) in der Übersicht „Ihre Buchungen“ --}}
@php
    $istTermin = $buchung instanceof \App\Model\listen_termine;
    $person = $istTermin ? $buchung->eingetragenePerson : $buchung->user;
    $mehrereFamilienmitglieder = count(auth()->user()->familyUserIds()) > 1;
@endphp
<article class="rounded-lg p-4 flex flex-col gap-3 border-l-4 border-emerald-500"
         style="background: var(--color-card-bg); border-top: 1px solid var(--color-card-border); border-right: 1px solid var(--color-card-border); border-bottom: 1px solid var(--color-card-border);">
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--color-text-secondary);">
            <i class="fas {{ $istTermin ? 'fa-calendar-check' : 'fa-list-ul' }} mr-1" aria-hidden="true"></i>
            {{ $liste->listenname }}
        </p>
        @if($istTermin)
            <p class="font-semibold mb-0" style="color: var(--color-text-primary);">
                {{ $buchung->termin->locale('de')->isoFormat('dd, DD.MM.YYYY') }}
            </p>
            <p class="mb-0" style="color: var(--color-text-primary);">
                {{ $buchung->termin->format('H:i') }}–{{ $buchung->ende->format('H:i') }} Uhr
            </p>
            @if($buchung->comment)
                <p class="text-sm mb-0" style="color: var(--color-text-secondary);">{{ $buchung->comment }}</p>
            @endif
        @else
            <p class="font-semibold mb-0 break-words" style="color: var(--color-text-primary);">{{ $buchung->eintragung }}</p>
            @if($buchung->bemerkung)
                <p class="text-sm mb-0" style="color: var(--color-text-secondary);"><i class="fas fa-comment-dots mr-1" aria-hidden="true"></i>{{ $buchung->bemerkung }}</p>
            @endif
        @endif
        @if($buchung->child || $mehrereFamilienmitglieder)
            <p class="text-xs mt-1 mb-0" style="color: var(--color-text-secondary);">
                @if($mehrereFamilienmitglieder && $person)
                    <i class="fas fa-user mr-1" aria-hidden="true"></i>{{ $person->name }}
                @endif
                @if($buchung->child)
                    <span class="ml-1"><i class="fas fa-child mr-1" aria-hidden="true"></i>für {{ $buchung->child->first_name }}</span>
                @endif
            </p>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-2 mt-auto">
        <a href="{{ url('listen/'.$liste->id) }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg"
           style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
            Zur Liste
        </a>
        @if($istTermin)
            @include('listen.partials.kalender_links', ['termin' => $buchung, 'liste' => $liste])
            <form action="{{ url('listen/termine/absagen/'.$buchung->id) }}" method="post" class="ml-auto mb-0"
                  onsubmit="return confirm(@js('Termin am '.$buchung->termin->format('d.m.Y').' um '.$buchung->termin->format('H:i').' Uhr wirklich absagen?'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Absagen
                </button>
            </form>
        @else
            <form action="{{ url('eintragungen/absagen/'.$buchung->id) }}" method="post" class="ml-auto mb-0"
                  onsubmit="return confirm(@js('Eintrag „'.$buchung->eintragung.'“ wirklich austragen?'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Austragen
                </button>
            </form>
        @endif
    </div>
</article>
