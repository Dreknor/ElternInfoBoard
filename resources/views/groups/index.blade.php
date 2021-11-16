@extends('layouts.app')
@section('title') - Gruppen @endsection

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
                                    @can('view groups')
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
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($group->users as $user)

                                                    <tr>
                                                        <td>
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
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
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
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($group->users->filter(function ($user){
                                                    if ($user->publicMail !="" or $user->publicPhone !=""){ return $user; }
                                                }) as $user)
                                                        <tr>
                                                            <td>
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
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    @endcan
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
