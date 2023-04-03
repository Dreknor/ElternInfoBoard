@extends('layouts.app')
@section('title')
    - Rückmeldungen
@endsection

@section('content')
    <a href="{{url('rueckmeldungen')}}" class="btn btn-round btn-primary">zurück</a>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title">
                            Rückmeldungen erfassen
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{url('userrueckmeldung/'.$rueckmeldung->id.'/save')}}" method="POST"
                      class="form form-horizontal">
                    @csrf
                    <div class="container-fluid">
                        <div class="row">
                            <label for="user">Für welchen Benutzer?</label>
                            <select class="custom-select" name="user">
                                @foreach($users as $user)
                                    <option value="{{$user->id}}">{{$user->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row mt-4 border-top p-4">
                            <div class="container-fluid">
                                @foreach($rueckmeldung->options as $option)
                                    @if($option->type == 'check')
                                        <div class="row">
                                            <div class="col-12">
                                                <label class="label w-100">
                                                    @if($rueckmeldung->max_answers ==1)
                                                        <input type="radio" name="answers[options][]"
                                                               value="{{$option->id}}" class="custom-radio">
                                                    @else
                                                        <input type="checkbox" name="answers[options][]"
                                                               value="{{$option->id}}"
                                                               class="custom-checkbox abfrage_{{$rueckmeldung->id}}">
                                                    @endif
                                                    {{$option->option}}
                                                </label>
                                            </div>
                                        </div>
                                    @else
                                        <div class="row ">
                                            <div class="col-12">
                                                <label class="label w-100">
                                                    {{$option->option}}
                                                    <input name="answers[text][{{$option->id}}]" class="form-control">
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                            </div>
                        </div>

                        <div class="row mt-2">
                            <button type="submit" class="btn btn-round btn-block btn-outline-success"
                                    id="{{$rueckmeldung->id}}_button">
                                absenden
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

@endsection
