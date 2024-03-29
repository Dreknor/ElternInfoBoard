<div class="card">
    <div class="card-header  @if($liste->active == 0) bg-info @endif ">
        @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
            <div class="d-inline pull-right">
                <div class="pull-right">
                    <a href="{{url("listen/$liste->id/edit")}}" class="card-link">
                        <i class="fas fa-pencil-alt @if($liste->active == 0) text-gray @endif" title="bearbeiten"></i>
                    </a>
                    @if($liste->active == 0)
                        <a href="{{url("listen/$liste->id/activate")}}"
                           class="card-link">
                            <i class="fas fa-eye  @if($liste->active == 0) text-gray @endif"
                               title="veröffentlichen"></i>
                        </a>
                    @else
                        <a href="{{url("listen/$liste->id/deactivate")}}"
                           class="card-link">
                            <i class="fas fa-eye-slash"
                               title="ausblenden"></i>
                        </a>
                    @endif
                    <a href="{{url("listen/$liste->id/archiv")}}"
                       class="card-link">
                        <i class="fa fa-archive"></i>
                    </a>
                </div>

            </div>
        @endif
        <h5>
            {{$liste->listenname}} @if($liste->active == 0) (inaktiv) @endif


        </h5>
        <div class="row">
            <div class="col-sm-8 col-md-8 col-lg-8">
                <p class="info small">
                    {!! $liste->comment !!}
                </p>
            </div>
        </div>
        <div class="row" id="collapse{{$liste->id}}">
            @foreach($liste->groups as $group)
                <div class="badge">
                    {{$group->name}}@if(!$loop->last), @endif
                </div>
            @endforeach
        </div>


    </div>
    <div class="card-body border-top">
        @if($liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
            <div class="row">
                <div class="col-12">
                    <p>
                        Bisherige Eintragungen:
                        {{$liste->termine->where('reserviert_fuer', '!=', null)->count()}}
                        / {{ $liste->termine->count() }}
                    </p>
                </div>

            </div>
        @endif

        @if($termine->where('listen_id', $liste->id)->count() > 0)
            @foreach($termine->filter(function ($eintrag) use ($liste)
                {
                    if ($eintrag->listen_id == $liste->id and $eintrag->termin->greaterThanOrEqualTo(\Carbon\Carbon::now()))
                    {
                        return $eintrag;
                    }
                })->sortBy('termin') as $eintragung)
                <div class="row">
                    <div class="col-8">
                        <b>Ihr Termin:</b> <br>{{$eintragung->termin->format('d.m.Y H:i')}} Uhr
                    </div>
                    <div class="col-4">

                        <form action="{{url('listen/termine/absagen/'.$eintragung->id)}}" method="post">
                            <a href="{{$eintragung->link($liste->listenname, $liste->duration)->google()}}"
                               class="btn btn-primary btn-sm" target="_blank" title="Goole-Kalender-Link">
                                <img src="{{asset('img/icon-google-cal.png')}}" height="25px">
                            </a>
                            <a href="{{$eintragung->link($liste->listenname,  $liste->duration)->ics()}}"
                               class="btn btn-primary btn-sm" title="ICS-Download für Apple und Windows">
                                <img src="{{asset('img/ics-icon.png')}}" height="25px">
                            </a>
                            @csrf
                            @method("delete")
                            <button type="submit" class="btn btn-xs btn-danger">absagen</button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
        @if($termine->where('listen_id', $liste->id)->count() < 1 or $liste->multiple == 1 or $liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
            <div class="row">
                <a href="{{url("listen/$liste->id")}}" class="btn btn-primary btn-block">
                    Termine anzeigen
                </a>
            </div>
        @endif
    </div>
    <div class="card-footer border-top">
        <small>
            endet: {{$liste->ende->format('d.m.Y')}}
        </small>
        @if(auth()->user()->can('edit terminliste'))
            <div class="badge badge-info pull-right">
                {{$liste->type}}
            </div>
        @endif
    </div>

</div>
