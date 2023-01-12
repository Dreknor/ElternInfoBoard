@extends('layouts.app')
@section('title') - Datenschutz @endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Gespeicherte Daten für {{$user->name}}
                </h5>
            </div>
            <div class="card-body alert-info">
                <p class="">
                    Die hier dargestellten Informationen beziehen sich ausschließlich auf die in der Datenbank gespeicherten Informationen. Es gibt also innerhalb des Schulzentrums darüber hinausgehende Daten, die hier aber nicht erfasst und daher auch nicht dargestellt werden können.
                    IP-Adressen speichern wir nicht.
                </p>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Benutzerdaten {{$user->name}}
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>
                            Datenfeld
                        </th>
                        <th>
                            Inhalt
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th>
                            Name
                        </th>
                        <td>
                            {{$user->name}}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            E-Mail
                        </th>
                        <td>
                            {{$user->email}}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            öffentliche E-Mail
                        </th>
                        <td>
                            {{$user->publicMail}}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Kennwort
                        </th>
                        <td>
                            Das Kennwort liegt nur in verschlüsselter Form vor und kann nicht nicht decodiert werden
                        </td>
                    </tr>
                    <tr>
                        <th>
                            automatischer Login
                        </th>
                        <td>
                            @if($user->remember_token != "")
                                Es liegt ein verschlüsselter Wert in der Datenbank vor. Dies bedeutet, dass auf dem Endgerät ein Cookie zur Wiedererkennung gespeichert wurde. Ob das Cookie noch exsitiert ist für uns nicht nachprüfbar.
                            @else
                                Es wurde kein Cookie zum Login gespeichert.
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Benutzer erstellt
                        </th>
                        <td>
                            {{$user->created_at->format('d.m.Y H:i:s')}} Uhr
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Benutzer geändert
                        </th>
                        <td>
                            {{$user->updated_at->format('d.m.Y H:i:s')}} Uhr
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Letzter gespeicherter Login
                        </th>
                        <td>
                            @if($user->track_login == 1)
                                {{$user->last_online_at->format('d.m.Y H:i:s')}} Uhr
                            @else
                                Benutzer wünscht keine Aufzeichnung
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Passwortänderung muss erzwungen werden
                        </th>
                        <td>
                            @if($user->changePassword == 1)
                                ja
                            @else
                                nein
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Changelog muss angezeigt werden
                        </th>
                        <td>
                            @if($user->changeSettings == 1)
                                ja
                            @else
                                nein
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b>Letzte versendete Informations-E-mail</b><br>
                            <i class="">versendete dringende Nachrichten oder automatisch erstellte Mails für Listen, Krankmeldungen etc. werden nicht gespeichert</i>
                        </td>
                        <td>
                            {{$user->lastEmail?->format('d.m.Y H:i:s')}}<br>
                            Die Mails werden @if($user->benachrichtigung == "weekly") wöchentlich @else täglich @endif versand. <br>
                            @if($user->sendCopy == "1") Versendete Mails möchte der Benutzer als Kopie. @else Es sollen keine Kopien von versendeten E-mails an den Benutzer geschickt werden. @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Sorgeberechtigter 2
                        </th>
                        <td>
                            @if($user->sorg2 != "")
                                Verknüpft mit {{$user->sorgeberechtigter2->name}}
                            @endif
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>
                            zugeordnete Gruppen
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($user->groups as $group)
                            <div class="btn btn-sm">
                                {{$group->name}}
                            </div>
                            @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>
                            zugeordnete Benutzerrollen
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($user->roles as $role)
                            <div class="btn btn-sm">
                                {{$role->name}}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Krankmeldungen
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Kind</th>
                        <th>von</th>
                        <th>bis</th>
                        <th>Meldung</th>
                        <th>Erstellt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->krankmeldungen as $krankmeldung)
                        <tr>
                            <td>
                                {{$krankmeldung->name}}
                            </td>
                            <td>
                                {{$krankmeldung->start->format('d.m.Y')}}
                            </td>
                            <td>
                                {{$krankmeldung->ende->format('d.m.Y')}}
                            </td>
                            <td>
                                {!! $krankmeldung->kommentar !!}
                            </td>
                            <td>
                                    {{$krankmeldung->created_at->format('d.m.Y h:i ')}} Uhr
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Listeneintragungen
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Liste</th>
                        <th>Termin</th>
                        <th>Anmerkung</th>
                        <th>reserviert</th>
                        <th>geändert</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->getListenTermine() as $eintrag)
                        <tr>
                            <td>
                                {{$eintrag->liste->listenname}}
                            </td>
                            <td>
                                {{$eintrag->termin?->format('d.m.Y h:i ')}} Uhr
                            </td>
                            <td>
                                {{$eintrag->comment}}
                            </td>
                            <td>
                                {{$eintrag->created_at?->format('d.m.Y h:i ')}} Uhr
                            </td>
                            <td>
                                {{$eintrag->updated_at?->format('d.m.Y h:i ')}} Uhr
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Eigene Beiträge
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Überschrift</th>
                        <th>erstellt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->own_posts as $post)
                        <tr>
                            <td>
                                {{$post->header}}
                            </td>
                            <td>
                                {{$post->created_at?->format('d.m.Y h:i ')}} Uhr
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Push-Registrierungen
                </h5>
            </div>
            <div class="card-body">
               <p>
                   Es wurden {{$user->pushSubscriptions->count()}} Geräte für Benachtichtigungen registriert. Die Geräte können durch das ElternInfoBoard nicht näher identifiziert werden.
               </p>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Reinigungstermine
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Aufgabe</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->Reinigung as $reinigung)
                        <tr>
                            <td>
                                {{$reinigung->datum?->format('d.m.Y')}}
                            </td>
                            <td>
                                {{$reinigung->bereich}}: {{$reinigung->aufgabe}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
    </div>
</div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Rückmeldungen
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Nachricht</th>
                        <th>Rückmeldung</th>
                        <th>Erstellt</th>
                        <th>geändert</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->userRueckmeldung as $rueckmeldung)
                        <tr>
                            <td>
                                {{$rueckmeldung->nachricht->header}}
                            </td>
                            <td>
                                {!! $rueckmeldung->text !!}
                            </td>
                            <td>
                                {{$rueckmeldung->created_at?->format('d.m.Y H:i')}}
                            </td>
                            <td>
                                {{$rueckmeldung->updated_at?->format('d.m.Y H:i')}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
    </div>
</div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Schickzeiten
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Name des Kindes</th>
                        <th>Wochentag</th>
                        <th>Art</th>
                        <th>Uhrzeit</th>
                        <th>erstellt</th>
                        <th>gelöscht</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->schickzeiten()->withTrashed()->get() as $schickzeit)
                        <tr @if($schickzeit->deleted_at) class="bg-danger" @endif>
                            <td>
                                {{$schickzeit->child_name}}
                            </td>
                            <td>
                                @switch($schickzeit->weekday)
                                    @case('1')
                                        Montag
                                        @break
                                    @case('2')
                                        Dienstag
                                        @break
                                    @case('3')
                                        Mittwoch
                                        @break
                                    @case('4')
                                        Donnerstag
                                        @break
                                    @case('5')
                                        Freitag
                                        @break
                                @endswitch
                            </td>
                            <td>
                                {{$schickzeit->type}}
                            </td>
                            <td>
                                {{$schickzeit->time}}
                            </td>
                            <td>
                                {{$schickzeit->time}}
                            </td>
                            <td>
                                {{$schickzeit->created_at?->format('d.m.Y h:i ')}} Uhr
                            </td>
                            <td>
                                {{$schickzeit->deleted_at?->format('d.m.Y h:i')}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Kommentare
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Beitrag</th>
                        <th>Kommenatr</th>
                        <th>erstellt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->comments as $comment)
                        <tr>
                            <td>
                                {{$comment->commentable->header}}
                            </td>
                            <td>
                                {{$comment->body}}
                            </td>
                            <td>
                                {{$comment->created_at?->format('d.m.Y h:i ')}} Uhr
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Diskussionen (Elternratsbereich)
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Überschrift</th>
                        <th>Beitrag</th>
                        <th>erstellt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user->discussions as $comment)
                        <tr>
                            <td>
                                {{$comment->header}}
                            </td>
                            <td>
                                {!! $comment->text !!}
                            </td>
                            <td>
                                {{$comment->created_at->format('d.m.Y h:i')}} Uhr
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
