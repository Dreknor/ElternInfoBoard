<div class="tab-pane" id="keycloak" role="tabpanel" aria-labelledby="keycloak-tab">
    <form action="{{url('settings/keycloak')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    aktiviert
                    <input type="checkbox" class="form-control" name="enabled"
                           value="1" @if($KeyCloakSetting->enabled) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Anmelden mit OpenIDConnect (OIDC) aktivieren
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Client ID
                    <input type="text" class="form-control" name="client_id"
                           value="{{ $KeyCloakSetting->client_id }}">
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Die Client ID wird von Ihrem OpenIDConnect-Server bereitgestellt.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Client Secret
                    <input type="text" class="form-control" name="client_secret"
                           value="{{ $KeyCloakSetting->client_secret }}">
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Das Client Secret wird von Ihrem OpenIDConnect-Server bereitgestellt.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Realm
                    <input type="text" class="form-control" name="realm"
                           value="{{ $KeyCloakSetting->realm }}">
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Der Realm wird von Ihrem OpenIDConnect-Server bereitgestellt.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                   Base URL
                    <input type="text" class="form-control" name="base_url" required
                           value="{{ $KeyCloakSetting->base_url }}">
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Base URL wird von Ihrem OpenIDConnect-Server bereitgestellt.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Redirect URL
                    <input type="text" class="form-control" name="redirect_url"
                           value="{{ $KeyCloakSetting->redirect_url ?? url('/login/keycloak/callback') }}" required>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Bitte geben Sie diese URL an Ihren OpenIDConnect-Server weiter.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Welche E-Mail-Domainen dürfen sich anmelden?
                    <input class="form-control" name="maildomain" value="{{$KeyCloakSetting->maildomain}}" required/>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Bitte geben Sie die E-Mail-Domainen an, die sich anmelden dürfen. Trennen Sie die Domainen mit einem Komma.
                    <br>Nur Nutzer mit einer E-Mail-Adresse, die zu einer der angegebenen Domainen gehört, können sich anmelden.
                </div>
            </div>

        <div class="form-row">
            <button type="submit" class="btn btn-success btn-block">
                Save Settings
            </button>
        </div>
        </div>
    </form>
</div>
