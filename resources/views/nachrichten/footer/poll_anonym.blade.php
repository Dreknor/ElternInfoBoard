@if(!is_null($nachricht->poll))
    <div class="border-t border-gray-200 bg-gradient-to-br from-blue-50 to-indigo-50">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden m-4">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-4 md:px-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2 mb-3">
                    <h5 class="text-lg md:text-xl font-bold text-white mb-0">
                        <i class="fas fa-poll mr-2"></i>
                        {{$nachricht->poll->poll_name}}
                    </h5>
                    <div class="inline-flex items-center px-3 py-1.5 bg-white/20 backdrop-blur-sm rounded-lg text-white text-sm font-medium">
                        <i class="far fa-clock mr-2"></i>
                        @if($nachricht->poll->ends->gt(\Carbon\Carbon::now()))
                            <span class="hidden sm:inline">Endet in </span> {{(int)\Carbon\Carbon::now()->diffInDays($nachricht->poll->ends)}} Tag<span class="hidden sm:inline">en</span>
                        @else
                            <span class="hidden sm:inline">Endete am </span>{{$nachricht->poll->ends->format('d.m.Y')}}
                        @endif
                    </div>
                </div>

                @if($nachricht->poll->description)
                    <p class="text-blue-100 text-sm md:text-base mb-0">
                        {{$nachricht->poll->description}}
                    </p>
                @endif
            </div>
            <div class="p-4 md:p-6">
                @if(($nachricht->poll->votes->where('author_id', auth()->id())->first() != null or \Carbon\Carbon::now()->greaterThan($nachricht->poll->ends) or $nachricht->poll->author_id == auth()->id()) and $nachricht->poll->answers->count() >0)
                    <div class="space-y-4">
                        @foreach($nachricht->poll->options as $option)
                            @php
                                $answerCount = $nachricht->poll->answers->where('option_id', $option->id)->count();
                                $totalCount = $nachricht->poll->answers->count();
                                $percentage = $totalCount > 0 ? round(($answerCount / $totalCount) * 100, 0) : 0;
                            @endphp
                            <div class="bg-gray-50 rounded-lg p-3 md:p-4 hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 text-sm md:text-base">
                                            {{$option->option}}
                                        </div>
                                    </div>
                                    <div class="inline-flex items-center gap-2 text-sm font-bold text-blue-600">
                                        <span class="text-lg">{{$percentage}}%</span>
                                        <span class="text-gray-500 text-xs">
                                            ({{$answerCount}} / {{$totalCount}})
                                        </span>
                                    </div>
                                </div>
                                <div class="relative w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500 ease-out"
                                         style="width: {{$percentage}}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                @else
                    <form action="{{url('poll/'.$nachricht->id.'/vote')}}" method="post">
                        @csrf
                        <div class="space-y-3">
                            @foreach($nachricht->poll->options as $option)
                                <div class="bg-gray-50 rounded-lg p-3 md:p-4 hover:bg-blue-50 hover:border-blue-300 border-2 border-transparent transition-all duration-200 cursor-pointer">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <div class="flex-shrink-0 mt-0.5">
                                            @if($nachricht->poll->max_number == 1)
                                                <input type="radio"
                                                       name="{{$nachricht->poll->id}}_answers[]"
                                                       value="{{$option->id}}"
                                                       class="w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2 cursor-pointer">
                                            @else
                                                <input type="checkbox"
                                                       name="{{$nachricht->poll->id}}_answers[]"
                                                       value="{{$option->id}}"
                                                       class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer">
                                            @endif
                                        </div>
                                        <span class="flex-1 text-gray-800 font-medium text-sm md:text-base select-none">
                                            {{$option->option}}
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @if($nachricht->poll->max_number > 1)
                            <div class="mt-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                <p class="text-blue-800 text-sm mb-0">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Sie können bis zu <strong>{{$nachricht->poll->max_number}}</strong> Optionen auswählen.
                                </p>
                            </div>
                        @endif

                        <button type="submit"
                                class="mt-4 w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-[1.02] invisible opacity-0"
                                id="{{$nachricht->poll->id}}_button">
                            <i class="fas fa-check-circle mr-2"></i>
                            Abstimmen
                        </button>
                    </form>
                @endif

            </div>
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 md:px-6">
                <div class="flex items-start gap-2 text-gray-600 text-xs md:text-sm">
                    <i class="fas fa-shield-alt text-blue-600 mt-0.5 flex-shrink-0"></i>
                    <p class="mb-0">
                        <strong>Datenschutz:</strong> Es wird nur gespeichert, dass ein Benutzer abgestimmt hat, nicht jedoch welche Antwort gegeben wurde. Daher kann die Antwort nach dem Absenden nicht verändert werden.
                    </p>
                </div>
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

                if ($('input[name="{{$nachricht->poll->id}}_answers[]"]:checked').length < 1) {
                    $('#{{$nachricht->poll->id}}_button').removeClass('visible opacity-100');
                    $('#{{$nachricht->poll->id}}_button').addClass('invisible opacity-0');
                } else {
                    $('#{{$nachricht->poll->id}}_button').addClass('visible opacity-100');
                    $('#{{$nachricht->poll->id}}_button').removeClass('invisible opacity-0');
                }
            });
        </script>
    @endpush
@endif
