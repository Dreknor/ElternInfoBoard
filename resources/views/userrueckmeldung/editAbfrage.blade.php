@extends('layouts.app')

@section('content')
    <a href="{{url('/home')}}" class="btn btn-round btn-primary">zurück</a>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                Rückmeldung bearbeiten
            </h5>
        </div>
        <div class="card-body">
            <form method="post" action="{{url('userrueckmeldung').'/'.$userRueckmeldung->id}}"
                  class="form form-horizontal">
                @csrf
                @method('put')
                @foreach($rueckmeldung->options as $option)
                    @if($option->type == 'check')
                        <div class="row ">
                            <div class="col-12">
                                <label class="label w-100 @if($option->required == true) text-danger @endif">
                                    @if($rueckmeldung->max_answers ==1)
                                        <input type="radio" name="answers[options][]"
                                               value="{{$option->id}}" class="custom-radio"
                                                @if($option->required == true) required @endif
                                               @if(!is_null($userRueckmeldung->answers->where('option_id', $option->id)->first())) checked @endif>
                                    @else
                                        <input type="checkbox" name="answers[options][]"
                                               value="{{$option->id}}"
                                                @if($option->required == true) required @endif
                                               class="custom-checkbox abfrage_{{$rueckmeldung->id}}"
                                               @if(!is_null($userRueckmeldung->answers->where('option_id', $option->id)->first())) checked @endif>
                                    @endif
                                    {{$option->option}}
                                </label>
                            </div>
                        </div>
                    @elseif($option->type == 'text')
                        <div class="row ">
                            <div class="col-12">
                                <label class="label w-100 @if($option->required == true) text-danger @endif">
                                    {{$option->option}}
                                    <input name="answers[text][{{$option->id}}]" class="form-control"  @if($option->required == true) required @endif
                                           @if(!is_null($userRueckmeldung->answers->where('option_id', $option->id)->first())) value="{{$userRueckmeldung->answers->where('option_id', $option->id)->first()->answer}}" @endif>
                                </label>
                            </div>
                        </div>
                    @elseif($option->type == 'textbox')
                        <div class="row ">
                            <div class="col-12">
                                <label class="label w-100 @if($option->required == true) text-danger @endif">
                                    {{$option->option}}
                                    <textarea name="answers[text][{{$option->id}}]"
                                         @if($option->required == true) required @endif
                                        class="form-control rueckmeldung">
                                        @if(!is_null($userRueckmeldung->answers->where('option_id', $option->id)->first()))
                                            {!! $userRueckmeldung->answers->where('option_id', $option->id)->first()->answer !!}
                                        @endif
                                    </textarea>
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
            </form>
        </div>

        @endsection

        @push('js')
            <script type="text/javascript">
                // Limit the number of checkboxes that can be selected at one time
                var checkboxLimit_{{$rueckmeldung->id}} = {{($rueckmeldung->max_answers > 0)? $rueckmeldung->max_answers  : 100}};
                $('input.abfrage_{{$rueckmeldung->id}}:checkbox').click(function () {
                    var checkTest = $('input.abfrage_{{$rueckmeldung->id}}:checked').length >= checkboxLimit_{{$rueckmeldung->id}};
                    $('input[type=checkbox][name="answers[options][]"]').not(":checked").attr("disabled", checkTest);

                    if ($('input[name="answers[options][]"]:checked').length < 1) {
                        $('#{{$rueckmeldung->id}}_button').removeClass('visible')
                        $('#{{$rueckmeldung->id}}_button').addClass('invisible')

                    } else {
                        $('#{{$rueckmeldung->id}}_button').addClass('visible')
                        $('#{{$rueckmeldung->id}}_button').removeClass('invisible')
                    }
                });
            </script>
    @endpush
