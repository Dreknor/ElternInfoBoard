<div class="tab-pane" id="keycloak" role="tabpanel" aria-labelledby="keycloak-tab">
    <form action="{{ url('settings/keycloak') }}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')

        {{-- Aktivierung --}}
        <div class="form-row mt-1 p-2 border bg-light">
            <div class="col-md-6 col-sm-12">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="kc_enabled"
                           name="enabled" value="1"
                           {{ $keycloakSettings->enabled ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="kc_enabled">
                        <i class="fas fa-key"></i> OIDC / Keycloak-Login aktivieren
                    </label>
                </div>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small text-muted">
                    Wenn aktiviert, wird auf der Login-Seite ein zusätzlicher Button zum SSO-Login angezeigt.
                </div>
            </div>
        </div>

        {{-- Verbindungsdaten --}}
        <div class="p-3 mt-3 border rounded">
            <h6 class="font-weight-bold text-primary mb-3">
                <i class="fas fa-server"></i> Verbindungsdaten
            </h6>

            <div class="form-row">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 font-weight-bold">
                        Basis-URL
                    </label>
                    <input type="url" class="form-control" name="base_url"
                           value="{{ $keycloakSettings->base_url }}"
                           placeholder="https://auth.example.com">
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small text-muted">
                        URL des Keycloak-/OIDC-Servers (ohne Pfad).
                    </div>
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 font-weight-bold">
                        Realm
                    </label>
                    <input type="text" class="form-control" name="realm"
                           value="{{ $keycloakSettings->realm }}"
                           placeholder="master">
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small text-muted">
                        Der Realm-Name in Keycloak. Standard: <code>master</code>.
                    </div>
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 font-weight-bold">
                        Redirect-URI
                    </label>
                    <input type="url" class="form-control" name="redirect_uri"
                           value="{{ $keycloakSettings->redirect_uri }}"
                           placeholder="{{ url('login/keycloak/callback') }}">
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small text-muted">
                        Muss exakt mit der in Keycloak hinterlegten Redirect-URI übereinstimmen.<br>
                        Empfehlung: <code>{{ url('login/keycloak/callback') }}</code>
                    </div>
                </div>
            </div>
        </div>

        {{-- Client-Daten --}}
        <div class="p-3 mt-3 border rounded">
            <h6 class="font-weight-bold text-secondary mb-3">
                <i class="fas fa-id-badge"></i> Client-Konfiguration
            </h6>

            <div class="form-row">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 font-weight-bold">
                        Client-ID
                    </label>
                    <input type="text" class="form-control" name="client_id"
                           value="{{ $keycloakSettings->client_id }}"
                           autocomplete="off">
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small text-muted">
                        Die Client-ID, die in Keycloak für diese Anwendung konfiguriert ist.
                    </div>
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 font-weight-bold">
                        Client-Secret
                    </label>
                    <input type="password" class="form-control no-tinymce" name="client_secret"
                           value="{{ $keycloakSettings->client_secret }}"
                           autocomplete="new-password">
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small text-muted">
                        Das Client-Secret aus den Credentials des Clients in Keycloak.
                    </div>
                </div>
            </div>
        </div>

        {{-- Optionen --}}
        <div class="p-3 mt-3 border rounded">
            <h6 class="font-weight-bold text-info mb-3">
                <i class="fas fa-envelope"></i> Zuordnung
            </h6>

            <div class="form-row">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 font-weight-bold">
                        Erlaubte Mail-Domain
                    </label>
                    <input type="text" class="form-control" name="maildomain"
                           value="{{ $keycloakSettings->maildomain }}"
                           placeholder="*">
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small text-muted">
                        Beschränkt den SSO-Login auf Nutzer mit dieser E-Mail-Domain (z.&nbsp;B. <code>schule.de</code>).
                        Verwenden Sie <code>*</code>, um alle Domains zuzulassen.
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="fas fa-info-circle"></i>
            Hinweis: Damit Änderungen wirksam werden, kann es nötig sein, den Config-Cache zu leeren.
            Dies geschieht beim Speichern automatisch.
        </div>

        <div class="form-row mt-3">
            <div class="col-12">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-save"></i> OIDC-Einstellungen speichern
                </button>
            </div>
        </div>
    </form>
</div>

