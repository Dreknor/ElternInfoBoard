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
                <p class="text-xs text-white/70 mb-0 mt-0.5">Snippet eines Drittanbieters einbinden, z.&nbsp;B. ein Chatbot-Widget</p>
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
                        Das Snippet läuft auf jeder Seite mit den Rechten der angemeldeten Person und kann alles lesen,
                        was diese sieht (Nachrichten, Kinderdaten, Krankmeldungen).</p>
                    <p class="mb-0">Klären Sie vor der Aktivierung den Datenschutz (Auftragsverarbeitung, ggf. Hinweis in der
                        Datenschutzerklärung), wenn der Anbieter Daten der Eltern verarbeitet.</p>
                </div>
            </div>

            @if($errors->any())
                <div class="rounded-lg p-4 bg-red-50 border border-red-300 text-red-900 dark:bg-red-900/30 dark:text-red-100 text-sm">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('settings.custom-scripts.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" class="w-4 h-4"
                           @checked(old('active', $setting->active))>
                    <span class="font-semibold">Snippet ausgeben</span>
                </label>

                <div>
                    <label for="snippet" class="block font-semibold mb-1">Snippet (HTML / JavaScript)</label>
                    <textarea id="snippet" name="snippet" rows="12" spellcheck="false" maxlength="{{ $maxLength }}"
                              class="no-tinymce w-full rounded-lg p-3 font-mono text-sm"
                              style="background-color: var(--color-body-bg); border: 1px solid var(--color-card-border); color: var(--color-text-primary);"
                              placeholder="&lt;script src=&quot;https://chatbot.example/widget.js&quot; data-id=&quot;…&quot; defer&gt;&lt;/script&gt;">{{ old('snippet', $setting->snippet) }}</textarea>
                    <p class="text-xs mt-1 opacity-75">Den Code so einfügen, wie ihn der Anbieter liefert, inklusive
                        <code>&lt;script&gt;</code>-Tags. Höchstens {{ number_format($maxLength, 0, ',', '.') }} Zeichen.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="position" class="block font-semibold mb-1">Ausgabeort</label>
                        <select id="position" name="position" class="w-full rounded-lg p-2"
                                style="background-color: var(--color-body-bg); border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                            <option value="body" @selected(old('position', $setting->position) === 'body')>Ende des Seiteninhalts (empfohlen)</option>
                            <option value="head" @selected(old('position', $setting->position) === 'head')>Im Seitenkopf (&lt;head&gt;)</option>
                        </select>
                        <p class="text-xs mt-1 opacity-75">Die meisten Chatbot-Anbieter empfehlen das Ende des Seiteninhalts.</p>
                    </div>

                    <div>
                        <span class="block font-semibold mb-1">Sichtbar für</span>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="show_for_guests" value="0">
                            <input type="checkbox" name="show_for_guests" value="1" class="w-4 h-4"
                                   @checked(old('show_for_guests', $setting->show_for_guests))>
                            <span>Auch auf Anmelde- und Passwortseiten (ohne Anmeldung)</span>
                        </label>
                        <p class="text-xs mt-1 opacity-75">Ohne Haken erscheint das Snippet nur für angemeldete Nutzer.</p>
                    </div>
                </div>

                <fieldset class="rounded-lg p-4 space-y-4" style="border: 1px solid var(--color-card-border);">
                    <legend class="px-1 font-semibold"><i class="fas fa-mobile-alt mr-1"></i> Eltern-App</legend>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="app_enabled" value="0">
                        <input type="checkbox" name="app_enabled" value="1" class="w-4 h-4"
                               @checked(old('app_enabled', $setting->app_enabled))>
                        <span>Chat auch in der App anbieten</span>
                    </label>
                    <p class="text-xs opacity-75 mb-0">Die App zeigt dann einen Button, der den Chat im In-App-Browser öffnet:
                        entweder die Chat-Seite dieser Instanz (<a href="{{ route('chat') }}" target="_blank" class="underline">{{ url('chat') }}</a>, angemeldet)
                        oder die unten eingetragene Chat-Seite des Anbieters.</p>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="app_label" class="block font-semibold mb-1">Beschriftung des Buttons</label>
                            <input type="text" id="app_label" name="app_label" maxlength="30"
                                   value="{{ old('app_label', $setting->app_label) }}" placeholder="Chat"
                                   class="w-full rounded-lg p-2"
                                   style="background-color: var(--color-body-bg); border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                        </div>
                        <div>
                            <label for="app_url" class="block font-semibold mb-1">Chat-Seite des Anbieters (optional)</label>
                            <input type="url" id="app_url" name="app_url" maxlength="500"
                                   value="{{ old('app_url', $setting->app_url) }}" placeholder="https://…"
                                   class="w-full rounded-lg p-2"
                                   style="background-color: var(--color-body-bg); border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                            <p class="text-xs mt-1 opacity-75">Leer lassen, um die Chat-Seite dieser Instanz zu verwenden.</p>
                        </div>
                    </div>
                </fieldset>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <p class="text-xs opacity-75 mb-0">
                        @if($setting->updated_at)
                            Zuletzt geändert am {{ \Carbon\Carbon::parse($setting->updated_at)->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            @if($updatedBy) von {{ $updatedBy->name }} @endif
                        @else
                            Noch nicht gespeichert.
                        @endif
                    </p>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white" style="background-color: var(--color-primary);">
                        <i class="fas fa-save mr-1"></i> Speichern
                    </button>
                </div>
            </form>

            <p class="text-xs opacity-75 mb-0">
                Notaus für den Betreiber: <code>CUSTOM_SCRIPTS_ENABLED=false</code> in der <code>.env</code> schaltet die Ausgabe
                sofort ab. Das Snippet erscheint nicht in der Kiosk-Ansicht und der Anwesenheitsliste; in der App nur über den Chat-Button.
            </p>
        </div>
    </div>
</div>
@endsection
