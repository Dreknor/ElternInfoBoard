@extends('layouts.app')
@section('title') - Einstellungen @endsection

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">
                    {{$user->name}}
                </h5>
            </div>
            <div class="card-body">
                @if(isset($changelog))
                    <div class="card-body border border-info">
                        <h6>
                            {{$changelog->header}}
                        </h6>
                        <p>
                            {!! $changelog->text !!}
                        </p>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Einstellungen
                                </h5>
                            </div>

                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form action="{{url('/einstellungen/')}}" method="post" class="form form-horizontal">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" class="form-control border-input" placeholder="Name" name="name" value="{{$user->name}}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>E-Mail</label>
                                                <input type="text" class="form-control border-input" placeholder="E-Mail" name="email" value="{{$user->email}}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>öffentliche E-Mail</label>
                                                <input type="text" class="form-control border-input" placeholder="öffentliche E-Mail" name="publicMail" value="{{$user->publicMail}}" >
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>öffentliche Telefonnummer (für andere Eltern in den gleichen Gruppen sichtbar)</label>
                                                <input type="text" class="form-control border-input" placeholder="öffentliche Telefonnummer" name="publicPhone" value="{{$user->publicPhone}}"  autocomplete="off" >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group w-100">
                                                <label class="w-100">neues Passwort
                                                    <input class="form-control" name="password" type="password"
                                                           minlength="8">
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group w-100">
                                                <label class="w-100">Passwort bestätigen
                                                    <input id="password-confirm" type="password" class="form-control"
                                                           name="password_confirmation" required
                                                           autocomplete="new-password">
                                                </label>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label>Benachrichtigung per E-Mail (zuletzt: {{$user->lastEmail?->format('d.m.Y H:i')}})</label>
                                                <select class="custom-select" name="benachrichtigung">
                                                    <option value="daily" @if($user->benachrichtigung == 'daily') selected @endif>Täglich (bei neuen Nachrichten)</option>
                                                    <option value="weekly" @if($user->benachrichtigung == 'weekly') selected @endif >Wöchentlich (Freitags)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label>Kopie von Rückmeldungen erhalten</label>
                                                <select class="custom-select" name="sendCopy">
                                                    <option value="1" @if($user->sendCopy == 1) selected @endif >Kopie erhalten</option>
                                                    <option value="0" @if($user->sendCopy == 0) selected @endif >keine
                                                        Kopie senden
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 col-sm-12">
                                            <div class="form-group">
                                                <label>Login aufzeichnen</label>
                                                <select class="custom-select" name="track_login">
                                                    <option value="1" @if($user->track_login == true) selected @endif >
                                                        letzten Login aufzeichnen
                                                    </option>
                                                    <option value="0" @if($user->track_login == false) selected @endif >
                                                        keine Speicherung
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-group">
                                                <label>Termine freigeben (Termine können dann per Link in externe
                                                    Kalender integiert werden)</label>
                                                <select class="custom-select" name="releaseCalendar">
                                                    <option value="1"
                                                            @if($user->releaseCalendar == true) selected @endif >ja
                                                    </option>
                                                    <option value="0"
                                                            @if($user->releaseCalendar == false) selected @endif >nein
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-group">
                                                <label>Prefix für Termine (die Namen der Termine bekommen das angegebene
                                                    Prefix vorangestellt, damit diese leichter gefunden werden
                                                    können)</label>
                                                <input type="text" class="form-control border-input"
                                                       name="calendar_prefix" value="{{$user->calendar_prefix}}"
                                                       max="8">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success btn-block collapse"
                                                    id="btn-save">speichern
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="row">
                            @if(auth()->user()->groups->count() > 0)
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                Kinder
                                            </h5>
                                            <p class="card-subtitle text-muted small">
                                                Hier werden die Kinder angezeigt, die mit ihrem Konto verknüpft sind. Sie können hier auch weitere Kinder hinzufügen. Geben Sie dazu bitte Lerngruppe und Klassenstufe an. Sollte keine Lerngruppe vorhanden sein, tragen Sie bitte in beiden Feldern die Klassenstufe ein.<br>
                                                Sollte Ihr Kind im Hort betreut werden, können Sie mittel der Glocke die Benachrichtigung aktivieren, wenn es sich im Hort an- bzw. abmeldet.
                                            </p>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group">
                                                @foreach($user->children() as $child)
                                                    <li class="list-group-item">
                                                        {{$child->first_name}}
                                                        {{$child->last_name}}


                                                        @if($child->notification)
                                                            <span class="badge bg-gradient-directional-teal p-2 ml-1 pull-right child-notification text-white" title="Benachrichtigung aktiv" data-child_id="{{$child->id}}" data-notification="1">
                                                                <i class="fas fa-bell"></i>
                                                            </span>
                                                        @else
                                                            <span class="badge bg-gradient-radial-amber p-2 ml-1 pull-right child-notification" title="Benachrichtigung deaktiviert"  data-child_id="{{$child->id}}" data-notification="0">
                                                                <i class="fas fa-bell-slash"></i>
                                                            </span>
                                                        @endif

                                                        <span class="badge badge-info ml-1 p-2 pull-right">
                                                            {{$child->group->name}}
                                                        </span>
                                                        <span class="badge badge-info ml-1 p-2 pull-right">
                                                            {{$child->class->name}}
                                                        </span>
                                                    </li>
                                            @endforeach
                                        </div>
                                        <div class="card-footer border-top">
                                            <form action="{{url('/child')}}" method="post">
                                                @csrf
                                                <div class="form-group row">
                                                    <label for="first_name"
                                                           class="col-form-label text-danger">Vorname</label>
                                                    <input id="first_name" type="text" class="form-control"
                                                           name="first_name" required>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="last_name"
                                                           class="col-form-label text-danger">Nachname</label>
                                                    <input id="last_name" type="text" class="form-control"
                                                           name="last_name" required>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="group" class="col-form-label text-danger">Gruppe</label>
                                                    <select id="group" class="form-control" name="group_id" required>
                                                        @foreach(auth()->user()->groups as $group)
                                                            <option value="{{$group->id}}">{{$group->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="class"
                                                           class="col-form-label text-danger">Klassenstufe</label>
                                                    <select id="class" class="form-control" name="class_id" required>
                                                        @foreach(auth()->user()->groups as $group)
                                                            <option value="{{$group->id}}">{{$group->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-block btn-primary">
                                                    Kind hinzufügen
                                                </button>

                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            Gruppen
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @foreach($user->groups as $gruppe)
                                            <div class="btn btn-outline-info">
                                                {{$gruppe->name}}
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="card-footer">
                                        <p class="footer-default small">
                                            Sollte die Lerngruppe und/oder Alterststufe ihres Kindes nicht korrekt in
                                            den
                                            Gruppen abgebildet sein, wenden Sie sich bitte an das Sekretariat.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="row">
                    @if($user->releaseCalendar == 1)

                            <div class="col-md-6 col-sm-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>
                                            ICAL-Kalender
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="info">
                                            Die angegebene URL kann in den meisten Kalender-Anwendungen hinzugefügt
                                            werden
                                            um die Termine direkt einzubinden
                                        </p>
                                        <p>
                                            {{config('app.url')."/".$user->uuid.'/ical'}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                    @endif
                    <div class="col-md-6 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h6>
                                    API-Token
                                </h6>
                                <i class="">
                                    Mit dem API-Token können externe Anwendungen auf die Daten-Schnittstellen zugreifen.
                                </i>
                            </div>
                            @if(session()->has('token'))
                                <div class="card-body">
                                    <div class="alert alert-success">
                                        <p>
                                            Das Token wurde erfolgreich erstellt. Bitte speichern Sie das Token an einem sicheren Ort. Er kann nicht noch einmal angezeigt werden.
                                        </p>

                                        <p>
                                            {{session('token')}}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            <div class="card-body">
                                <div class="">
                                    <table class="table">
                                        @foreach(auth()->user()->tokens as $token)
                                            <tr>
                                                <td>
                                                    {{$token->name}}
                                                </td>
                                                <td>
                                                    {{$token->created_at->format('d.m.Y')}}
                                                </td>
                                                <td>
                                                    <form action="{{url('/einstellungen/token/'.$token->id)}}" method="post">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            löschen
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                            </div>
                        </div>
                        <div class="card-footer border-top">
                            <b>
                                Neues Token erstellen
                            </b>
                            <form action="{{url('/einstellungen/token')}}" method="post">
                                @csrf
                                <div class="form-group row">
                                    <div class="col-md-12">
                                        <label for="name" class="col-form-label text-danger">Name</label>
                                        <input id="name" type="text" class="form-control" name="name" required>
                                    </div>
                                </div>
                                <div class="form-group  row mb-0">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            Token erstellen
                                        </button>
                                    </div>  </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @if($user->sorg2 != null)
                <div class="card-footer">
                    <p>
                        Das Konto ist verknüpft mit <b>{{$user->sorgeberechtigter2?->name}}</b>. Dadurch sind
                        die Rückmeldungen in beiden Konten sichtbar.
                </div>
            @endif
        </div>
    </div>
@endsection

@push('js')

    <script>
        $(document).ready(function () {


            $("input").keyup(function() {
                checkChanged();
            });
            $("select").change(function() {
                checkChanged();
            });

            function checkChanged() {

                if (!$('input').val()) {
                    $("#btn-save").hide();
                } else {
                    $("#btn-save").show();
            }
            }
        });


        $('.child-notification').click(function () {
            let child_id = $(this).data('child_id');
            let notification = $(this).data('notification');

            $.ajax({
                url: '/child/' + child_id + '/notification',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    'child_id': child_id,
                    'notification': notification == 1 ? 0 : 1
                },
                success: function (data) {
                    if (data.notification == 1) {
                        $('.child-notification[data-child_id=' + child_id + ']').removeClass('bg-gradient-radial-amber').addClass('bg-gradient-directional-teal text-white').html('<i class="fas fa-bell"></i>');
                        $('.child-notification[data-child_id=' + child_id + ']').data('notification', 1);
                    } else {
                        $('.child-notification[data-child_id=' + child_id + ']').removeClass('bg-gradient-directional-teal text-white').addClass('bg-gradient-radial-amber').html('<i class="fas fa-bell-slash"></i>');
                        $('.child-notification[data-child_id=' + child_id + ']').data('notification', 0);
                    }
                },
                error: function (data) {
                    alert('Fehler beim Speichern');
                }
            });
        });

    </script>

@endpush
