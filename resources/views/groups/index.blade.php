@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @foreach($groups as $group)
            <div class="row mt-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                @if(!$group->protected) <i class="fas fa-unlock-alt"></i> @else <i class="fas fa-lock"></i> @endif
                                {{$group->name}}
                            </h5>
                            @can('view groups')
                                <i>
                                    Es gibt {{$group->users->count()}} Benutzer
                                </i>
                            @endcan
                        </div>
                        <div class="card-body">
                            <div class="container-fluid">
                                <div class="row">
                                    @can('view groups')
                                        @foreach($group->users->chunk(15) as $users)
                                            <div class="col-xl-3 col-md-4 col-sm-12">
                                                <ul class="list-group">
                                                    @foreach($users as $user)
                                                        <li class="list-group-item">
                                                            <a href="mailto://{{$user->email}}" class="card-link">{{$user->name}}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    @else
                                        @foreach($group->users->filter(function ($user){
                                                if ($user->publicMail !=""){ return $user; }
                                            })->chunk(15) as $users)
                                            <div class="col-xl-3 col-md-4 col-sm-12">
                                                <ul class="list-group">
                                                    @foreach($users as $user)
                                                        <li class="list-group-item">
                                                            <a href="mailto://{{$user->publicMail}}" class="card-link">{{$user->name}}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    @endcan
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>
@can('view groups')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Neue Gruppe anlegen
                </h5>
            </div>
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
            <div class="card-body">
                <form action="{{url('groups')}}" method="post" class="form-horizontal">
                    @csrf
                    <div class="row">
                        <div class="col-md-7 col-sm-12">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control border-input" placeholder="Name der Gruppe" name="name" value="{{old('name')}}" required>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label>Bereich</label>
                                <input type="text" class="form-control border-input" name="bereich" placeholder="Bereich der Gruppe" value="{{old('bereich')}}">
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

                                    <input type="checkbox" name="protected" id="protected" value="1" {{ old('protected') ? 'checked="checked"' : '' }}>
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
        </div>
        @endcan
    </div>
@endsection

@section('css')
    <link href="{{asset('css/switch.css')}}" rel="stylesheet" />

@endsection
