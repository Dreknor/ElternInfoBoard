<div class="tab-pane" id="keycloak" role="tabpanel" aria-labelledby="keycloak-tab">

    {{-- ================================================================
         Info-Hinweis: Geteilte Konfiguration
    ================================================================ --}}
    <div class="alert alert-info mt-3 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        Diese Einstellungen gelten für <strong>beide</strong> OIDC-Login-Flows:
        den <strong>Mitarbeiter-Login</strong> (Button „Login mit SSO" auf der Anmeldeseite)
        und den <strong>UCS@school-Eltern-Login</strong> (separater Login-Endpunkt).
        Die Zugangsdaten zum IdP (Keycloak / UCS Konnect) sind identisch.
    </div>

    <form action="{{ url('settings/keycloak') }}" method="POST">
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
                            <input type="checkbox" class="custom-control-input" id="keycloak_enabled"
                                   name="enabled" value="1"
                                   {{ $keycloakSettings->enabled ? 'checked' : '' }}>
                            <label class="custom-control-label" for="keycloak_enabled">
                                OIDC / Keycloak-Login aktivieren
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Aktiviert den SSO-Button auf der Anmeldeseite und den UCS@school-OIDC-Endpunkt.
                        <br>
                        <em>Der UCS-OIDC-Login erfordert zusätzlich den Master-Schalter im Tab „UCS@school".</em>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- IdP-Verbindungsparameter ---- --}}
        <div class="card mb-3">
            <div class="card-header"><strong>IdP-Server (Keycloak / UCS Konnect)</strong></div>
            <div class="card-body">

                {{-- Basis-URL --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Basis-URL des IdP
                            <input type="url" class="form-control" name="base_url"
                                   value="{{ old('base_url', $keycloakSettings->base_url) }}"
                                   placeholder="https://auth.example.de">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Basis-URL des Keycloak- / OpenID-Connect-Servers,
                        z.&nbsp;B. <code>https://ucs-host.example.de</code>
                    </div>
                </div>

                {{-- Realm --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Realm
                            <input type="text" class="form-control" name="realm"
                                   value="{{ old('realm', $keycloakSettings->realm) }}"
                                   placeholder="master">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Keycloak-Realm, z.&nbsp;B. <code>ucs</code> oder <code>master</code>.
                    </div>
                </div>

                {{-- Redirect-URI --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Callback-URI (Redirect URI)
                            <input type="url" class="form-control" name="redirect_uri"
                                   value="{{ old('redirect_uri', $keycloakSettings->redirect_uri) }}"
                                   placeholder="{{ url('/auth/ucs/callback') }}">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Callback-URI, die im IdP als erlaubte Redirect-URI eingetragen sein muss.
                        Standard: <code>{{ url('/auth/ucs/callback') }}</code>
                    </div>
                </div>

                {{-- Erlaubte E-Mail-Domains --}}
                <div class="form-row mt-1 p-2 border">
                    <div class="col-md-6 col-sm-12">
                        <label class="label-control w-100">
                            Erlaubte E-Mail-Domains (Mitarbeiter-Login)
                            <input type="text" class="form-control" name="maildomain"
                                   value="{{ old('maildomain', $keycloakSettings->maildomain) }}"
                                   placeholder="*">
                        </label>
                    </div>
                    <div class="col-md-6 col-sm-12 text-muted small m-auto">
                        Komma-getrennte Liste erlaubter E-Mail-Domains für die automatische
                        Neu-Anlage von Mitarbeiter-Konten beim ersten SSO-Login.
                        <code>*</code> = alle Domains zulassen.
                        <br>
                        <em>Gilt nur für den klassischen Mitarbeiter-Keycloak-Flow.</em>
                    </div>
                </div>

            </div>
        </div>

        {{-- ---- OAuth2-Credentials (nur mit Berechtigung) ---- --}}
        <div class="card mb-3">
            <div class="card-header"><strong>OAuth2-Zugangsdaten</strong></div>
            <div class="card-body">

                @can('edit settings')
                    {{-- Client-ID --}}
                    <div class="form-row mt-1 p-2 border">
                        <div class="col-md-6 col-sm-12">
                            <label class="label-control w-100">
                                Client-ID
                                <input type="text" class="form-control" name="client_id"
                                       value="{{ old('client_id', $keycloakSettings->client_id) }}"
                                       autocomplete="off">
                            </label>
                        </div>
                        <div class="col-md-6 col-sm-12 text-muted small m-auto">
                            OAuth2 Client-ID (im IdP als „Client" angelegt).
                        </div>
                    </div>

                    {{-- Client-Secret --}}
                    <div class="form-row mt-1 p-2 border">
                        <div class="col-md-6 col-sm-12">
                            <label class="label-control w-100">
                                Client-Secret
                                <input type="password" class="form-control" name="client_secret"
                                       value="" autocomplete="new-password"
                                       placeholder="(leer lassen um bestehendes Secret zu behalten)">
                            </label>
                        </div>
                        <div class="col-md-6 col-sm-12 text-muted small m-auto">
                            OAuth2 Client-Secret. Leer lassen, um das bestehende Secret zu behalten.
                        </div>
                    </div>
                @else
                    <div class="alert alert-secondary mt-2">
                        <i class="fas fa-lock mr-1"></i>
                        Die OAuth2-Zugangsdaten können nur von Benutzern mit der Berechtigung
                        <strong>edit settings</strong> eingesehen und geändert werden.
                    </div>
                @endcan

            </div>
        </div>

        {{-- Speichern-Button --}}
        <div class="form-row p-2 mb-3">
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Einstellungen speichern
                </button>
            </div>
        </div>

    </form>

</div>

