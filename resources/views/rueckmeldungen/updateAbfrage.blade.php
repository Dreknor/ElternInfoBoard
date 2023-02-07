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
                            Rückmeldungen bearbeiten
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{url('userrueckmeldung/'.$rueckmeldung->id.'/update/'.$userRueckmeldung->id)}}"
                      method="POST"
                      class="form form-horizontal">
                    @csrf
                    @method('put')
                    <div class="container-fluid">
                        @foreach($rueckmeldung->options as $option)
                            @if($option->type == 'check')
                                <div class="row">
                                    <div class="col-12 border-bottom">
                                        <label class="label w-100">
                                            @if($rueckmeldung->max_answers ==1)
                                                <input type="radio" name="answers[options][]"
                                                       value="{{$option->id}}" class="custom-radio"
                                                       @if($userRueckmeldung->answers->contains('option_id', $option->id)) checked @endif>
                                            @else
                                                <input type="checkbox" name="answers[options][]"
                                                       value="{{$option->id}}"
                                                       class="custom-checkbox abfrage_{{$rueckmeldung->id}}"
                                                       @if($userRueckmeldung->answers->contains('option_id', $option->id)) checked @endif>
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
                                            <input name="answers[text][{{$option->id}}]" class="form-control"
                                                   @if($userRueckmeldung->answers->contains('option_id', $option->id)) value="{{$userRueckmeldung->answers->firstWhere('option_id',$option->id)->answer}}" @endif>
                                        </label>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <div class="row mt-2">
                            <button type="submit" class="btn btn-round btn-block btn-outline-success"
                                    id="{{$rueckmeldung->id}}_button">
                                absenden
                            </button>
                        </div>
                    </div>

                </form>
            </div>
            <div class="card-footer bg-danger mt-5">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <form
                                action="{{url('userrueckmeldung/'.$rueckmeldung->id.'/delete/'.$userRueckmeldung->id)}}"
                                method="POST"
                                class="form form-horizontal pull-right">
                                @csrf
                                @method('delete')

                                <button type="submit" class="btn btn-round  "
                                        id="{{$rueckmeldung->id}}_button">
                                    Rückmeldung löschen
                                </button>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
