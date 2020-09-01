@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @foreach($groups as $group)
            <div class="row mt-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                {{$group->name}}
                            </h5>
                            <i>
                                Es gibt {{$group->users->count()}} Benutzer
                            </i>
                        </div>
                        <div class="card-body">
                            <div class="container-fluid">
                                <div class="row">
                                    @foreach($group->users->chunk(15) as $users)
                                        <div class="col-xl-3 col-md-4 col-sm-12">
                                            <ul class="list-group">
                                                @foreach($users as $user)
                                                    <li class="list-group-item">
                                                        {{$user->name}}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>
@endsection