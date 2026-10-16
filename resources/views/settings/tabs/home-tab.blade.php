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
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100">Geschützte Rollen</label>
                    <div class="space-y-1">
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 cursor-pointer mb-0">
                                <input type="checkbox" name="protected_roles[]" value="{{ $role->name }}"
                                       class="w-4 h-4 cursor-pointer" style="accent-color: var(--color-primary);"
                                       @checked(in_array($role->name, $settings->protected_roles ?? []))>
                                <span class="text-sm select-none">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Benutzer mit einer dieser Rollen werden von der Massenlöschung, dem
                        automatischen Inaktivitäts-Cleanup und dem Schuljahreswechsel
                        ausgeschlossen und nicht automatisch gelöscht.
                    </div>
                </div>
            </div>
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <input type="hidden" name="public_termine_enabled" value="0">
                    <label class="flex items-center gap-2 cursor-pointer mb-0">
                        <input type="checkbox" name="public_termine_enabled" value="1"
                               class="w-4 h-4 cursor-pointer" style="accent-color: var(--color-primary);"
                               @checked($settings->public_termine_enabled ?? true)>
                        <span class="select-none">Öffentliche Termine erlauben</span>
                    </label>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Wenn aktiv, können Termine als „öffentlich“ markiert werden. Öffentliche Termine sind ohne
                        Anmeldung abrufbar:
                        <ul class="mt-1 mb-0 pl-4">
                            <li>als Seite: <a href="{{ url('termine/oeffentlich') }}" target="_blank" rel="noopener">{{ url('termine/oeffentlich') }}</a></li>
                            <li>als Kalender-Datei: <a href="{{ url('ical/publicEvents') }}">{{ url('ical/publicEvents') }}</a></li>
                        </ul>
                        Ist die Option deaktiviert, wird die Auswahl beim Anlegen ausgeblendet und beide Adressen sind nicht erreichbar.
                    </div>
                </div>
            </div>
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100">Veranstaltungsort öffentlicher Termine</label>
                    <input type="text" name="public_termine_location_name" class="form-control mb-1"
                           placeholder="Name (z. B. Grundschule Musterstadt)"
                           value="{{ old('public_termine_location_name', $settings->public_termine_location_name) }}">
                    <input type="text" name="public_termine_location_street" class="form-control mb-1"
                           placeholder="Straße und Hausnummer"
                           value="{{ old('public_termine_location_street', $settings->public_termine_location_street) }}">
                    <div class="flex gap-1">
                        <input type="text" name="public_termine_location_postal_code" class="form-control" style="max-width: 7rem;"
                               placeholder="PLZ"
                               value="{{ old('public_termine_location_postal_code', $settings->public_termine_location_postal_code) }}">
                        <input type="text" name="public_termine_location_city" class="form-control"
                               placeholder="Ort"
                               value="{{ old('public_termine_location_city', $settings->public_termine_location_city) }}">
                    </div>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Wird auf der öffentlichen Terminseite angezeigt und als strukturierte Daten (schema.org) ausgegeben.
                        Suchmaschinen wie Google zeigen die Termine nur dann als Veranstaltungen an, wenn eine
                        vollständige Adresse hinterlegt ist.
                    </div>
                </div>
            </div>
            <div class="form-row mt-1 p-2 border">
                <div class="col-md-6 col-sm-12">
                    <label class="flex items-center gap-2 cursor-pointer mb-0">
                        <input type="checkbox" name="allow_individual_recipients" value="1"
                               class="w-4 h-4 cursor-pointer" style="accent-color: var(--color-primary);"
                               @checked($settings->allow_individual_recipients ?? false)>
                        <span class="label-control mb-0 select-none">Freigabe an einzelne Personen</span>
                    </label>
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small">
                        Beim Erstellen von Nachrichten und Terminen können zusätzlich zu Gruppen
                        einzelne Personen ausgewählt werden – auch ohne Gruppenzugehörigkeit.
                        Beim Deaktivieren bleiben bestehende Freigaben erhalten.
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
