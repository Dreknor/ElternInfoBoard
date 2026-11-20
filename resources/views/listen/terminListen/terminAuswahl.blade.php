@extends('layouts.app')
@section('title') - Termine @endsection

@section('content')
    @php
        $preview ??= false;
        $canManage ??= ! $preview && (auth()->id() == $liste->besitzer or auth()->user()->can('edit terminliste'));
    @endphp
    <div class="w-full max-w-5xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-5"
         x-data="{ onlyFree: false, showPast: false, buchen: null, absage: null, neuerTermin: false }"
         x-init="$watch('buchen', v => v && $nextTick(() => $refs.buchenOk.focus())); $watch('absage', v => v && $nextTick(() => $refs.absageText.focus()))"
         @keydown.escape.window="buchen = null; absage = null; neuerTermin = false">

        @include('listen.partials.kopf', [
            'zaehler' => $gesamt > 0 ? "$frei von $gesamt Terminen frei" : null,
            'werkzeuge' => 'listen.terminListen.werkzeuge',
        ])

        <div class="space-y-5" @if($preview) inert @endif>

            {{-- Eigene Buchung(en) --}}
            @if($eigeneTermine->isNotEmpty())
                <section aria-labelledby="eigene-termine" class="rounded-lg p-4 border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20">
                    <h2 id="eigene-termine" class="text-base font-bold mb-3 flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        {{ $eigeneTermine->count() > 1 ? 'Ihre Termine' : 'Ihr Termin' }}
                    </h2>
                    <ul class="space-y-3 mb-0 pl-0 list-none">
                        @foreach($eigeneTermine as $termin)
                            <li class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-lg p-3" style="background: var(--color-card-bg);">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold mb-0" style="color: var(--color-text-primary);">
                                        {{ $termin->termin->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                                    </p>
                                    <p class="mb-0" style="color: var(--color-text-primary);">
                                        {{ $termin->termin->format('H:i') }}–{{ $termin->ende->format('H:i') }} Uhr
                                    </p>
                                    @if($termin->comment)
                                        <p class="text-sm mb-0" style="color: var(--color-text-secondary);">{{ $termin->comment }}</p>
                                    @endif
                                    @if($termin->child)
                                        <p class="text-xs mb-0" style="color: var(--color-text-secondary);"><i class="fas fa-child mr-1" aria-hidden="true"></i>für {{ $termin->child->first_name }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @include('listen.partials.kalender_links', ['termin' => $termin])
                                    <button type="button"
                                            @click="absage = { id: {{ $termin->id }}, label: @js($termin->termin->format('d.m.Y').', '.$termin->termin->format('H:i').' Uhr'), eltern: true }"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                                        <i class="fas fa-times" aria-hidden="true"></i> Absagen
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($gesperrt && ! $canManage)
                <div class="rounded-lg px-4 py-3 flex items-start gap-3 bg-sky-50 text-sky-900 border border-sky-200 dark:bg-sky-900/20 dark:text-sky-200 dark:border-sky-800" role="status">
                    <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                    <p class="mb-0 text-sm">
                        {{ $liste->bookingPerChild() ? 'Für alle Ihre Kinder ist bereits ein Termin gebucht.' : 'Sie haben bereits einen Termin.' }}
                        Zum Umbuchen bitte zuerst absagen.
                    </p>
                </div>
            @endif

            @if($liste->termine->isEmpty())
                <div class="rounded-lg p-6 text-center" style="background: var(--color-card-bg); border: 2px dashed var(--color-card-border);">
                    <i class="fas fa-calendar-times text-3xl mb-2" style="color: var(--color-text-secondary);" aria-hidden="true"></i>
                    <p class="text-lg mb-0" style="color: var(--color-text-secondary);">Es wurden bisher keine Termine angelegt.</p>
                    @if($canManage)
                        <p class="text-sm mt-1 mb-0" style="color: var(--color-text-secondary);">Lege mit „+ Termin“ die ersten Termine an.</p>
                    @endif
                </div>
            @else
                @if($allTermineInPast)
                    <div class="rounded-lg px-4 py-3 flex items-start gap-3 bg-amber-50 text-amber-900 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-200 dark:border-amber-800">
                        <i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold mb-0">Alle angelegten Termine liegen bereits in der Vergangenheit.</p>
                            <p class="text-sm mb-0">
                                {{ $canManage ? 'Mit „Vergangene anzeigen“ kannst du sie trotzdem einblenden.' : 'Aktuell sind daher keine buchbaren Termine mehr vorhanden.' }}
                            </p>
                        </div>
                    </div>
                @elseif($frei === 0 && $eigeneTermine->isEmpty() && ! $canManage)
                    <div class="rounded-lg px-4 py-3 flex items-start gap-3 bg-amber-50 text-amber-900 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-200 dark:border-amber-800">
                        <i class="fas fa-calendar-times mt-1" aria-hidden="true"></i>
                        <p class="mb-0 text-sm">
                            Alle Termine sind vergeben.
                            @if($liste->ersteller)
                                Bei Fragen wenden Sie sich an {{ $liste->ersteller->name }}.
                            @endif
                        </p>
                    </div>
                @endif

                {{-- Filter --}}
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                    <label class="inline-flex items-center gap-2 text-sm cursor-pointer" style="color: var(--color-text-primary);">
                        <input type="checkbox" x-model="onlyFree" class="w-4 h-4 rounded">
                        Nur freie Termine
                    </label>
                    @if($canManage)
                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer" style="color: var(--color-text-primary);">
                            <input type="checkbox" x-model="showPast" class="w-4 h-4 rounded">
                            Vergangene anzeigen
                        </label>
                    @endif
                    <span class="flex items-center gap-3 text-xs ml-auto" style="color: var(--color-text-secondary);" aria-hidden="true">
                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded border-2 border-sky-500"></span> frei</span>
                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500"></span> Ihr Termin</span>
                        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded" style="background: var(--color-card-border);"></span> vergeben</span>
                    </span>
                </div>

                <template x-if="onlyFree && {{ $frei }} === 0">
                    <p class="text-sm" style="color: var(--color-text-secondary);">Es gibt aktuell keine freien Termine.</p>
                </template>

                {{-- Termine nach Tagen --}}
                @foreach($tage as $tag)
                    @php
                        $hatEigenen = $tag['termine']->contains(fn ($t) => $eigeneTermine->contains('id', $t->id));
                    @endphp
                    @continue($tag['vergangen'] && ! $canManage)
                    <section aria-label="{{ $tag['datum']->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}"
                             class="rounded-lg overflow-hidden shadow-sm"
                             style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);"
                             @if($tag['vergangen']) x-show="showPast" x-cloak
                             @elseif($tag['frei'] === 0 && ! $hatEigenen) x-show="!onlyFree" @endif>
                        <h2 class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-3 mb-0 text-base font-bold"
                            style="background: var(--color-surface-subtle); border-bottom: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                            <span>
                                <i class="far fa-calendar mr-1" aria-hidden="true"></i>
                                {{ $tag['datum']->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                            </span>
                            <span class="text-sm font-medium {{ $tag['frei'] > 0 ? 'text-sky-700 dark:text-sky-300' : '' }}"
                                  @if($tag['frei'] === 0) style="color: var(--color-text-secondary);" @endif>
                                {{ $tag['vergangen'] ? 'vergangen' : $tag['frei'].' von '.$tag['gesamt'].' frei' }}
                            </span>
                        </h2>
                        <div class="p-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                            @foreach($tag['termine'] as $termin)
                                @include('listen.terminListen.termin')
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif
        </div>

        {{-- Dialog: Termin reservieren --}}
        <div x-show="buchen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="buchen-titel">
            <div class="absolute inset-0 bg-black/50" @click="buchen = null"></div>
            <form method="post" :action="buchen ? @js(url('listen/termine')) + '/' + buchen.id : '#'"
                  class="relative w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 space-y-4"
                  style="background: var(--color-card-bg);">
                @csrf
                @method('PUT')
                <h2 id="buchen-titel" class="text-lg font-bold mb-0" style="color: var(--color-text-primary);">Termin reservieren</h2>
                <div class="rounded-lg p-3" style="background: var(--color-surface-subtle); border: 1px solid var(--color-card-border);">
                    <p class="text-sm mb-1" style="color: var(--color-text-secondary);">{{ $liste->listenname }}</p>
                    <p class="font-semibold mb-0" style="color: var(--color-text-primary);" x-text="buchen?.label"></p>
                    <p class="text-sm mb-0" style="color: var(--color-text-secondary);" x-show="buchen?.kommentar" x-text="buchen?.kommentar"></p>
                </div>
                @include('listen.partials.child_select', ['selectSuffix' => 'termin'])
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="buchen = null"
                            class="px-4 py-2.5 rounded-lg font-medium" style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                        Abbrechen
                    </button>
                    <button type="submit" x-ref="buchenOk" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-white bg-emerald-600 hover:bg-emerald-700">
                        <i class="fas fa-check" aria-hidden="true"></i> Verbindlich reservieren
                    </button>
                </div>
            </form>
        </div>

        {{-- Dialog: Termin absagen --}}
        <div x-show="absage" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="absage-titel">
            <div class="absolute inset-0 bg-black/50" @click="absage = null"></div>
            <form method="post" :action="absage ? @js(url('listen/termine/absagen')) + '/' + absage.id : '#'"
                  class="relative w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 space-y-4"
                  style="background: var(--color-card-bg);">
                @csrf
                @method('DELETE')
                <h2 id="absage-titel" class="text-lg font-bold mb-0" style="color: var(--color-text-primary);">Termin absagen</h2>
                <p class="mb-0" style="color: var(--color-text-primary);">
                    Termin am <strong x-text="absage?.label"></strong> wirklich absagen?
                </p>
                <div>
                    <label for="absage-text" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">
                        Nachricht <span class="font-normal" style="color: var(--color-text-secondary);">(optional)</span>
                    </label>
                    <textarea name="text" id="absage-text" rows="3" x-ref="absageText"
                              class="w-full px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-red-300 resize-none"
                              style="border: 1px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);"
                              :placeholder="absage?.eltern ? 'z. B. Grund der Absage' : 'Nachricht an die Familie'"></textarea>
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="absage = null"
                            class="px-4 py-2.5 rounded-lg font-medium" style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                        Abbrechen
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-times" aria-hidden="true"></i> Termin absagen
                    </button>
                </div>
            </form>
        </div>

        @if($canManage)
            @include('listen.terminListen.neuer_termin')
        @endif
    </div>
@endsection
