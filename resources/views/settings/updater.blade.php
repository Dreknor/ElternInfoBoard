@extends('layouts.app')

@section('content')
@php
    $formatDate = fn ($date) => $date ? \Carbon\Carbon::parse($date)->timezone(config('app.timezone'))->format('d.m.Y H:i') : '–';
    $busy = in_array($state['status'] ?? 'idle', ['requested', 'running']);
@endphp
<div class="container-fluid px-4 py-3"
     x-data="updaterStatus(@js($state), @js($log), @js($maintenance))"
     x-init="init()">

    <div class="rounded-xl shadow-lg overflow-hidden" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);">

        <div class="px-6 py-4 flex items-center gap-4" style="background-color: var(--color-primary); border-bottom: 3px solid var(--color-primary-dark);">
            <div class="flex items-center justify-center w-10 h-10 bg-white/20 rounded-xl flex-shrink-0">
                <i class="fas fa-cloud-download-alt text-white text-xl"></i>
            </div>
            <div class="flex-1">
                <h5 class="text-xl font-bold text-white mb-0">Online-Update</h5>
                <p class="text-xs text-white/70 mb-0 mt-0.5">Neue Versionen aus dem Git-Repository einspielen</p>
            </div>
            <a href="{{ url('settings') }}" class="text-sm text-white/80 hover:text-white">
                <i class="fas fa-arrow-left mr-1"></i> Einstellungen
            </a>
        </div>

        <div class="p-6 space-y-6" style="color: var(--color-text-primary);">

            {{-- Wartungsmodus --}}
            <div x-show="maintenance" x-cloak class="rounded-lg p-4 flex flex-wrap items-center gap-3 bg-amber-50 border border-amber-300 text-amber-900 dark:bg-amber-900/30 dark:text-amber-100">
                <i class="fas fa-hard-hat text-xl"></i>
                <div class="flex-1 text-sm">
                    <strong>Die Anwendung befindet sich im Wartungsmodus.</strong>
                    Nur Sie sehen die Seite weiterhin (Bypass-Cookie).
                </div>
                <form method="post" action="{{ route('updater.up') }}" x-show="state.status !== 'running'"
                      onsubmit="return confirm('Wartungsmodus wirklich beenden? Bei einem fehlgeschlagenen Update ist die Installation evtl. unvollständig.')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-amber-600 text-white hover:bg-amber-700">
                        Wartungsmodus beenden
                    </button>
                </form>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">

                {{-- Installierte Version / Prüfung --}}
                <section class="rounded-lg p-5" style="border: 1px solid var(--color-card-border);">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="font-semibold text-base mb-0">Installierte Version</h6>
                        <form method="post" action="{{ route('updater.check') }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium text-white" style="background-color: var(--color-primary);" :disabled="busy">
                                <i class="fas fa-sync-alt mr-1"></i> Jetzt prüfen
                            </button>
                        </form>
                    </div>

                    @if(! $check)
                        <p class="text-sm opacity-75">Es wurde noch nicht nach Updates gesucht.</p>
                    @elseif($check['error'])
                        <p class="text-sm text-red-600">Prüfung fehlgeschlagen: {{ $check['error'] }}</p>
                    @else
                        <dl class="text-sm grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1.5 mb-0">
                            <dt class="opacity-70">Branch</dt>
                            <dd class="mb-0 font-mono">{{ $check['remote'] }}/{{ $check['branch'] }}</dd>
                            <dt class="opacity-70">Commit</dt>
                            <dd class="mb-0"><span class="font-mono">{{ $check['current']['short'] }}</span> vom {{ $formatDate($check['current']['date']) }}</dd>
                            <dt class="opacity-70">Beschreibung</dt>
                            <dd class="mb-0">{{ $check['current']['subject'] }}</dd>
                            <dt class="opacity-70">Letzte Prüfung</dt>
                            <dd class="mb-0">{{ $formatDate($check['checked_at']) }}</dd>
                        </dl>

                        @if($check['current_branch'] !== $check['branch'])
                            <p class="mt-3 text-sm text-red-600 mb-0">
                                Ausgecheckt ist „{{ $check['current_branch'] }}“, konfiguriert ist „{{ $check['branch'] }}“ (UPDATER_BRANCH). Ein Update ist so nicht möglich.
                            </p>
                        @endif
                        @if($check['fetch_error'])
                            <p class="mt-3 text-sm text-amber-700 mb-0">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Das Repository konnte vom Webserver nicht abgefragt werden; angezeigt wird der zuletzt bekannte Stand.
                                <span class="block text-xs opacity-75 mt-1 font-mono break-all">{{ Str::limit($check['fetch_error'], 300) }}</span>
                            </p>
                        @endif
                    @endif
                </section>

                {{-- Verfügbares Update --}}
                <section class="rounded-lg p-5" style="border: 1px solid var(--color-card-border);">
                    @if($check && ! $check['error'] && $check['behind'] > 0)
                        <h6 class="font-semibold text-base mb-3">
                            <i class="fas fa-gift mr-1" style="color: var(--color-primary);"></i>
                            {{ $check['behind'] }} neue(r) Commit(s) verfügbar
                        </h6>
                        <p class="text-sm mb-3">
                            Neuester Stand: <span class="font-mono">{{ $check['latest']['short'] }}</span> vom {{ $formatDate($check['latest']['date']) }}
                        </p>
                        <ul class="text-sm space-y-1 mb-3">
                            @if($check['migrations'])
                                <li><i class="fas fa-database w-4 mr-1 text-amber-600"></i> {{ $check['migrations'] }} Datenbank-Migration(en)</li>
                            @endif
                            @if($check['dependencies'])
                                <li><i class="fas fa-box w-4 mr-1 opacity-70"></i> Geänderte PHP-Abhängigkeiten (composer install)</li>
                            @endif
                            @if($check['assets'])
                                <li><i class="fas fa-paint-brush w-4 mr-1 opacity-70"></i> Frontend-Assets werden neu gebaut</li>
                            @endif
                            @if($check['ahead'])
                                <li class="text-red-600"><i class="fas fa-code-branch w-4 mr-1"></i> {{ $check['ahead'] }} lokale(r) Commit(s) – kein Fast-Forward möglich</li>
                            @endif
                            @if($check['local_changes'])
                                <li class="text-amber-700"><i class="fas fa-exclamation-triangle w-4 mr-1"></i> Lokal geänderte Dateien: {{ implode(', ', $check['local_changes']) }}</li>
                            @endif
                        </ul>
                        <form method="post" action="{{ route('updater.start') }}"
                              onsubmit="return confirm('Update jetzt installieren? Die Anwendung ist währenddessen für alle Nutzer im Wartungsmodus.')">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-50" style="background-color: var(--color-primary);" :disabled="busy">
                                <i class="fas fa-download mr-1"></i> Update installieren
                            </button>
                        </form>
                    @else
                        <h6 class="font-semibold text-base mb-3">
                            <i class="fas fa-check-circle mr-1 text-green-600"></i> Keine neuen Versionen
                        </h6>
                        <p class="text-sm opacity-75">
                            Die Installation entspricht dem zuletzt geprüften Stand des Repositorys.
                            Nach einem fehlgeschlagenen Update lassen sich alle Schritte erneut ausführen
                            (Abhängigkeiten, Assets, Migrationen, Caches).
                        </p>
                        <form method="post" action="{{ route('updater.start') }}"
                              onsubmit="return confirm('Alle Update-Schritte erneut ausführen? Die Anwendung ist währenddessen im Wartungsmodus.')">
                            @csrf
                            <input type="hidden" name="full" value="1">
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium border disabled:opacity-50" style="border-color: var(--color-card-border);" :disabled="busy">
                                <i class="fas fa-redo mr-1"></i> Update-Schritte erneut ausführen
                            </button>
                        </form>
                    @endif
                </section>
            </div>

            @if($check && ! $check['error'] && count($check['commits']))
                <section class="rounded-lg p-5" style="border: 1px solid var(--color-card-border);">
                    <h6 class="font-semibold text-base mb-3">Änderungen</h6>
                    <ul class="text-sm divide-y" style="border-color: var(--color-card-border);">
                        @foreach($check['commits'] as $commit)
                            <li class="py-1.5 flex gap-3">
                                <span class="font-mono opacity-60 flex-shrink-0">{{ $commit['short'] }}</span>
                                <span class="flex-1">{{ $commit['subject'] }}</span>
                                <span class="opacity-60 flex-shrink-0 hidden sm:inline">{{ $formatDate($commit['date']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Fortschritt / letzter Lauf --}}
            <section class="rounded-lg p-5" style="border: 1px solid var(--color-card-border);" x-show="state.status !== 'idle'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h6 class="font-semibold text-base mb-0">
                        <span x-show="state.status === 'requested'"><i class="fas fa-hourglass-half mr-1 text-amber-600"></i> Update angefordert – wartet auf den Scheduler</span>
                        <span x-show="state.status === 'running'"><i class="fas fa-spinner fa-spin mr-1" style="color: var(--color-primary);"></i> Update läuft …</span>
                        <span x-show="state.status === 'success'"><i class="fas fa-check-circle mr-1 text-green-600"></i> Letztes Update erfolgreich</span>
                        <span x-show="state.status === 'failed'"><i class="fas fa-times-circle mr-1 text-red-600"></i> Letztes Update fehlgeschlagen</span>
                    </h6>
                    <form method="post" action="{{ route('updater.cancel') }}" x-show="state.status === 'requested'">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium border" style="border-color: var(--color-card-border);">Anforderung zurücknehmen</button>
                    </form>
                    <button type="button" x-show="finishedNow" @click="window.location.reload()" class="px-3 py-1.5 rounded-lg text-sm font-medium text-white" style="background-color: var(--color-primary);">
                        Seite neu laden
                    </button>
                </div>

                <p class="text-xs opacity-70 mb-3">
                    <span x-show="state.requested_by">Angefordert von <span x-text="state.requested_by"></span><span x-show="state.requested_at"> am <span x-text="formatDate(state.requested_at)"></span></span>.</span>
                    <span x-show="state.started_at"> Gestartet <span x-text="formatDate(state.started_at)"></span>.</span>
                    <span x-show="state.finished_at"> Beendet <span x-text="formatDate(state.finished_at)"></span>.</span>
                </p>

                <p x-show="state.status === 'requested' && waitingTooLong" x-cloak class="text-sm text-amber-700 mb-3">
                    Das Update wurde noch nicht gestartet. Läuft der Cron-Job für <code>php artisan schedule:run</code>?
                    Alternativ auf dem Server ausführen: <code>php artisan updater:run</code>
                </p>
                <p x-show="state.error" x-cloak class="text-sm text-red-600 mb-3" x-text="state.error"></p>
                <p x-show="state.status === 'success' && state.message" x-cloak class="text-sm text-green-700 mb-3" x-text="state.message"></p>
                <p x-show="connectionLost" x-cloak class="text-sm text-amber-700 mb-3">
                    <i class="fas fa-plug mr-1"></i> Server vorübergehend nicht erreichbar – während des Updates normal. Es wird weiter abgefragt …
                </p>

                <ol class="text-sm space-y-1.5 mb-4" x-show="state.steps">
                    <template x-for="(step, key) in state.steps" :key="key">
                        <li class="flex items-start gap-2">
                            <span class="w-5 text-center flex-shrink-0">
                                <i x-show="step.status === 'pending'" class="far fa-circle opacity-40"></i>
                                <i x-show="step.status === 'running'" class="fas fa-spinner fa-spin" style="color: var(--color-primary);"></i>
                                <i x-show="step.status === 'done'" class="fas fa-check text-green-600"></i>
                                <i x-show="step.status === 'skipped'" class="fas fa-minus opacity-50"></i>
                                <i x-show="step.status === 'warning'" class="fas fa-exclamation-triangle text-amber-600"></i>
                                <i x-show="step.status === 'failed'" class="fas fa-times text-red-600"></i>
                            </span>
                            <span class="flex-1">
                                <span x-text="step.label" :class="step.status === 'pending' || step.status === 'skipped' ? 'opacity-60' : ''"></span>
                                <span x-show="step.message" class="block text-xs opacity-70 break-words" x-text="step.message"></span>
                            </span>
                        </li>
                    </template>
                </ol>

                <details :open="state.status === 'running' || state.status === 'failed'">
                    <summary class="text-sm cursor-pointer select-none mb-2">Protokoll</summary>
                    <pre x-ref="log" x-text="log || '(noch keine Ausgabe)'" class="text-xs p-3 rounded-lg overflow-auto whitespace-pre-wrap break-all bg-gray-900 text-gray-100" style="max-height: 420px;"></pre>
                </details>
            </section>

            <p class="text-xs opacity-70 mb-0">
                <i class="fas fa-info-circle mr-1"></i>
                Das Update wird vom Scheduler ausgeführt (Cron: <code>* * * * * php artisan schedule:run</code>) –
                als Benutzer, dem die Dateien gehören. Vorher wird die Datenbank nach <code>storage/app/updater/backups</code> gesichert.
                Auf der Konsole: <code>php artisan updater:check</code> und <code>php artisan updater:run</code>.
            </p>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    function updaterStatus(initialState, initialLog, initialMaintenance) {
        return {
            state: initialState,
            log: initialLog,
            maintenance: initialMaintenance,
            connectionLost: false,
            finishedNow: false,
            timer: null,

            get busy() {
                return ['requested', 'running'].includes(this.state.status);
            },

            get waitingTooLong() {
                return this.state.requested_at && (Date.now() - new Date(this.state.requested_at).getTime()) > 3 * 60 * 1000;
            },

            init() {
                if (this.busy) {
                    this.poll();
                }
            },

            poll() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.refresh(), 3000);
            },

            async refresh() {
                try {
                    const response = await fetch(@js(route('updater.status')), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        throw new Error(response.status);
                    }
                    const data = await response.json();
                    const wasBusy = this.busy;
                    this.state = data.state;
                    this.log = data.log;
                    this.maintenance = data.maintenance;
                    this.connectionLost = false;
                    if (wasBusy && !this.busy) {
                        this.finishedNow = true;
                    }
                    this.$nextTick(() => {
                        if (this.$refs.log) {
                            this.$refs.log.scrollTop = this.$refs.log.scrollHeight;
                        }
                    });
                } catch (e) {
                    // Während composer/Migrationen kann die Anwendung kurzzeitig Fehler liefern
                    this.connectionLost = true;
                }
                if (this.busy || this.connectionLost) {
                    this.poll();
                }
            },

            formatDate(value) {
                return value ? new Date(value).toLocaleString('de-DE', { dateStyle: 'short', timeStyle: 'short' }) : '';
            },
        };
    }
</script>
@endpush
