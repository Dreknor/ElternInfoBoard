<div class="tab-pane active" id="home" role="tabpanel" aria-labelledby="home-tab">
    <div class="" id="GeneralSettings">
        <form action="{{url('settings/general')}}" method="post" class="form-horizontal"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 ">
                        App-Name
                        <input type="text" class="form-control" name="app_name"
                               value="{{$settings->app_name}}">
                    </label>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Hier wird der Name des Boards geändert
                    </div>
                </div>
            </div>
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 ">
                        App-Logo
                        <input type="file" class="form-control" name="app_logo"
                               accept="image/*">
                    </label>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Hier wird das Logo des Boards geändert
                    </div>
                </div>
            </div>
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 ">
                        Favicon
                        <input type="file" class="form-control" name="favicon"
                               accept="image/*, .ico" max="">
                    </label>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Favicons sind kleine Symbole, die in der Adressleiste des Browsers
                        angezeigt werden. Sie können auch in Lesezeichen und in der
                        Registerkarte des Browsers angezeigt werden. Das Bild sollte
                        quadratisch sein und etwa 260 x 260 Pixel groß sein.
                    </div>
                </div>
            </div>
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100">
                        Login-Tracking-Modus
                        <select name="login_tracking_mode" class="form-control mt-1">
                            <option value="user" @selected($settings->login_tracking_mode === 'user')>
                                Nutzer entscheidet selbst
                            </option>
                            <option value="always" @selected($settings->login_tracking_mode === 'always')>
                                Immer aufzeichnen
                            </option>
                            <option value="never" @selected($settings->login_tracking_mode === 'never')>
                                Niemals aufzeichnen
                            </option>
                        </select>
                    </label>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Legt fest, ob der letzte Login der Nutzer gespeichert wird.
                        <ul class="mt-1 mb-0 pl-4">
                            <li><strong>Nutzer entscheidet:</strong> Jeder Nutzer kann in seinen Einstellungen wählen, ob sein Login aufgezeichnet wird.</li>
                            <li><strong>Immer:</strong> Das Tracking ist systemweit aktiv – die Nutzereinstellung wird ignoriert.</li>
                            <li><strong>Niemals:</strong> Das Tracking ist systemweit deaktiviert – der Nutzerbereich wird ausgeblendet.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <button type="submit" class="btn btn-success btn-block">
                    Einstellungen speichern
                </button>
            </div>

        </form>
    </div>
</div>
