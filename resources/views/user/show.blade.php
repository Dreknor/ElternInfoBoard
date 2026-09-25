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
                                                <label>öffentliche E-Mail (für andere Eltern in den gleichen Gruppen sichtbar)</label>
                                                <input type="email" class="form-control border-input" placeholder="öffentliche E-Mail" name="publicMail" value="{{$user->publicMail}}"  autocomplete="ohne ">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>öffentliche Telefonnummer (für andere Eltern in den gleichen Gruppen sichtbar)</label>
                                                <input type="text" class="form-control border-input" placeholder="öffentliche Telefonnummer" name="publicPhone" value="{{$user->publicPhone}}"  autocomplete="ohne" >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label>Benachrichtigung per E-Mail (letzte E-Mail: {{$user->lastEmail?->format('d.m.Y H:i')}})</label>
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

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>
                                                    Konto-Status
                                                    @if(!$user->is_active)
                                                        <span class="badge badge-danger ml-1">Deaktiviert</span>
                                                        @if($user->deactivated_at)
                                                            <small class="text-muted d-block">Deaktiviert am: {{ $user->deactivated_at->format('d.m.Y H:i') }}</small>
                                                        @endif
                                                    @endif
                                                </label>
                                                <select class="custom-select" name="is_active">
                                                    <option value="1" @if($user->is_active !== false) selected @endif>Aktiv</option>
                                                    <option value="0" @if($user->is_active === false) selected @endif>Deaktiviert</option>
                                                </select>
                                                <small class="form-text text-muted">
                                                    Deaktivierte Benutzer werden beim nächsten Seitenaufruf automatisch ausgeloggt.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    @can('set password')
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>neues Passwort <small class="text-muted">(mind. 10 Zeichen, Groß-/Kleinbuchstaben und Zahl)</small></label>
                                                    <input class="form-control" name="new-password" type="password" minlength="10" autocomplete="new-password">
                                                </div>

                                            </div>
                                        </div>
                                    @endcan

                                        <div class="row">
                                            <div class="col-12">
                                                {{-- Familie (kind-zentriertes Familienmodell, ersetzt sorg2) --}}
                                                <label class="font-weight-bold mb-1">Familie</label>
                                                @if($user->family)
                                                    <p class="mb-1">
                                                        @can('manage families')
                                                            <a href="{{ route('families.show', $user->family) }}">{{ $user->family->name }}</a>
                                                        @else
                                                            {{ $user->family->name }}
                                                        @endcan
                                                        @if($user->family->is_locked)
                                                            <span class="badge badge-secondary">gesperrt</span>
                                                        @endif
                                                    </p>
                                                    <ul class="mb-2">
                                                        @foreach($user->family->users->where('id', '!=', $user->id) as $member)
                                                            <li><a href="{{ url('users/'.$member->id) }}">{{ $member->name }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                    @can('manage families')
                                                        <p class="small">
                                                            <a href="{{ url('users/'.$user->id.'/remove/sorg2/0') }}"
                                                               onclick="return confirm('{{ addslashes($user->name) }} aus der Familie lösen?')">aus Familie lösen</a>
                                                        </p>
                                                    @endcan
                                                @else
                                                    <p class="text-muted mb-1">keiner Familie zugeordnet</p>
                                                @endif
                                                @can('manage families')
                                                    <label for="sorg2" class="small mb-0">Mit Person zu einer Familie verknüpfen:</label>
                                                    <select class="custom-select" name="sorg2" id="sorg2">
                                                        <option value=""></option>
                                                        @foreach($users as $otherUser)
                                                            <option value="{{$otherUser->id}}">{{$otherUser->name}}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Die gewählte Person wird dieser Familie hinzugefügt (bzw. umgekehrt).</small>
                                                @endcan

                                                <label class="font-weight-bold mt-3 mb-1">Kinder</label>
                                                <ul class="mb-0">
                                                    @forelse($user->children_rel as $child)
                                                        <li>
                                                            <a href="{{ route('child.edit', $child) }}#bezugspersonen">{{ $child->first_name }} {{ $child->last_name }}</a>
                                                            <small class="text-muted">
                                                                – {{ $child->pivot->relationType()->label() }}
                                                                ({{ collect(['S' => $child->pivot->has_custody, 'I' => $child->pivot->receives_information, 'V' => $child->pivot->can_manage])->filter()->keys()->implode('/') ?: 'keine Rechte' }},
                                                                {{ $child->pivot->sourceLabel() }})
                                                            </small>
                                                            @if($child->pivot->isPendingReview())
                                                                <span class="badge badge-warning">ungeprüft</span>
                                                            @endif
                                                        </li>
                                                    @empty
                                                        <li class="text-muted">keine Kinder verknüpft</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>


                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success btn-block" id="btn-save" style="display: none;">speichern</button>
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
                                @if($roles->count() > 0)
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
            // Track initial form state
            let formChanged = false;

            $("input, select, textarea").on("input change", function() {
                formChanged = true;
                checkChanged();
            });

            $(":checkbox").on("change", function() {
                formChanged = true;
                checkChanged();
            });

            function checkChanged() {
                if (formChanged) {
                    $("#btn-save").show();
                } else {
                    $("#btn-save").hide();
                }
            }
        });

    </script>

@endpush
