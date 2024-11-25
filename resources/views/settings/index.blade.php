@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h5 class="card-title
                        ">
                            Einstellungen
                        </h5>
                    </div>
                    <div class="card-body border-bottom">
                        <ul class="nav nav-tabs" id="SettingsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="home-tab" data-toggle="tab" data-target="#home"
                                        type="button" role="tab" aria-controls="home" aria-selected="true">Home
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="Email-tab" data-toggle="tab" data-target="#email"
                                        type="button" role="tab" aria-controls="profile" aria-selected="false">Email
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="notify-tab" data-toggle="tab" data-target="#notify"
                                        type="button" role="tab" aria-controls="notify" aria-selected="false">Benachrichtigungen
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="schickzeiten-tab" data-toggle="tab" data-target="#schickzeiten"
                                        type="button" role="tab" aria-controls="schicken" aria-selected="false">Schickzeiten
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content">
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
                                        <div class="form-row">
                                            <button type="submit" class="btn btn-success btn-block">
                                                Einstellungen speichern
                                            </button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                            <div class="tab-pane" id="email" role="tabpanel" aria-labelledby="email-tab">
                                <form action="{{url('settings/email')}}" method="post" class="form-horizontal"
                                     enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                SMTP-Host
                                                <input type="text" class="form-control" name="mail_server"
                                                       value="{{$mailSettings->mail_server}}">
                                            </label>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Hier wird der SMTP-Host geändert
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                SMTP-Port
                                                <input type="text" class="form-control" name="mail_port"
                                                       value="{{$mailSettings->mail_port}}">
                                            </label>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Hier wird der SMTP-Port geändert
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                SMTP-Username
                                                <input type="text" class="form-control" name="mail_username"
                                                       value="{{$mailSettings->mail_username}}">
                                            </label>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Hier wird der SMTP-Username geändert
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                SMTP-Password
                                                <input type="password" class="form-control" name="mail_password"
                                                       value="{{$mailSettings->mail_password}}">
                                            </label>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto ">
                                            <div class="small">
                                                Hier wird das SMTP-Password geändert
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                SMTP-Encryption
                                                <input type="text" class="form-control" name="mail_encryption"
                                                       value="{{$mailSettings->mail_encryption}}">
                                            </label>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Hier wird die SMTP-Encryption geändert
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                SMTP-From-Name
                                                <input type="text" class="form-control" name="mail_from_name"
                                                       value="{{$mailSettings->mail_from_name}}">
                                            </label>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Welcher Name soll als Absender angezeigt werden?
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                SMTP-From-Email
                                                <input type="text" class="form-control" name="mail_from_address"
                                                       value="{{$mailSettings->mail_from_address}}">
                                            </label>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Welche E-Mail-Adresse soll als Antwortadresse verwendet werden?
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
                                            <input type="time" class="form-control" name="krankmeldungen_report_time" value="@if($notifySettings->krankmeldungen_report_hour < 10)0{{$notifySettings->krankmeldungen_report_hour}}:{{$notifySettings->krankmeldungen_report_minute}}@else{{$notifySettings->krankmeldungen_report_hour}}:{{$notifySettings->krankmeldungen_report_minute}}@endif">
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
                            <div class="tab-pane" id="schickzeiten" role="tabpanel" aria-labelledby="notify-tab">
                                <form action="{{url('settings/schickzeiten')}}" method="post" class="form-horizontal">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                Schickzeiten ab
                                            </label>
                                            <input type="time" class="form-control" name="schicken_ab" value="{{$schickzeitenSettings->schicken_ab}}">
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Ab welcher Uhrzeit dürfen die Schüler das Haus verlassen?
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                Schickzeiten bis
                                            </label>
                                            <input type="time" class="form-control" name="schicken_bis" value="{{$schickzeitenSettings->schicken_bis}}">
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Bis zu welcher Uhrzeit dürfen die Schüler das Haus verlassen?
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                                Erklärung Schickzeiten
                                            </label>
                                            <textarea class="form-control" name="schicken_text">{{$schickzeitenSettings->schicken_text}}</textarea>
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Hier kann der Text angepasst werden, der den Eltern angezeigt wird, wenn sie die Schickzeiten erfassen. So sind sie über die Regeln und Rahmenbedingungen informiert.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row mt-1 p-2 border">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="label-control w-100 ">
                                               Interval Schickzeiten
                                            </label>
                                            <input type="number" class="form-control" name="schicken_intervall" value="{{$schickzeitenSettings->schicken_intervall}}">
                                        </div>
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="small">
                                                Hier kann das Intervall angepasst werden, in dem die Kinder losgeschickt werden können. Abweichende können angegeben werden, es wird den Eltern dann angezeigt, dass die Kinder selbstständig losgehen müssen.
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
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/piexif.min.js" type="text/javascript"></script>

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink  link charmap',
                'searchreplace visualblocks code',
                'insertdatetime  paste code wordcount',
                'contextmenu textcolor',
            ],

            toolbar: 'undo redo | formatselect | bold italic ',
            contextmenu: " link  inserttable | cell row column deletetable",

        });


        $(document).ready(function () {
            $('#SettingsTab a').on('click', function (e) {
                e.preventDefault()
                console.log('clicked')
                console.log($(this))
                $(this).tab('show')
            })
        });
    </script>
@endpush
