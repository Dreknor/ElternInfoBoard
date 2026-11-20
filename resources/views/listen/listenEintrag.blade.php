@extends('layouts.app')
@section('title') - Liste Details @endsection

@section('content')
    @php
        $preview ??= false;
        $canManage ??= ! $preview && (auth()->id() == $liste->besitzer or auth()->user()->can('edit terminliste'));
        $ansicht = app(\App\Services\App\ListenAnsicht::class);
        $gesamt = $liste->eintragungen->count();
        $feld = 'px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-sky-400 text-sm';
        $feldStyle = 'border: 1px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);';
    @endphp
    <div class="w-full max-w-4xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-5"
         x-data="{ neuerEintrag: false, offen: null, zeigeVergeben: {{ $canManage ? 'true' : 'false' }} }">

        @include('listen.partials.kopf', [
            'zaehler' => $gesamt > 0 ? $offen->count().' von '.$gesamt.' Einträgen offen' : null,
            'werkzeuge' => 'listen.partials.eintrag_werkzeuge',
        ])

        <div class="space-y-5" @if($preview) inert @endif>

            {{-- Eigene Einträge --}}
            @if($eigeneEintraege->isNotEmpty())
                <section aria-labelledby="eigene-eintraege" class="rounded-lg p-4 border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20">
                    <h2 id="eigene-eintraege" class="text-base font-bold mb-3 flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        {{ $eigeneEintraege->count() > 1 ? 'Ihre Einträge' : 'Ihr Eintrag' }}
                    </h2>
                    <ul class="space-y-2 mb-0 pl-0 list-none">
                        @foreach($eigeneEintraege as $eintrag)
                            <li class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-lg p-3" style="background: var(--color-card-bg);">
                                @include('listen.partials.eintrag_text', ['zeigeKind' => true, 'zeigeName' => count(auth()->user()->familyUserIds()) > 1])
                                <form method="post" action="{{ url('listen/eintragungen/'.$eintrag->id) }}" class="mb-0 flex-shrink-0"
                                      onsubmit="return confirm(@js('Eintrag „'.$eintrag->eintragung.'“ wirklich austragen?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                                        <i class="fas fa-times" aria-hidden="true"></i> Austragen
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($gesperrt && ! $canManage)
                <div class="rounded-lg px-4 py-3 flex items-start gap-3 bg-sky-50 text-sky-900 border border-sky-200 dark:bg-sky-900/20 dark:text-sky-200 dark:border-sky-800" role="status">
                    <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                    <p class="mb-0 text-sm">
                        {{ $liste->bookingPerChild() ? 'Für alle Ihre Kinder ist bereits ein Eintrag vorhanden.' : 'Sie haben sich bereits eingetragen.' }}
                        Zum Ändern bitte zuerst austragen.
                    </p>
                </div>
            @endif

            {{-- Eigenen Eintrag hinzufügen --}}
            @if($darfEintragHinzufuegen)
                <div>
                    @unless($canManage)
                        <button type="button" @click="neuerEintrag = !neuerEintrag" x-show="!neuerEintrag" :aria-expanded="neuerEintrag.toString()"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg font-semibold"
                                style="border: 2px dashed var(--color-card-border); color: var(--color-text-primary);">
                            <i class="fas fa-plus" aria-hidden="true"></i> Eigenen Eintrag hinzufügen
                        </button>
                    @endunless
                    <form x-show="neuerEintrag" x-cloak x-transition
                          action="{{ url('/listen/' . $liste->id . '/eintragungen/') }}" method="post"
                          class="rounded-lg p-4 space-y-3" style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
                        @csrf
                        <div>
                            <label for="neuer-eintrag" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">
                                {{ $canManage ? 'Neuer Eintrag' : 'Ihr Eintrag' }}
                            </label>
                            <input type="text" id="neuer-eintrag" name="eintragung" maxlength="100" required
                                   class="w-full {{ $feld }}" style="{{ $feldStyle }}" placeholder="z. B. Kuchen, Salat, Aufbau-Hilfe …"
                                   x-effect="if (neuerEintrag) $nextTick(() => $el.focus())">
                        </div>
                        @unless($canManage)
                            @include('listen.partials.child_select', ['selectSuffix' => 'neu'])
                        @endunless
                        <div class="flex flex-wrap gap-2 justify-end">
                            <button type="button" @click="neuerEintrag = false" class="px-4 py-2 rounded-lg font-medium"
                                    style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">Abbrechen</button>
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-white bg-emerald-600 hover:bg-emerald-700">
                                <i class="fas fa-check" aria-hidden="true"></i> Speichern
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div id="export" class="space-y-5">
                {{-- Noch offen --}}
                <section aria-labelledby="offen-titel" class="rounded-lg overflow-hidden shadow-sm" style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
                    <h2 id="offen-titel" class="flex items-center justify-between gap-2 px-4 py-3 mb-0 text-base font-bold"
                        style="background: var(--color-surface-subtle); border-bottom: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                        <span><i class="fas fa-circle-notch mr-1 text-sky-600" aria-hidden="true"></i> Noch offen</span>
                        <span class="text-sm font-medium" style="color: var(--color-text-secondary);">{{ $offen->count() }}</span>
                    </h2>
                    @if($offen->isEmpty())
                        <p class="px-4 py-5 text-center mb-0" style="color: var(--color-text-secondary);">
                            @if($gesamt === 0)
                                Noch keine Einträge.
                            @else
                                Alle Einträge sind vergeben.
                                @if(! $canManage && $liste->ersteller)
                                    Bei Fragen wenden Sie sich an {{ $liste->ersteller->name }}.
                                @endif
                            @endif
                        </p>
                    @else
                        <ul class="mb-0 pl-0 list-none divide-y" style="border-color: var(--color-card-border);">
                            @foreach($offen as $eintrag)
                                <li class="px-4 py-3" style="border-color: var(--color-card-border);">
                                    <div class="flex items-center gap-3">
                                        <p class="flex-1 min-w-0 font-medium break-words mb-0" style="color: var(--color-text-primary);">{{ $eintrag->eintragung }}</p>
                                        @unless($gesperrt)
                                            <button type="button" x-show="offen !== {{ $eintrag->id }}"
                                                    @click="offen = {{ $eintrag->id }}"
                                                    class="flex-shrink-0 inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold rounded-lg border-2 border-sky-500 text-sky-700 dark:text-sky-300 hover:bg-sky-50 dark:hover:bg-sky-900/30"
                                                    aria-label="„{{ $eintrag->eintragung }}“ übernehmen">
                                                <i class="fas fa-hand-paper" aria-hidden="true"></i> Übernehmen
                                            </button>
                                        @endunless
                                        @if($canManage)
                                            @include('listen.partials.eintrag_menu')
                                        @endif
                                    </div>
                                    @unless($gesperrt)
                                        <form x-show="offen === {{ $eintrag->id }}" x-cloak x-transition
                                              method="post" action="{{ url('listen/eintragungen/'.$eintrag->id) }}"
                                              class="mt-3 rounded-lg p-3 space-y-3" style="background: var(--color-surface-subtle);">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label for="bemerkung-{{ $eintrag->id }}" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">
                                                    Bemerkung <span class="font-normal" style="color: var(--color-text-secondary);">(optional)</span>
                                                </label>
                                                <input type="text" id="bemerkung-{{ $eintrag->id }}" name="bemerkung" maxlength="255"
                                                       class="w-full {{ $feld }}" style="{{ $feldStyle }}"
                                                       placeholder="z. B. Apfelkuchen">
                                            </div>
                                            @include('listen.partials.child_select', ['selectSuffix' => $eintrag->id])
                                            <div class="flex flex-wrap gap-2 justify-end">
                                                <button type="button" @click="offen = null" class="px-4 py-2 rounded-lg font-medium text-sm"
                                                        style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">Abbrechen</button>
                                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm text-white bg-emerald-600 hover:bg-emerald-700">
                                                    <i class="fas fa-check" aria-hidden="true"></i> Verbindlich übernehmen
                                                </button>
                                            </div>
                                        </form>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Bereits vergeben --}}
                @if($vergeben->isNotEmpty())
                    <section aria-labelledby="vergeben-titel" class="rounded-lg overflow-hidden shadow-sm" style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
                        <h2 id="vergeben-titel" class="mb-0">
                            <button type="button" @click="zeigeVergeben = !zeigeVergeben" :aria-expanded="zeigeVergeben.toString()" aria-controls="vergeben-liste"
                                    class="w-full flex items-center justify-between gap-2 px-4 py-3 text-base font-bold text-left"
                                    style="background: var(--color-surface-subtle); color: var(--color-text-primary);">
                                <span><i class="fas fa-lock mr-1" style="color: var(--color-text-secondary);" aria-hidden="true"></i> Bereits vergeben</span>
                                <span class="inline-flex items-center gap-2 text-sm font-medium" style="color: var(--color-text-secondary);">
                                    {{ $vergeben->count() }}
                                    <i class="fas fa-chevron-down transition-transform" :class="zeigeVergeben && 'rotate-180'" aria-hidden="true"></i>
                                </span>
                            </button>
                        </h2>
                        <ul id="vergeben-liste" x-show="zeigeVergeben" @unless($canManage) x-cloak @endunless
                            class="mb-0 pl-0 list-none divide-y" style="border-top: 1px solid var(--color-card-border);">
                            @foreach($vergeben as $eintrag)
                                @php $namenSichtbar = $ansicht->namesVisible(auth()->user(), $liste, $preview); @endphp
                                <li class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-2" style="border-color: var(--color-card-border);">
                                    @include('listen.partials.eintrag_text', ['zeigeKind' => $namenSichtbar, 'zeigeName' => false])
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if($namenSichtbar && $eintrag->user)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                                                  style="background: var(--color-badge-termin-bg); color: var(--color-badge-termin-text);">
                                                <i class="fas fa-user" aria-hidden="true"></i> {{ $eintrag->user->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                                                  style="background: var(--color-surface-subtle); color: var(--color-text-secondary); border: 1px solid var(--color-card-border);">
                                                <i class="fas fa-lock" aria-hidden="true"></i> vergeben
                                            </span>
                                        @endif
                                        @if($canManage)
                                            <form method="post" action="{{ url('listen/eintragungen/'.$eintrag->id) }}" class="mb-0"
                                                  onsubmit="return confirm(@js('Eintrag „'.$eintrag->eintragung.'“ wieder freigeben? '.($eintrag->user?->name ?? 'Die Person').' wird benachrichtigt.'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/30">
                                                    <i class="fas fa-unlock" aria-hidden="true"></i> Freigeben
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection

@if(! $preview)
    @push('js')
        <script src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
        <script>
            function generatePDF() {
                const element = document.getElementById('export');
                html2pdf().from(element).save();
            }
        </script>
    @endpush
@endif
