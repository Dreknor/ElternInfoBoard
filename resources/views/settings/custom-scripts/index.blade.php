@extends('layouts.app')

@section('title') - Eigene Skripte @endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="rounded-xl shadow-lg overflow-hidden" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);">

        <div class="px-6 py-4 flex items-center gap-4" style="background-color: var(--color-primary); border-bottom: 3px solid var(--color-primary-dark);">
            <div class="flex items-center justify-center w-10 h-10 bg-white/20 rounded-xl flex-shrink-0">
                <i class="fas fa-code text-white text-xl"></i>
            </div>
            <div class="flex-1">
                <h5 class="text-xl font-bold text-white mb-0">Eigene Skripte</h5>
                <p class="text-xs text-white/70 mb-0 mt-0.5">Snippets einbinden, z.&nbsp;B. ein Chatbot-Widget oder einen Gag in der Anwesenheitsliste</p>
            </div>
            <a href="{{ url('settings') }}" class="text-sm text-white/80 hover:text-white">
                <i class="fas fa-arrow-left mr-1"></i> Einstellungen
            </a>
        </div>

        <div class="p-6 space-y-6" style="color: var(--color-text-primary);">

            <div class="rounded-lg p-4 flex gap-3 bg-amber-50 border border-amber-300 text-amber-900 dark:bg-amber-900/30 dark:text-amber-100">
                <i class="fas fa-exclamation-triangle text-xl mt-0.5"></i>
                <div class="text-sm space-y-1">
                    <p class="mb-0"><strong>Nur Code aus vertrauenswürdiger Quelle einfügen.</strong>
                        Ein Skript läuft mit den Rechten der angemeldeten Person und kann alles lesen,
                        was diese sieht (Nachrichten, Kinderdaten, Krankmeldungen).</p>
                    <p class="mb-0">Klären Sie vor der Aktivierung den Datenschutz (Auftragsverarbeitung, ggf. Hinweis in der
                        Datenschutzerklärung), wenn ein Anbieter Daten der Eltern verarbeitet.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="rounded-lg p-4 bg-red-50 border border-red-300 text-red-900 dark:bg-red-900/30 dark:text-red-100 text-sm">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h6 class="text-lg font-semibold mb-0">Skripte</h6>
                <a href="{{ route('settings.custom-scripts.create') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium text-white" style="background-color: var(--color-primary);">
                    <i class="fas fa-plus mr-1"></i> Neues Skript
                </a>
            </div>

            @if($scripts->isEmpty())
                <p class="text-sm opacity-75 mb-0">Noch keine Skripte hinterlegt.</p>
            @else
                <div class="overflow-x-auto rounded-lg" style="border: 1px solid var(--color-card-border);">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="text-left" style="background-color: var(--color-body-bg);">
                            <th class="p-3 font-semibold">Aktiv</th>
                            <th class="p-3 font-semibold">Name</th>
                            <th class="p-3 font-semibold">Zielgruppe</th>
                            <th class="p-3 font-semibold">Seiten</th>
                            <th class="p-3 font-semibold">Ausgabeort</th>
                            <th class="p-3 font-semibold text-right">Reihenfolge</th>
                            <th class="p-3"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($scripts as $script)
                            <tr style="border-top: 1px solid var(--color-card-border);">
                                <td class="p-3 align-top">
                                    <form method="post" action="{{ route('settings.custom-scripts.toggle', $script) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2 py-1 rounded-md text-xs font-medium {{ $script->active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}"
                                                title="{{ $script->active ? 'Deaktivieren' : 'Aktivieren' }}">
                                            <i class="fas {{ $script->active ? 'fa-toggle-on' : 'fa-toggle-off' }} mr-1"></i>{{ $script->active ? 'aktiv' : 'aus' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="p-3 align-top">
                                    <div class="font-semibold">{{ $script->name }}</div>
                                    @if($script->description)
                                        <div class="text-xs opacity-75">{{ $script->description }}</div>
                                    @endif
                                    @if($script->use_on_chat)
                                        <div class="text-xs mt-1"><i class="fas fa-comments mr-1"></i>Chat-Seite</div>
                                    @endif
                                    @if($script->disabled_reason)
                                        <div class="text-xs mt-1 text-red-700 dark:text-red-300"><i class="fas fa-exclamation-circle mr-1"></i>{{ $script->disabled_reason }}</div>
                                    @endif
                                </td>
                                <td class="p-3 align-top">{{ $script->audienceLabel() }}</td>
                                <td class="p-3 align-top break-all">{{ $script->pageLabel() }}</td>
                                <td class="p-3 align-top">{{ $script->position === 'head' ? 'Seitenkopf' : 'Seitenende' }}</td>
                                <td class="p-3 align-top text-right">{{ $script->sort_order }}</td>
                                <td class="p-3 align-top text-right whitespace-nowrap">
                                    <a href="{{ route('settings.custom-scripts.edit', $script) }}" class="px-2 py-1 text-sm" title="Bearbeiten">
                                        <i class="fas fa-edit"></i><span class="sr-only">Bearbeiten</span>
                                    </a>
                                    <form method="post" action="{{ route('settings.custom-scripts.destroy', $script) }}" class="inline"
                                          onsubmit="return confirm('Skript „{{ addslashes($script->name) }}“ wirklich löschen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 text-sm text-red-600" title="Löschen">
                                            <i class="fas fa-trash"></i><span class="sr-only">Löschen</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <form method="post" action="{{ route('settings.custom-scripts.app') }}">
                @csrf
                @method('PUT')
                <fieldset class="rounded-lg p-4 space-y-4" style="border: 1px solid var(--color-card-border);">
                    <legend class="px-1 font-semibold"><i class="fas fa-mobile-alt mr-1"></i> Eltern-App</legend>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="app_enabled" value="0">
                        <input type="checkbox" name="app_enabled" value="1" class="w-4 h-4"
                               @checked(old('app_enabled', $setting->app_enabled))>
                        <span>Chat auch in der App anbieten</span>
                    </label>
                    <p class="text-xs opacity-75 mb-0">Die App zeigt dann einen Button, der die Chat-Seite dieser Instanz
                        (<a href="{{ route('chat') }}" target="_blank" class="underline">{{ url('chat') }}</a>) angemeldet im In-App-Browser öffnet.
                        Die Chat-Seite lädt alle aktiven Skripte mit „Auf der Chat-Seite verwenden“, deren Zielgruppe passt.
                        Der Button erscheint nur, wenn für die Person mindestens ein solches Skript greift.</p>

                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[12rem]">
                            <label for="app_label" class="block font-semibold mb-1">Beschriftung des Buttons</label>
                            <input type="text" id="app_label" name="app_label" maxlength="30"
                                   value="{{ old('app_label', $setting->app_label) }}" placeholder="Chatbot"
                                   class="w-full rounded-lg p-2"
                                   style="background-color: var(--color-body-bg); border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white" style="background-color: var(--color-primary);">
                            <i class="fas fa-save mr-1"></i> Speichern
                        </button>
                    </div>
                </fieldset>
            </form>

            <p class="text-xs opacity-75 mb-0">
                Notaus für den Betreiber: <code>CUSTOM_SCRIPTS_ENABLED=false</code> in der <code>.env</code> schaltet alle Skripte
                sofort ab; gespeicherte Skripte bleiben erhalten. Skripte erscheinen in der normalen Ansicht, auf Anmeldeseiten,
                in der Anwesenheitsliste, der Kiosk-Ansicht und im Schülerbereich – jeweils nur, wenn Zielgruppe und Seitenregel passen.
            </p>
        </div>
    </div>
</div>
@endsection
