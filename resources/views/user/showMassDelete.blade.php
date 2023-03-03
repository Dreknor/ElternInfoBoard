@extends('layouts.app')
@section('title')
    - Benutzer
@endsection

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title">
                            Benutzerkonten
                        </h5>
                    </div>
                    <div class="col">
                        <p class=" pull-right">
                            <a href="{{url('users/create')}}" class="btn btn-primary">
                                <i class="fa fa-dd-user"></i>
                                Benutzer anlegen
                            </a>
                        </p>

                    </div>
                    @can('import user')
                        <div class="col">
                            <p class=" pull-right">
                                <a href="{{url('users/import')}}" class="btn btn-secondary">
                                    <i class="far fa-address-book"></i>
                                    Benutzer importieren
                                </a>
                            </p>

                        </div>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <table class="table" id="userTable">
                    <thead>
                    <tr>
                        <td></td>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Gruppen</th>
                        <th>Rechte</th>
                        <th>Verknüpft</th>
                        <th>letzte E-Mail</th>
                    </tr>
                    </thead>
                    <tbody>
                    <form action="{{url('users/mass/delete')}}" method="post" class="form-horizontal">
                        @csrf
                        @method('delete')
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    <input type="checkbox" name="users[]" value="{{$user->id}}" checked
                                           class="custom-checkbox">
                                </td>
                                <td>
                                    {{$user->name}}
                                </td>
                                <td>
                                    {{$user->email}}
                                </td>
                                <td class="small">
                                    @foreach($user->groups as $gruppe)
                                        <div class="btn btn-outline-info btn-sm">
                                            {{$gruppe->name}}
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <div class="btn btn-outline-warning btn-sm">
                                            {{$role->name}}
                                        </div>
                                    @endforeach

                                    @foreach($user->permissions as $permission)
                                        <div class="btn btn-outline-danger btn-sm">
                                            {{$permission->name}}
                                        </div>
                                    @endforeach
                                </td>

                                <td>
                                    @if(!is_null($user->sorgeberechtigter2))
                                        {{$user->sorgeberechtigter2->name}}
                                    @endif
                                </td>
                                <td>
                                    {{$user->lastEmail?->format('d.m.Y')}}
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="7">
                                <button type="submit" class="btn btn-danger btn-block">
                                    Ausgewählte löschen
                                </button>
                            </td>
                        </tr>
                    </form>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

