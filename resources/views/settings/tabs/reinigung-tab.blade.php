<div class="tab-pane" id="reinigung" role="tabpanel" aria-labelledby="reinigung-tab">
    <form action="{{url('settings/reinigung')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Reinigungsplan nach Gruppenbereichen trennen
                    <input type="checkbox" class="form-control" name="separate_bereiche"
                           value="1" @if($reinigungSettings->separate_bereiche) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Ist diese Option aktiviert, wird für jeden bei den Gruppen gepflegten Bereich (Feld "Bereich" der Gruppe) ein eigener Reinigungsplan geführt.
                    Ist sie deaktiviert, gibt es einen gemeinsamen Reinigungsplan für die gesamte Einrichtung.
                    Sind bei den Gruppen gar keine Bereiche gepflegt, wird unabhängig von dieser Einstellung automatisch der gemeinsame Plan verwendet.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Bereiche vom gemeinsamen Plan ausschließen
                    <select name="combined_exclude_bereiche[]" class="form-control" multiple>
                        @foreach($reinigungBereiche as $bereich)
                            <option value="{{ $bereich }}" @if(in_array($bereich, $reinigungSettings->combined_exclude_bereiche)) selected @endif>
                                {{ $bereich }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Nur relevant im gemeinsamen Modus (Option oben deaktiviert oder keine Bereiche gepflegt): Nutzer aus den hier ausgewählten Bereichen (z. B. ein reiner Verwaltungs-/Mitarbeiterbereich) werden nicht in den gemeinsamen Verteilungspool aufgenommen.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Ferienwochen beim automatischen Befüllen überspringen
                    <input type="checkbox" class="form-control" name="skip_holidays"
                           value="1" @if($reinigungSettings->skip_holidays) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn aktiviert, werden beim automatischen Befüllen des Reinigungsplans Wochen übersprungen, die (anhand des unter Care hinterlegten Bundeslandes) in die Schulferien fallen.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Erinnerung vor dem eigenen Reinigungseinsatz
                    <input type="checkbox" class="form-control" name="reminder_enabled"
                           value="1" @if($reinigungSettings->reminder_enabled) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn aktiviert, erhalten Familien vor ihrem Reinigungseinsatz automatisch eine Erinnerung per E-Mail und/oder Push-Benachrichtigung.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Tage vor dem Einsatz
                    <input type="number" min="0" max="30" class="form-control" name="reminder_days_before"
                           value="{{ $reinigungSettings->reminder_days_before }}">
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Anzahl der Tage vor Beginn der Reinigungswoche, zu der die Erinnerung verschickt werden soll.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Versandzeitpunkt
                    <input type="time" class="form-control" name="reminder_time"
                           value="{{ $reinigungSettings->reminder_time }}">
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Tageszeit, zu der täglich geprüft wird, ob eine Erinnerung fällig ist.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Erinnerung per E-Mail
                    <input type="checkbox" class="form-control" name="reminder_email"
                           value="1" @if($reinigungSettings->reminder_email) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Versendet die Erinnerung zusätzlich als E-Mail.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Erinnerung per Push-Benachrichtigung
                    <input type="checkbox" class="form-control" name="reminder_push"
                           value="1" @if($reinigungSettings->reminder_push) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Versendet die Erinnerung zusätzlich als Web-Push-Benachrichtigung.
                </div>
            </div>
        </div>

        <div class="form-row">
            <button type="submit" class="btn btn-success btn-block">
                Save Settings
            </button>
        </div>
    </form>
</div>
