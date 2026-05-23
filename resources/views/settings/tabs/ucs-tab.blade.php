<div class="tab-pane" id="ucs" role="tabpanel" aria-labelledby="ucs-tab">

    {{-- ================================================================
         Status-Karte (Telemetrie, read-only)
    ================================================================ --}}
    <div class="card mb-3 mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="m-0"><i class="fas fa-info-circle mr-1"></i> UCS@school – Status</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Letzter Sync:</dt>
                        <dd class="col-sm-7">
                            {{ $ucsSettings->last_sync_at ? \Carbon\Carbon::parse($ucsSettings->last_sync_at)->format('d.m.Y H:i:s') : '–' }}
                        </dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            @php
                                $syncStatus = $ucsSettings->last_sync_status;
                                $isStale    = $syncStatus === 'running' && \Illuminate\Support\Facades\Cache::missing('ucs.sync.running_lock');
                            @endphp
                            @if($isStale)
                                <span class="badge badge-warning">stale (Lock abgelaufen)</span>
                            @elseif($syncStatus === 'success')
                                <span class="badge badge-success">Erfolgreich</span>
                            @elseif($syncStatus === 'failed')
                                <span class="badge badge-danger">Fehlgeschlagen</span>
                            @elseif($syncStatus === 'running')
                                <span class="badge badge-info">Läuft</span>
                            @else
                                <span class="badge badge-secondary">–</span>
                            @endif
                        </dd>

                        @if($ucsSettings->last_sync_message)
                            <dt class="col-sm-5">Meldung:</dt>
                            <dd class="col-sm-7 text-muted small">{{ $ucsSettings->last_sync_message }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Synchronisierte Eltern:</dt>
                        <dd class="col-sm-6">{{ $ucsSettings->last_sync_parents ?? '–' }}</dd>

                        <dt class="col-sm-6">Synchronisierte Schüler:</dt>
                        <dd class="col-sm-6">{{ $ucsSettings->last_sync_students ?? '–' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="mt-3 d-flex flex-wrap gap-2">
                @can('edit settings')
                    <form action="{{ route('settings.ucs.test') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-plug mr-1"></i> Verbindung testen
                        </button>
                    </form>
                @endcan

                @can('manage ucs sync')
                    <form action="{{ route('settings.ucs.sync') }}" method="POST" class="d-inline ml-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm"
                                onclick="return confirm('Manuellen Sync starten?')">
                            <i class="fas fa-sync mr-1"></i> Sync jetzt starten
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    {{-- ================================================================
         Einstellungs-Formular
    ================================================================ --}}
    <form action="{{ url('settings/ucs') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Fehler-Anzeige --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ---- Master-Schalter ---- --}}
        <div class="card mb-3">
            <div class="card-header"><strong>Allgemein</strong></div>
            <div class="card-body">
                <div class="form-row p-2">
                    <div class="col-md-6 col-sm-12">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="ucs_enabled"
                                   name="enabled" value="1"
                                   {{ $ucsSettings->enabled ? 'checked' : '' }}>
                            <label class="custom-control-label" for="ucs_enabled">
                                UCS-Integration aktivieren
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Master-Schalter für die gesamte UCS@school-Anbindung.
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- Kelvin REST API ---- --}}
        <div class="card mb-3">
            <div class="card-header"><strong>Kelvin REST API</strong></div>
            <div class="card-body">

                {{-- Basis-URL --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Basis-URL
                            <input type="url" class="form-control" name="kelvin_base_url"
                                   value="{{ old('kelvin_base_url', $ucsSettings->kelvin_base_url) }}"
                                   placeholder="https://ucs-host/ucsschool/kelvin/v1">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Basis-URL der Kelvin REST API, z. B.
                        <code>https://&lt;ucs-host&gt;/ucsschool/kelvin/v1</code>
                    </div>
                </div>

                {{-- Schule --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Schul-Name
                            <input type="text" class="form-control" name="school"
                                   value="{{ old('school', $ucsSettings->school) }}"
                                   placeholder="z.B. GS-XY">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Interner Name der Schule in UCS@school (Single-School-Betrieb).
                        <div class="alert alert-warning p-1 mt-1 small">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Änderung der Schule oder Basis-URL kann verwaiste Datensätze erzeugen.
                        </div>
                    </div>
                </div>

                {{-- Service-Account-Username --}}
                @can('edit settings')
                    <div class="form-row mt-1 p-2 border">
                        <div class="col-md-6 col-sm-12">
                            <label class="label-control w-100">
                                Service-Account-Benutzer
                                <input type="text" class="form-control" name="kelvin_username"
                                       value="{{ old('kelvin_username', $ucsSettings->kelvin_username) }}"
                                       autocomplete="off">
                            </label>
                        </div>
                        <div class="col-md-6 col-sm-12 text-muted small m-auto">
                            Benutzername des Service-Accounts für die Kelvin API.
                        </div>
                    </div>

                    {{-- Service-Account-Passwort --}}
                    <div class="form-row mt-1 p-2 border">
                        <div class="col-md-6 col-sm-12">
                            <label class="label-control w-100">
                                Service-Account-Passwort
                                <input type="password" class="form-control" name="kelvin_password"
                                       value="" autocomplete="new-password"
                                       placeholder="(leer lassen um bestehendes Passwort zu behalten)">
                            </label>
                        </div>
                        <div class="col-md-6 col-sm-12 text-muted small m-auto">
                            Passwort des Service-Accounts – wird verschlüsselt gespeichert.
                            Leer lassen, um das bestehende Passwort zu behalten.
                        </div>
                    </div>
                @else
                    <div class="alert alert-secondary mt-2">
                        <i class="fas fa-lock mr-1"></i>
                        Die Zugangsdaten können nur von Benutzern mit der Berechtigung
                        <strong>edit settings</strong> eingesehen und geändert werden.
                    </div>
                @endcan

                {{-- Page-Size --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Pagination-Seitengröße
                            <input type="number" class="form-control" name="kelvin_page_size"
                                   value="{{ old('kelvin_page_size', $ucsSettings->kelvin_page_size) }}"
                                   min="1" max="1000">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Anzahl Datensätze pro API-Anfrage (empfohlen: 200).
                    </div>
                </div>

                {{-- Timeout --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            HTTP-Timeout (Sekunden)
                            <input type="number" class="form-control" name="kelvin_timeout"
                                   value="{{ old('kelvin_timeout', $ucsSettings->kelvin_timeout) }}"
                                   min="5" max="300">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Timeout in Sekunden für HTTP-Anfragen an die Kelvin API.
                    </div>
                </div>

                {{-- Token-TTL --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Token-TTL (Sekunden)
                            <input type="number" class="form-control" name="kelvin_token_ttl"
                                   value="{{ old('kelvin_token_ttl', $ucsSettings->kelvin_token_ttl) }}"
                                   min="60" max="86400">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Gültigkeitsdauer des Bearer-Tokens in Sekunden
                        (Standard: 3300 = 55 Minuten).
                    </div>
                </div>

            </div>
        </div>

        {{-- ---- Synchronisation ---- --}}
        <div class="card mb-3">
            <div class="card-header"><strong>Synchronisation</strong></div>
            <div class="card-body">

                {{-- sync_enabled --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="ucs_sync_enabled"
                                   name="sync_enabled" value="1"
                                   {{ $ucsSettings->sync_enabled ? 'checked' : '' }}>
                            <label class="custom-control-label" for="ucs_sync_enabled">
                                Nächtlichen Sync aktivieren
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Aktiviert den automatischen Sync gemäß dem Cron-Ausdruck unten.
                    </div>
                </div>

                {{-- sync_cron --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Cron-Ausdruck
                            <input type="text" class="form-control" name="sync_cron"
                                   value="{{ old('sync_cron', $ucsSettings->sync_cron) }}"
                                   placeholder="30 2 * * *">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Zeitplan für den nächtlichen Sync (Standard: <code>30 2 * * *</code> = täglich 02:30 Uhr).
                    </div>
                </div>

                {{-- on_login_fallback --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="ucs_on_login_fallback"
                                   name="on_login_fallback" value="1"
                                   {{ $ucsSettings->on_login_fallback ? 'checked' : '' }}>
                            <label class="custom-control-label" for="ucs_on_login_fallback">
                                JIT-Sync beim OIDC-Login aktivieren
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Führt beim Login einen Just-in-Time-Sync durch, wenn der Benutzer
                        noch nicht synchronisiert ist.
                    </div>
                </div>

                {{-- on_login_timeout --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Login-Sync-Timeout (Sekunden)
                            <input type="number" class="form-control" name="on_login_timeout"
                                   value="{{ old('on_login_timeout', $ucsSettings->on_login_timeout) }}"
                                   min="1" max="60">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Maximale Wartezeit für den JIT-Sync beim Login in Sekunden.
                    </div>
                </div>

                {{-- purge_after_days --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Verwaiste Datensätze löschen nach (Tagen)
                            <input type="number" class="form-control" name="purge_after_days"
                                   value="{{ old('purge_after_days', $ucsSettings->purge_after_days) }}"
                                   min="1" max="365">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Tage, nach denen nicht mehr in UCS vorhandene Sync-Objekte
                        endgültig gelöscht werden (Hard-Delete).
                    </div>
                </div>

            </div>
        </div>

        {{-- Speichern-Button --}}
        <div class="form-row p-2">
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Einstellungen speichern
                </button>
            </div>
        </div>

    </form>

    {{-- ================================================================
         Verknüpfungsvorschläge (Initial-Linking-Workflow, TODO-08)
    ================================================================ --}}
    @include('settings.tabs._ucs-link-candidates')

</div>


