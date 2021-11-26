@if(!is_null($nachricht->poll))
    <div class="card-footer">
        <div class="card">
            <div class="card-header">
                <div class="pull-right d-inline">
                    @if($nachricht->poll->ends->gt(\Carbon\Carbon::now()))
                        Endet in {{$nachricht->poll->ends->diffInDays(\Carbon\Carbon::now())}} Tagen
                    @else
                        Endete am {{$nachricht->poll->ends->format('d.m.Y')}}
                    @endif
                </div>
                <h5 class="card-title">
                    {{$nachricht->poll->poll_name}}
                </h5>

                <p class="">
                    {{$nachricht->poll->description}}
                </p>
            </div>
            <div class="card-body">
                @if($nachricht->poll->votes->where('author_id', auth()->id())->first() != null or \Carbon\Carbon::now()->greaterThan($nachricht->poll->ends))
                    <ul class="list-group">
                        @foreach($nachricht->poll->options as $option)
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-3 col-md-1 ">
                                        {{($nachricht->poll->answers->where('option_id', $option->id)->count() / $nachricht->poll->answers->count())*100}}
                                        %
                                    </div>
                                    <div class="col">
                                        <div class="">
                                            {{$option->option}}
                                        </div>
                                        <div class="h-50 bg-info"
                                             style="width: {{($nachricht->poll->answers->where('option_id', $option->id)->count() / $nachricht->poll->answers->count())*100}}%"></div>

                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                @else
                    <form action="{{url('poll/'.$nachricht->id.'/vote')}}" method="post" class="form-horizontal">
                        @csrf
                        <ul class="list-group">
                            @foreach($nachricht->poll->options as $option)
                                <li class="list-group-item">
                                    <label>
                                        @if($nachricht->poll->max_number == 1)
                                            <input type="radio" name="{{$nachricht->poll->id}}_answers[]"
                                                   value="{{$option->id}}" class="custom-radio">
                                        @else
                                            <input type="checkbox" name="{{$nachricht->poll->id}}_answers[]"
                                                   value="{{$option->id}}" class="custom-checkbox">
                                        @endif
                                        {{$option->option}}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <button type="submit" class="btn btn-success btn-block invisible"
                                id="{{$nachricht->poll->id}}_button">speichern
                        </button>
                    </form>
                @endif

            </div>
            <div class="card-footer">
                <p>
                    Hinweis: Es wird nur gespeichert, dass ein Benutzer abgestimmt hat, nicht jedoch welche Antwort
                    gegeben wurde. Daher kann die Antwort nach dem Absenden nicht verändert werden.
                </p>
            </div>
        </div>

    </div>

    @push('js')
        <script type="text/javascript">
            // Limit the number of checkboxes that can be selected at one time
            var checkboxLimit = {{$nachricht->poll->max_number}};

            $('input[name="{{$nachricht->poll->id}}_answers[]"]').click(function () {
                var checkTest = $('input[type=checkbox][name="{{$nachricht->poll->id}}_answers[]"]:checked').length >= checkboxLimit;
                $('input[type=checkbox][name="{{$nachricht->poll->id}}_answers[]"]').not(":checked").attr("disabled", checkTest);
                $('input[type=checkbox][name="{{$nachricht->poll->id}}_answers[]"]').not(":checked").attr("disabled", checkTest);

                if ($('input[name="{{$nachricht->poll->id}}_answers[]"]:checked').length < 1) {
                    $('#{{$nachricht->poll->id}}_button').removeClass('visible')
                    $('#{{$nachricht->poll->id}}_button').addClass('invisible')

                } else {
                    $('#{{$nachricht->poll->id}}_button').addClass('visible')
                    $('#{{$nachricht->poll->id}}_button').removeClass('invisible')
                }
            });
        </script>
    @endpush
@endif
