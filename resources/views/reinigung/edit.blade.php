@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <a href="{{url('reinigung')}}" class="btn btn-primary">zurück</a>
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                Reinigung {{$Bereich}}: {{$datum->format('d.m')}} -  {{$ende->format('d.m.Y')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="editform" class="form-horizontal" action="{{url('reinigung/'.$Bereich)}}" method="post">
                                @csrf
                                <div class="form-row">
                                    <label class="label">
                                        Familie:
                                    </label>
                                    <select name="users_id" class="custom-select">
                                        @foreach($users as $user)
                                            <option value="{{$user->id}}">
                                                {{$user->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label class="label">
                                        Datum:
                                    </label>
                                    <input class="form-control" name="datum" type="date" readonly value="{{$datum->format('Y-m-d')}}">
                                </div>
                                <div class="form-row mt-2">
                                    <label class="label">
                                        Aufgabe:
                                    </label>
                                    <select name="aufgabe" class="custom-select">
                                        @foreach($aufgaben as $task)
                                            <option value="{{$task->id}}">
                                                {{$task->task}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row mt-2">
                                    <label class="label">
                                        Bemerkung:
                                    </label>
                                    <input type="text" name="bemerkung" class="form-control">
                                </div>
                            </form>
                        </div>
                        <div class="card-footer">
                            <button type="submit" form="editform" class="btn btn-success btn-block">
                                speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
    </div>

@endsection
