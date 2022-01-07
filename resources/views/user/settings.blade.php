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
                    <div class="col-md-6 col-sm-12">
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
                                            <div class="form-group">
                                                <label>Benachrichtigung per E-Mail (zuletzt: {{optional($user->lastEmail)->format('d.m.Y H:i')}})</label>
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
                                        <div class="col-md-5 col-sm-12">
                                            <div class="form-group">
                                                <label>Login aufzeichnen um Benachrichtigungen zu erhalten</label>
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
                                        <div class="col-md-6 col-sm-12">
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
                    <div class="col-md-5 offset-md-1 col-sm-12">
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
                                    Sollte die Lerngruppe und/oder Alterststufe ihres Kindes nicht korrekt in den
                                    Gruppen abgebildet sein, wenden Sie sich bitte an das Sekretariat.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                    @if($user->releaseCalendar == 1)
                        <div class="row">
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
                        </div>
                    @endif
            </div>
            @if($user->sorg2 != null)
                <div class="card-footer">
                    <p>
                        Das Konto ist verknüpft mit <b>{{optional($user->sorgeberechtigter2)->name}}</b>. Dadurch sind
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

    </script>

@endpush
