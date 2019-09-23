@if(!is_null($user->rueckmeldungNachricht($nachricht->id)))
    <div class="row">
        <div class="col">
            Rückmeldung erfolgt am {{$user->rueckmeldungNachricht($nachricht->id)->created_at->format('d.m.Y')}}
        </div>
        <div class="col">
            {!! $user->rueckmeldungNachricht($nachricht->id)->text !!}
        </div>
    </div>
@else
    <form method="post" action="{{url('rueckmeldung').'/'.$nachricht->id}}"  class="form form-horizontal">
        @csrf
        <div class="col-md-12">
            <div class="form-group">
                Rückmeldung (bis spätestens {{$nachricht->rueckmeldung->ende->format('d.m.Y')}})
                <textarea class="form-control border-input" name="text" rows="15">{{$nachricht->rueckmeldung->text}}</textarea>
            </div>
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-success btn-block">Rückmeldung senden</button>
        </div>
    </form>
@endif