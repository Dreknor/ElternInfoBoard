@extends('layouts.app')
@section('title') - Gruppen @endsection

@section('content')
    <div class="container-fluid">
        @foreach($groups as $group)
            <div class="row mt-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-10">
                                    <h6 class="card-title">
                                        @if(!$group->protected)
                                            <i class="fas fa-unlock-alt"></i>
                                        @else
                                            <i class="fas fa-lock"></i>
                                        @endif
                                        {{$group->name}}
                                    </h6>
                                </div>
                                @canany(['edit groups', 'delete groups'])
                                    <div class="col-1 ml-auto">
                                        <div class="pull-right">
                                            <a href="#" class="card-link text-black-50" data-toggle="dropdown"
                                               aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                            </a>
                                            <div class="dropdown-menu">
                                                @if(auth()->user()->can('create own group') and $group->owner_id == auth()->user()->id)
                                                    <a href="{{url('groups/'.$group->id.'/add')}}"
                                                       class="dropdown-item">
                                                        <i class="fas fa-user-plus"></i>
                                                        hinzufügen
                                                    </a>
                                                @endcan
                                                @if( auth()->user()->can('delete groups') or $group->owner_id == auth()->user()->id)
                                                    <div class="dropdown-divider"></div>
                                                    <div class="dropdown-footer">
                                                        <div class="dropdown-item dropdown-danger">
                                                            Soll diese Gruppe gelöscht werden? Dies muss per Passwort
                                                            bestätigt werden.
                                                            <form method="post"
                                                                  action="{{url('groups/'.$group->id.'/delete')}}"
                                                                  class="form-horizontal">
                                                                @csrf
                                                                @method('delete')
                                                                <input name="passwort" type="password"
                                                                       placeholder="Passwort eingeben"
                                                                       class="form-control">
                                                                <button type="submit" class="btn btn-danger mt-2">
                                                                    Grupper endgültig löschen
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endcan
                            </div>

                            @canany(['edit groups', 'create own group'])
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <i>
                                            Es gibt {{$group->users->count()}} Benutzer
                                        </i>
                                    </div>
                                    <div class="col-md-6 col-sm-12">
                                        <div class="float-right">

                                        </div>
                                    </div>
                                </div>

                            @endcan
                        </div>
                        <div class="card-body">
                            <div class="container-fluid">
                                @can('edit groups')
                                        <div class="">
                                            <table class="table table-bordered table-striped table-sm">
                                                <thead>
                                                <tr>
                                                    <th>
                                                        Name
                                                    </th>
                                                    <th>
                                                        E-Mail
                                                    </th>
                                                    <th>
                                                        Telefon
                                                    </th>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($group->users as $user)
                                                    <tr>
                                                        <td>
                                                            @can('edit user')
                                                                    <a href="{{url('users/'.$user->id)}}" class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-user-edit"></i>
                                                                    </a>
                                                            @endcan
                                                             {{$user->name}}:
                                                        </td>
                                                        <td>
                                                            @if($user->publicMail !="")
                                                                <a href="mailto://{{$user->publicMail}}" class="card-link">{{$user->publicMail}}</a>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($user->publicPhone !="")
                                                                <a href="tel://{{$user->publicPhone}}" class="card-link">{{$user->publicPhone}}</a>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(auth()->user()->can('create own group') and $group->owner_id == auth()->user()->id)
                                                                <form
                                                                    action="{{url('groups/'.$group->id.'/removeUser')}}"
                                                                    method="post" class="form-inline">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id"
                                                                           value="{{$user->id}}">
                                                                    <button type="submit"
                                                                            class="btn btn-sm btn-outline-danger">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                    <div class="card-body">
                                                    @foreach($group->users->filter(function ($user){
                                                        if ($user->publicMail !="" or $user->publicPhone !=""){ return $user; }
                                                        }) as $user)

                                            <div class="row">
                                                @can('edit user')
                                                    <div class="col-1">
                                                        <a href="{{url('user/'.$user->id)}}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-user-edit"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                {{$user->name}}:
                                            </div>
                                            @if($user->publicMail !="")
                                                <div class="row mt-2">
                                                    <div class="col-auto" style="overflow: hidden">
                                                        <a href="mailto://{{$user->publicMail}}" class="card-link"
                                                           style="font-size: 0.8rem">{{$user->publicMail}}</a>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($user->publicPhone !="")
                                                <div class="row mt-2">
                                                    <div class="col-auto overflow-auto" style="font-size: 0.8em">
                                                        <a href="tel://{{$user->publicPhone}}"
                                                           class="card-link">{{$user->publicPhone}}</a>
                                                    </div>
                                                </div>
                                            @endif
                                                    @endforeach
                                    </div>
                                    @endcan
                                </div>
                            </div>

                    </div>
                    </div>
                </div>

        @endforeach
    </div>

    @if(auth()->user()->can('edit groups') or auth()->user()->can('create own group'))
        <div class="container-fluid">
            <div class="card">
                @if ($errors->any())
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
                @can('create own group')
                    <div class="card-body border-top">
                        <h5>
                            Eigene Gruppe anlegen
                        </h5>
                        <p>
                            Eigene Gruppen werden vom Ersteller verwaltet (Personen hinzufügen etc.). Die Gruppe ist
                            nicht öffentlich und kann nur vom Ersteller gesehen und genutzt werden.
                            Persönliche Gruppen werden grundsätzlich
                            zum {{config('app.own_groups_delete', \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', '2020-07-01 00:00:00')->format('d.m.Y'))}}
                        </p>
                        <form action="{{url('groups/own')}}" method="post" class="form-horizontal">
                            @csrf
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Name</label>
                                        <input type="text" class="form-control border-input"
                                               placeholder="Name der Gruppe" name="name" value="{{old('name')}}"
                                               required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <button type="submit" class="btn btn-success btn-block">
                                    speichern
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
        @can('edit groups')
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <h5>
                            globale Gruppe anlegen
                        </h5>
                        <p>
                            Die hier angelegten Gruppen stehen allen Benutzern zur Verfügung.
                        </p>
                        <form action="{{url('groups')}}" method="post" class="form-horizontal">
                            @csrf
                            <div class="row">
                                <div class="col-md-7 col-sm-12">
                                    <div class="form-group">
                                        <label>Name</label>
                                        <input type="text" class="form-control border-input"
                                               placeholder="Name der Gruppe" name="name" value="{{old('name')}}"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <label>Bereich</label>
                                        <input type="text" class="form-control border-input" name="bereich"
                                               placeholder="Bereich der Gruppe" value="{{old('bereich')}}">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <div class="form-row">
                                        <label>
                                            Geschützt
                                        </label>
                                    </div>
                                    <div class="form-row">
                                        <label class="switch">

                                            <input type="checkbox" name="protected" id="protected"
                                                   value="1" {{ old('protected') ? 'checked="checked"' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <button type="submit" class="btn btn-success btn-block">
                                    speichern
                                </button>
                            </div>
                        </form>
                    </div>
                    @endcan
                </div>
            </div>
        @endif

@endsection
