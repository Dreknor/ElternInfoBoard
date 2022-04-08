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
                        {{$liste->eintragungen->where('user', '!=', null)->count()}}
                        / {{ $liste->eintragungen->count() }}
                    </p>
                </div>

            </div>
        @endif

        @if($eintragungen->where('listen_id', $liste->id)->count() > 0)
            @foreach($eintragungen->where('listen_id', $liste->id)->sortBy('termin')->all() as $eintragung)
                <div class="row">
                    <div class="col-8">
                        <b>Ihre Eintragung:</b> <br>{{$eintragung->eintragung}}
                    </div>
                    <div class="col-4">
                        <form action="{{url('eintragungen/absagen/'.$eintragung->id)}}" method="post">
                            @csrf
                            @method("delete")
                            <button type="submit" class="btn btn-xs btn-danger">löschen</button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
        @if($eintragungen->where('listen_id', $liste->id)->count() < 1 or $liste->multiple == 1 or $liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
            <div class="row">
                <a href="{{url("listen/$liste->id")}}" class="btn btn-primary btn-block">
                    Eintragungen anzeigen
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
