@if(!is_null($user->getRueckmeldung()) and !is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()))
    @foreach($user->getRueckmeldung()->where('post_id', $nachricht->id)->all() as $rueckmeldung)
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            @if($nachricht->rueckmeldung->ende->gte(\Carbon\Carbon::today()))
                                <div class="pull-left ml-2 mr-2">
                                    <a href="{{url('/userrueckmeldung/edit/'.$rueckmeldung->id)}}"
                                       class=" text-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </div>
                            @endif
                            <h6>
                                Ihr Antworten zu: {{ $nachricht->rueckmeldung->text}}
                            </h6>
                        </div>
                        <div class="card-body">
                            @foreach($nachricht->rueckmeldung->options as $option)
                                <div class="row border-bottom">
                                    <div class="col-6">
                                        {{$option->option}}
                                    </div>
                                    <div class="col-6">
                                        @if($rueckmeldung->answers->where('option_id', $option->id)->first() != null)
                                            @switch($option->type)
                                                @case('text')
                                                    {{$rueckmeldung->answers->where('option_id', $option->id)->first()->answer}}
                                                    @break
                                                @case('check')
                                                    <i class="fa fa-check"></i>
                                                    @break
                                            @endswitch
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>

            </div>
        </div>

    @endforeach
@endif
@if($nachricht->rueckmeldung->ende->endOfDay()->greaterThan(\Carbon\Carbon::now()) and ($nachricht->rueckmeldung->multiple == true or $user->getRueckmeldung()->where('post_id', $nachricht->id)->count()==0))
    <div id="rueckmeldeForm_{{$nachricht->id}}"
         class="card-footer @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first())) d-none @endif">
        <div class="card">
            <div class="card-header">
                <h6>{{$nachricht->rueckmeldung->text}}</h6>
            </div>
            <div class="card-body">
                <form action="{{url('userrueckmeldung/'.$nachricht->rueckmeldung->id.'')}}" method="POST"
                      class="form form-horizontal">
                    @csrf
                    @foreach($nachricht->rueckmeldung->options as $option)
                        @if($option->type == 'check')
                            <div class="row ">
                                <div class="col-12">
                                    <label class="label w-100 @if($option->required == true) text-danger @endif">
                                        @if($nachricht->rueckmeldung->max_answers ==1)
                                            <input type="radio" name="answers[options][]"
                                                   value="{{$option->id}}"
                                                   @if($option->required == true) required @endif
                                                   class="custom-radio">
                                        @else
                                            <input type="checkbox" name="answers[options][]"
                                                   value="{{$option->id}}"
                                                   @if($option->required == true) required @endif
                                                   class="custom-checkbox abfrage_{{$nachricht->rueckmeldung->id}}">
                                        @endif
                                        {{$option->option}}
                                    </label>
                                </div>
                            </div>
                        @else
                            <div class="row ">
                                <div class="col-12">
                                    <label class="label w-100 @if($option->required == true) text-danger @endif">
                                        {{$option->option}}
                                        @if($option->type == 'textbox')
                                            <textarea name="answers[text][{{$option->id}}]"
                                                      class="form-control rueckmeldung"
                                                      @if($option->required == true) required @endif
                                                      height="">

                                            </textarea>
                                        @else
                                            <input name="answers[text][{{$option->id}}]"
                                                   @if($option->required == true) required @endif
                                                   class="form-control">
                                        @endif
                                    </label>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    <div class="row mt-2">
                        <button type="submit" class="btn btn-round btn-block btn-outline-success"
                                id="{{$nachricht->rueckmeldung->id}}_button">
                            absenden
                        </button>
                    </div>
                </form>

            </div>

        </div>

    </div>
    @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->multiple == true)
        <div class="card-footer" id="rueckmeldeButton_{{$nachricht->id}}"
             onclick="showRueckmeldung(event,{{$nachricht->id}})">
            <a href="#" class="btn btn-block btn-outline-success">weitere Rückmeldung</a>
        </div>
    @endif
@endif
@can('edit posts')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-footer">
                        @can('manage rueckmeldungen')
                            <div class="pull-right">
                                <a href="{{url('rueckmeldungen/'.$nachricht->rueckmeldung->id."/download")}}">
                                    <i class="fa fa-download"></i>
                                </a>
                            </div>
                        @endcan
                        <h6>Auswertung:</h6>
                        @foreach($nachricht->rueckmeldung->options()->where('type', 'check')->get() as $option)
                            <div class="row border-bottom">
                                <div class="col-2">
                                    {{$option->answers->count()}}
                                </div>
                                <div class="col-10">
                                    {{$option->option}}
                                </div>

                            </div>
                        @endforeach
                    </div>

                </div>

            </div>
        </div>
    </div>
@endcan
@push('js')
    <script type="text/javascript">
        // Limit the number of checkboxes that can be selected at one time
        var checkboxLimit_{{$nachricht->rueckmeldung->id}} = {{($nachricht->rueckmeldung->max_answers > 0)? $nachricht->rueckmeldung->max_answers  : 100}};
        $('input.abfrage_{{$nachricht->rueckmeldung->id}}:checkbox').click(function () {
            var checkTest = $('input.abfrage_{{$nachricht->rueckmeldung->id}}:checked').length >= checkboxLimit_{{$nachricht->rueckmeldung->id}};
            $('input[type=checkbox][name="answers[options][]"]').not(":checked").attr("disabled", checkTest);

            if ($('input[name="answers[options][]"]:checked').length < 1) {
                $('#{{$nachricht->rueckmeldung->id}}_button').removeClass('visible')
                $('#{{$nachricht->rueckmeldung->id}}_button').addClass('invisible')

            } else {
                $('#{{$nachricht->rueckmeldung->id}}_button').addClass('visible')
                $('#{{$nachricht->rueckmeldung->id}}_button').removeClass('invisible')
            }
        });
    </script>
@endpush
