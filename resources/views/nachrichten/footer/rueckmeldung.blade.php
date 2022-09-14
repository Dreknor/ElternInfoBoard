@if(!is_null($user->getRueckmeldung()) and !is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()))
    @foreach($user->getRueckmeldung()->where('post_id', $nachricht->id)->all() as $rueckmeldung)
        <div
            class="card-footer @if(\Illuminate\Support\Facades\Session::has("id") and \Illuminate\Support\Facades\Session::get("id") == $nachricht->id) border border-success bg-success @else border-top @endif">
            <div class="row">
                <div class="col-md-3 col-sm-12">
                    Rückmeldung erfolgt am: {{$rueckmeldung->created_at->format('d.m.Y')}}
                </div>
                <div class="col-md-8 col-sm-11">
                    {!! $rueckmeldung->text !!}
                </div>
                @if($nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
                    <div class="col-md-1 col-sm-1">
                        <a href="{{url('/userrueckmeldung/edit/'.$rueckmeldung->id)}}" class="btn btn-sm btn-warning"
                           id="editTextBtn" data-toggle="tooltip" data-placement="top" title="Rückmeldung bearbeiten">
                            <i class="far fa-edit"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endif
@if(is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->ende->endOfDay()->greaterThan(\Carbon\Carbon::now()->startOfDay()))
    <div class="card-footer border-top">
        <p>Rückmeldung abgelaufen</p>
    </div>
@else
    <div id="rueckmeldeForm_{{$nachricht->id}}"
         class="card-footer border-top @if($nachricht->rueckmeldung->ende->lessThan(\Carbon\Carbon::now()->addWeek())) border border-danger @endif @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first())) d-none @endif ">
        <form method="post" action="{{url('rueckmeldung').'/'.$nachricht->id}}" class="form form-horizontal">
            @csrf
            <div class="col-md-12">
                <div class="form-group">
                    Rückmeldung (bis spätestens {{$nachricht->rueckmeldung->ende->format('d.m.Y')}})
                    <textarea class="form-control border-input textInput rueckmeldung" name="text" rows="15"
                              id="nachricht_{{$nachricht->id}}">{{$nachricht->rueckmeldung->text}}</textarea>
                </div>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-success btn-block collapse"
                        id="btnSave_nachricht_{{$nachricht->id}}">Rückmeldung senden
                </button>
            </div>
        </form>
    </div>
    @if(!is_null($user->getRueckmeldung()->where('post_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->multiple ==true)
        <div class="card-footer" id="rueckmeldeButton_{{$nachricht->id}}"
             onclick="showRueckmeldung(event,{{$nachricht->id}})">
            <a href="#" class="btn btn-block btn-outline-success">weitere Rückmeldung</a>
        </div>
    @endif
@endif
