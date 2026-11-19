@extends('layouts.app')
@section('title') - Listen @endsection

@section('content')
    <div class="w-full max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-6">

        {{-- Kopf --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold flex items-center gap-3 mb-0" style="color: var(--color-text-primary);">
                <i class="fas fa-list" aria-hidden="true"></i>
                Listen
            </h1>
            @can('create terminliste')
                <a href="{{ url('listen/create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg hover:opacity-90 transition-opacity"
                   style="background: var(--color-widget-success-from);">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Neue Liste
                </a>
            @endcan
        </div>

        {{-- 1. Ihre Buchungen --}}
        @if($buchungen->isNotEmpty())
            <section aria-labelledby="buchungen-titel">
                <h2 id="buchungen-titel" class="text-lg font-semibold flex items-center gap-2 mb-3" style="color: var(--color-text-primary);">
                    <i class="fas fa-check-circle text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                    Ihre Buchungen
                    <span class="text-sm font-normal" style="color: var(--color-text-secondary);">({{ $buchungen->count() }})</span>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($buchungen as $eintrag)
                        @include('listen.cards.buchung', ['liste' => $eintrag['liste'], 'buchung' => $eintrag['buchung']])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 2. Terminlisten / 3. Eintragslisten --}}
        @if($terminKarten->isEmpty() && $eintragKarten->isEmpty())
            <div class="rounded-lg p-6 text-center" style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
                <i class="fas fa-inbox text-3xl mb-2" style="color: var(--color-text-secondary);" aria-hidden="true"></i>
                <p class="text-lg mb-0" style="color: var(--color-text-secondary);">Aktuell gibt es keine Listen für Sie.</p>
            </div>
        @endif

        @foreach([
            ['id' => 'terminlisten', 'titel' => 'Terminlisten', 'icon' => 'fa-calendar-check', 'karten' => $terminKarten],
            ['id' => 'eintragslisten', 'titel' => 'Eintragslisten', 'icon' => 'fa-list-ul', 'karten' => $eintragKarten],
        ] as $abschnitt)
            @if($abschnitt['karten']->isNotEmpty())
                <section aria-labelledby="{{ $abschnitt['id'] }}-titel">
                    <h2 id="{{ $abschnitt['id'] }}-titel" class="text-lg font-semibold flex items-center gap-2 mb-3" style="color: var(--color-text-primary);">
                        <i class="fas {{ $abschnitt['icon'] }}" aria-hidden="true"></i>
                        {{ $abschnitt['titel'] }}
                        <span class="text-sm font-normal" style="color: var(--color-text-secondary);">({{ $abschnitt['karten']->count() }})</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($abschnitt['karten'] as $karte)
                            @include('listen.cards.liste', $karte)
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        {{-- 4. Verwaltung --}}
        @if($verwaltungKarten->isNotEmpty() || auth()->user()->can('create terminliste') || auth()->user()->can('edit terminliste'))
            <section aria-labelledby="verwaltung-titel" class="pt-2" style="border-top: 1px solid var(--color-card-border);">
                <h2 id="verwaltung-titel" class="text-lg font-semibold flex items-center gap-2 mb-1" style="color: var(--color-text-primary);">
                    <i class="fas fa-cog" aria-hidden="true"></i>
                    Verwaltung
                    <span class="text-sm font-normal" style="color: var(--color-text-secondary);">({{ $verwaltungKarten->count() }})</span>
                </h2>
                <p class="text-sm mb-3" style="color: var(--color-text-secondary);">
                    {{ auth()->user()->can('edit terminliste') ? 'Alle aktuellen Listen, auch ausgeblendete.' : 'Ihre eigenen Listen, auch ausgeblendete.' }}
                </p>

                @if($verwaltungKarten->isEmpty())
                    <div class="rounded-lg p-4 text-center" style="background: var(--color-card-bg); border: 1px dashed var(--color-card-border);">
                        <p class="mb-0" style="color: var(--color-text-secondary);">Sie haben noch keine aktuellen Listen angelegt.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($verwaltungKarten as $karte)
                            @include('listen.cards.verwaltung', $karte)
                        @endforeach
                    </div>
                @endif

                @can('edit terminliste')
                    <form method="POST" action="{{ url('listen/search') }}" class="mt-6 rounded-lg p-4" style="background: var(--color-card-bg); border: 1px solid var(--color-card-border);">
                        @csrf
                        <label for="listen-suche" class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                            <i class="fas fa-history mr-1" aria-hidden="true"></i>
                            Abgelaufene Listen durchsuchen
                        </label>
                        <div class="flex gap-2">
                            <input type="text" id="listen-suche" name="query"
                                   class="flex-1 min-w-0 px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-amber-400"
                                   style="border: 1px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);"
                                   placeholder="Listenname …">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                Suchen
                            </button>
                        </div>
                    </form>
                @endcan
            </section>
        @endif
    </div>
@endsection
