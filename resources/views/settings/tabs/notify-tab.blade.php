<div class="tab-pane" id="notify" role="tabpanel" aria-labelledby="notify-tab">
    <form action="{{url('settings/notifications')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    E-Mail-Benachrichtigungen an welchem Wochentag senden?
                </label>
                <select name="weekday_send_information_mail" class="form-control">
                    <option value="1" @if($notifySettings->weekday_send_information_mail == 1) selected @endif>Montag</option>
                    <option value="2" @if($notifySettings->weekday_send_information_mail == 2) selected @endif>Dienstag</option>
                    <option value="3" @if($notifySettings->weekday_send_information_mail == 3) selected @endif>Mittwoch</option>
                    <option value="4" @if($notifySettings->weekday_send_information_mail == 4) selected @endif>Donnerstag</option>
                    <option value="5" @if($notifySettings->weekday_send_information_mail == 5) selected @endif>Freitag</option>
                    <option value="6" @if($notifySettings->weekday_send_information_mail == 6) selected @endif>Samstag</option>
                    <option value="0" @if($notifySettings->weekday_send_information_mail == 0) selected @endif>Sonntag</option>
                </select>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    An welchem Wochentag sollen die E-Mail-Benachrichtigungen mit neuen Mitteilungen, Terminen etc. gesendet werden?
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    E-Mail-Benachrichtigungen zu folgender Stunde?
                </label>
                <input type="number" class="form-control" name="hour_send_information_mail" value="{{$notifySettings->hour_send_information_mail}}" min="0" max="23" step="1">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Gibt an, in welcher Stunde die E-Mail-Benachrichtigungen gesendet werden sollen. Der Versand erfolgt immer an einen Nutzerteil, im Abstand von 5 Minuten um einer Spam-Blockierung vorzubeugen.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Uhrzeit für Erinnerungen an fehlende Rückmeldungen
                </label>
                <input type="number" class="form-control" name="hour_send_reminder_mail" value="{{$notifySettings->hour_send_reminder_mail}}" min="0" max="23" step="1">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Bei verpflichtenden Rückmeldungen werden die Nutzer 3 Tage vor Ablauf der Frist an die fehlende Rückmeldung erinnert.
                    Zu welcher Uhrzeit sollen Erinnerungen an fehlende Rückmeldungen gesendet werden?
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Uhrzeit für Krankmeldungen senden
                </label>
                <input type="time" class="form-control" name="krankmeldungen_report_time"
                       value="@if($notifySettings->krankmeldungen_report_hour < 10)0{{$notifySettings->krankmeldungen_report_hour}}@else{{$notifySettings->krankmeldungen_report_hour}}@endif:@if($notifySettings->krankmeldungen_report_minute < 10)0{{$notifySettings->krankmeldungen_report_minute}}@else{{$notifySettings->krankmeldungen_report_minute}}@endif">

            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zu der angegebenen Uhrzeit werden die Krankmeldungen noch einmal gesammelt an das Sekretariat gesendet. Unabhängig davon werden Krankmeldungen sofort an das Sekretariat gesendet.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Wochentag für Erinnerungen Schickzeiten
                </label>
                <select name="schickzeiten_report_weekday" class="form-control">
                    <option value="1" @if($notifySettings->schickzeiten_report_weekday == 1) selected @endif>Montag</option>
                    <option value="2" @if($notifySettings->schickzeiten_report_weekday == 2) selected @endif>Dienstag</option>
                    <option value="3" @if($notifySettings->schickzeiten_report_weekday == 3) selected @endif>Mittwoch</option>
                    <option value="4" @if($notifySettings->schickzeiten_report_weekday == 4) selected @endif>Donnerstag</option>
                    <option value="5" @if($notifySettings->schickzeiten_report_weekday == 5) selected @endif>Freitag</option>
                    <option value="6" @if($notifySettings->schickzeiten_report_weekday == 6) selected @endif>Samstag</option>
                    <option value="0" @if($notifySettings->schickzeiten_report_weekday == 0) selected @endif>Sonntag</option>
                </select>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Eltern erhalten eine Erinnerung an die hinterlegten Schickzeiten. An welchem Wochentag sollen die Erinnerungen gesendet werden?
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Uhrzeit für Erinnerungen Schickzeiten
                </label>
                <input type="number" class="form-control" name="schickzeiten_report_hour" value="{{$notifySettings->schickzeiten_report_hour}}">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zu der angegebenen Uhrzeit werden die erfassten Schickzeiten an die Eltern als Erinnerung gesendet. Sind keine Schickzeiten erfasst, wird keine E-Mail gesendet.
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
