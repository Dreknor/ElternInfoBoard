@if(!is_null($user->getRueckmeldung()) and !is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()))
    @foreach($user->getRueckmeldung()->where('post_id', $nachricht->id)->all() as $rueckmeldung)
        <!-- Submitted Feedback -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-lg p-4 mb-4 @if(\Illuminate\Support\Facades\Session::has('id') and \Illuminate\Support\Facades\Session::get('id') == $nachricht->id) ring-2 ring-green-500 shadow-lg @endif">
            <div class="flex flex-col md:flex-row md:items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm font-medium">
                        <i class="fas fa-check-circle"></i>
                        <span>Rückmeldung erfolgt</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-2">am {{$rueckmeldung->created_at->format('d.m.Y')}}</p>
                </div>

                <div class="flex-1 min-w-0 bg-white rounded-lg p-4 shadow-sm">
                    <div class="prose max-w-none text-gray-700">
                        {!! $rueckmeldung->text !!}
                    </div>
                </div>

                @if($nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
                    <div class="flex-shrink-0">
                        <a href="{{url('/userrueckmeldung/edit/'.$rueckmeldung->id)}}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-lg transition-colors duration-200"
                           data-toggle="tooltip" data-placement="top" title="Rückmeldung bearbeiten">
                            <i class="far fa-edit"></i>
                            <span class="hidden md:inline">Bearbeiten</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endif

@if(is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->ende->endOfDay()->lessThan(\Carbon\Carbon::now()->startOfDay()))
    <!-- Expired Feedback -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-200 rounded-full mb-3">
            <i class="fas fa-clock text-gray-500 text-2xl"></i>
        </div>
        <p class="text-gray-600 font-medium">Rückmeldung abgelaufen</p>
        <p class="text-sm text-gray-500 mt-1">Frist endete am {{$nachricht->rueckmeldung->ende->format('d.m.Y')}}</p>
    </div>
@else
    <!-- Feedback Form -->
    <div id="rueckmeldeForm_{{$nachricht->id}}"
         class="@if($nachricht->rueckmeldung->ende->lessThan(\Carbon\Carbon::now()->addWeek())) border-2 border-red-400 @else border border-gray-200 @endif rounded-lg overflow-hidden bg-white @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first())) d-none @endif">

        <!-- Form Header -->
        <div class="bg-gradient-to-r @if($nachricht->rueckmeldung->ende->lessThan(\Carbon\Carbon::now()->addWeek())) from-red-500 to-red-600 @else from-green-500 to-green-600 @endif px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-comment-dots text-white"></i>
                    </div>
                    <div>
                        <h6 class="text-white font-semibold mb-0">Ihre Rückmeldung</h6>
                        <p class="text-white/80 text-xs mb-0">bis spätestens {{$nachricht->rueckmeldung->ende->format('d.m.Y')}}</p>
                    </div>
                </div>
                @if($nachricht->rueckmeldung->ende->lessThan(\Carbon\Carbon::now()->addWeek()))
                    <span class="px-3 py-1 bg-white/20 text-white text-xs font-medium rounded-full">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Bald fällig!
                    </span>
                @endif
            </div>
        </div>

        <!-- Form Body -->
        <form method="post" action="{{url('rueckmeldung').'/'.$nachricht->id}}" class="p-4">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ihre Nachricht
                    </label>
                    <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 rueckmeldung"
                              name="text"
                              rows="8"
                              id="nachricht_{{$nachricht->id}}"
                              placeholder="Ihre Rückmeldung hier eingeben...">{{$nachricht->rueckmeldung->text}}</textarea>
                </div>

                <button type="submit"
                        class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
                        id="btnSave_nachricht_{{$nachricht->id}}">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Rückmeldung senden
                </button>
            </div>
        </form>
    </div>

    @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->multiple == true)
        <!-- Multiple Feedback Button -->
        <div class="mt-4" id="rueckmeldeButton_{{$nachricht->id}}" onclick="showRueckmeldung(event,{{$nachricht->id}})">
            <button class="w-full px-4 py-3 bg-white hover:bg-green-50 text-green-600 font-medium border-2 border-green-500 rounded-lg transition-all duration-200">
                <i class="fas fa-plus-circle mr-2"></i>
                Weitere Rückmeldung hinzufügen
            </button>
        </div>
    @endif
@endif

