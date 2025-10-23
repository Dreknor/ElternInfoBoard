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
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-12 col-sm-12">
                <label class="label-control w-100 ">
                    <strong>Begrüßungstext für neue Benutzer</strong>
                    <textarea class="form-control" name="new_user_welcome_text" rows="4">{{$mailSettings->new_user_welcome_text}}</textarea>
                </label>
            </div>
            <div class="col-md-12 col-sm-12 m-auto mt-2">
                <div class="small">
                    <i class="fas fa-info-circle text-info"></i> Dieser Text wird in der Willkommens-E-Mail angezeigt, die neue Benutzer mit ihrem Startkennwort erhalten.
                    <br><strong>Hinweis:</strong> Der Text erscheint nach der Begrüßung "Hallo [Name]," und vor den Zugangsdaten.
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
