@extends('layouts.app')
@section('title') - Benutzer @endsection

@section('content')
    <form action="{{url('/users/').'/'.$user->id}}" method="post" class="form form-horizontal">
        @csrf
        @method('PUT')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">
                    {{$user->name}}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Benutzer-Einstellungen
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
                                                <input type="text" class="form-control border-input" placeholder="öffentliche E-Mail" name="publicMail" value="{{$user->publicMail}}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label>Benachrichtigung per E-Mail (letzte E-Mail: {{optional($user->lastEmail)->format('d.m.Y H:i')}})</label>
                                                <select class="custom-select" name="benachrichtigung">
                                                    <option value="daily" @if($user->benachrichtigung == 'daily') selected @endif>Täglich (bei neuen Nachrichten)</option>
                                                    <option value="weekly" @if($user->benachrichtigung == 'weekly') selected @endif>Wöchentlich (Freitags)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label>Kopie von Rückmeldungen erhalten</label>
                                                <select class="custom-select" name="sendCopy">
                                                    <option value="1" @if($user->sendCopy == 1) selected @endif >Kopie erhalten</option>
                                                    <option value="0" @if($user->sendCopy == 0) selected @endif >keine Kopie senden</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Muss Passwort ändern</label>
                                                <select class="custom-select" name="changePassword">
                                                    <option value="1" @if($user->changePassword)selected @endif>Ja</option>
                                                    <option value="0" @if(!$user->changePassword)selected @endif>Nein</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>
                                    @can('set password')
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>neues Passwort</label>
                                                    <input class="form-control" name="new-password" type="password" minlength="8">
                                                </div>

                                            </div>
                                        </div>
                                    @endcan

                                        <div class="row">
                                            <div class="col-12">
                                                @if($user->sorg2 != "")
                                                    <p>
                                                        Das Konto ist verknüpft mit
                                                        <b>
                                                            <a href="{{url('users/'.$user->sorg2)}}">
                                                                {{optional($user->sorgeberechtigter2)->name}}
                                                            </a>
                                                        </b>.
                                                    </p>
                                                @else
                                                    <label for="sorg2">Verknüpfen mit:</label>
                                                    <select class="custom-select" name="sorg2" id="sorg2">
                                                        <option value=""></option>
                                                        @foreach($users as $otherUser)
                                                            <option value="{{$otherUser->id}}">{{$otherUser->name}}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        </div>


                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success btn-block collapse" id="btn-save">speichern</button>
                                        </div>
                                    </div>

                            </div>
                        </div>

                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Gruppen
                                </h5>
                            </div>
                            <div class="card-body">
                                @include('include.formGroups')
                            </div>

                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Rollen
                                </h5>
                            </div>
                            <div class="card-body">
                                @can('edit permission')
                                    @foreach($roles as $role)
                                        <div>
                                            <input type="checkbox" id="{{$role->name}}" name="roles[]" value="{{$role->name}}" @if($user->hasRole($role->name)) checked @endif>
                                            <label for="{{$role->name}}">{{$role->name}}</label>
                                        </div>
                                    @endforeach
                                @else
                                    <p>Kein Recht zur Rollenzuordnung</p>
                                @endcan
                            </div>
                            <div class="card-footer">

                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    indiv. Rechte
                                </h5>
                            </div>
                            <div class="card-body">
                                @can('edit permission')
                                    @foreach($permissions as $permission)
                                        <div>
                                            <input type="checkbox" id="{{$permission->name}}" name="permissions[]" value="{{$permission->name}}" @if($user->hasDirectPermission($permission->name)) checked @endif>
                                            <label for="{{$permission->name}}">{{$permission->name}}</label>
                                        </div>
                                    @endforeach
                                @else
                                    <p>Kein Recht zur Rechtevergabe</p>
                                @endcan
                            </div>
                            <div class="card-footer">

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </form>

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

            $(":checkbox").change(function() {
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
