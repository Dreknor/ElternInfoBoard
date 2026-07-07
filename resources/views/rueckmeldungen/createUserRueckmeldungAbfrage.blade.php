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
                            <div class="col-12">
                                <label for="user">Für welchen Benutzer?</label>
                                <select class="form-control" id="user-select" name="user" style="width:100%;">
                                    <option value="">— Elternteil auswählen —</option>
                                    @foreach($users as $user)
                                        <option value="{{$user->id}}">{{trim($user->familie_name)}}, {{$user->vorname}}</option>
                                    @endforeach
                                </select>
                            </div>
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
                                    @elseif($option->type == 'trenner')
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <h6>{{$option->option}}</h6>
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

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .select2-container { width: 100% !important; }
    </style>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#user-select').select2({
                placeholder: '— Elternteil auswählen —',
                allowClear: true,
                language: {
                    noResults: function () { return 'Keine Treffer'; },
                    searching: function () { return 'Suche…'; }
                }
            });
        });
    </script>
@endpush

